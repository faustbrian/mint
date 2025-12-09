<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms;

use Cline\Mint\Contracts\AlgorithmInterface;
use Cline\Mint\Exceptions\InvalidCuid2FormatException;
use Override;

use const STR_PAD_LEFT;

use function bin2hex;
use function getmypid;
use function hash;
use function hash_algos;
use function hexdec;
use function in_array;
use function mb_str_pad;
use function mb_str_split;
use function mb_strlen;
use function mb_substr;
use function microtime;
use function php_uname;
use function preg_match;
use function random_bytes;
use function random_int;
use function sprintf;

/**
 * CUID2 (Collision-resistant Unique IDentifier v2) algorithm implementation.
 *
 * Generates secure, collision-resistant identifiers using SHA-3 hashing
 * of multiple entropy sources including timestamp, random salt, monotonic
 * counter, and machine fingerprint. Designed for horizontal scaling across
 * distributed systems with minimal coordination requirements.
 *
 * CUID2 improvements over CUID v1:
 * - Configurable length (2-32 characters, default 24)
 * - SHA-3 hashing for improved security
 * - Better collision resistance through additional entropy
 * - No discernible patterns in generated IDs
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/paralleldrive/cuid2
 */
final class Cuid2Algorithm implements AlgorithmInterface
{
    /**
     * Base36 alphabet used for encoding hash output.
     *
     * Uses lowercase alphanumeric characters (0-9, a-z) for URL-safe
     * identifiers that are case-insensitive and easily shareable.
     */
    private const string ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyz';

    /**
     * Default identifier length in characters.
     *
     * 24 characters provides strong collision resistance while maintaining
     * reasonable length for most use cases.
     */
    private const int DEFAULT_LENGTH = 24;

    /**
     * Minimum allowed identifier length.
     *
     * 2 characters is the minimum viable length, though collision resistance
     * is significantly reduced at this length.
     */
    private const int MIN_LENGTH = 2;

    /**
     * Maximum allowed identifier length.
     *
     * 32 characters is the maximum length supported by the base36 encoding
     * of SHA-3 hash output.
     */
    private const int MAX_LENGTH = 32;

    /**
     * Monotonic counter for additional entropy within the same process.
     *
     * Incremented on each ID generation to ensure uniqueness even when
     * multiple IDs are generated within the same millisecond in a single
     * process. Provides ordering guarantees for IDs generated sequentially.
     */
    private static int $counter = 0;

    /**
     * Machine and process fingerprint for distributed uniqueness.
     *
     * Generated once per instance using hostname, process ID, and random
     * data. Ensures IDs are unique across different machines and processes
     * without requiring centralized coordination.
     */
    private readonly string $fingerprint;

    /**
     * Create a new CUID2 algorithm instance.
     *
     * @param int $length The desired length of generated identifiers in characters.
     *                    Must be between 2 and 32 (inclusive). Longer lengths provide
     *                    better collision resistance but result in larger identifiers.
     *                    Defaults to 24 for balanced performance and collision safety.
     */
    public function __construct(
        private readonly int $length = self::DEFAULT_LENGTH,
    ) {
        $this->fingerprint = $this->generateFingerprint();
    }

    /**
     * Generate raw CUID2 ID data.
     *
     * Combines multiple entropy sources (timestamp, random salt, counter,
     * and fingerprint) through SHA-3 hashing to produce a collision-resistant
     * identifier. The first character is always a letter for compatibility
     * with systems requiring alphabetic prefixes.
     *
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function generate(): array
    {
        // First character is always a letter (for compatibility)
        $firstLetter = self::ALPHABET[random_int(10, 35)];

        // Gather entropy
        $time = (string) (int) (microtime(true) * 1_000);
        $salt = $this->generateSalt();
        $counter = (string) (self::$counter++);

        // Hash all entropy sources
        $hash = $this->createHash($time.$salt.$counter.$this->fingerprint);

        // Take the required length (minus 1 for first letter)
        $id = $firstLetter.mb_substr($hash, 0, $this->length - 1);

        return [
            'value' => $id,
            'bytes' => $id,
        ];
    }

    /**
     * Parse a CUID2 ID string into raw data.
     *
     * @param string $value The CUID2 ID string
     *
     * @throws InvalidCuid2FormatException         When the value is not valid
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function parse(string $value): array
    {
        if (!$this->isValid($value)) {
            throw InvalidCuid2FormatException::forValue($value);
        }

        return [
            'value' => $value,
            'bytes' => $value,
        ];
    }

    /**
     * Check if a string is a valid CUID2 ID.
     *
     * Checks three validation rules:
     * 1. Length must be between MIN_LENGTH and MAX_LENGTH (2-32 characters)
     * 2. First character must be a lowercase letter (a-z)
     * 3. Remaining characters must be base36 (0-9, a-z)
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid CUID2 format
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        $length = mb_strlen($value);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            return false;
        }

        // First character must be a letter
        $first = mb_substr($value, 0, 1);

        if (preg_match('/^[a-z]$/', $first) !== 1) {
            return false;
        }

        // Rest must be alphanumeric (base36)
        return preg_match('/^[a-z][0-9a-z]+$/', $value) === 1;
    }

    /**
     * Get the configured identifier length.
     *
     * @return int The length in characters for generated identifiers
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * Create a cryptographic hash from the input entropy string.
     *
     * Uses SHA-3-256 when available (preferred for better security), falling
     * back to SHA-256 on systems without SHA-3 support. The resulting hash
     * is converted to base36 encoding for the final identifier.
     *
     * @param string $input The concatenated entropy sources to hash
     *
     * @return string The base36-encoded hash output
     */
    private function createHash(string $input): string
    {
        // Use SHA-3-256 (or SHA-256 as fallback)
        $hashAlgorithm = in_array('sha3-256', hash_algos(), true) ? 'sha3-256' : 'sha256';
        $hash = hash($hashAlgorithm, $input);

        // Convert hex to base36
        return $this->hexToBase36($hash);
    }

    /**
     * Convert hexadecimal string to base36 encoding.
     *
     * Processes the hex string in 8-character chunks to avoid integer overflow
     * issues. Each chunk is converted to decimal, then to base36, and padded
     * to maintain consistent output length. This provides a compact, URL-safe
     * representation of the hash.
     *
     * @param string $hex The hexadecimal hash string to convert
     *
     * @return string The base36-encoded representation
     */
    private function hexToBase36(string $hex): string
    {
        $result = '';
        $chunks = mb_str_split($hex, 8);

        foreach ($chunks as $chunk) {
            $decimal = hexdec($chunk);
            $base36 = '';

            while ($decimal > 0) {
                $base36 = self::ALPHABET[$decimal % 36].$base36;
                $decimal = (int) ($decimal / 36);
            }

            $result .= mb_str_pad($base36, 6, '0', STR_PAD_LEFT);
        }

        return $result;
    }

    /**
     * Generate a cryptographically random salt for entropy.
     *
     * Uses PHP's cryptographically secure random_bytes() to generate
     * 16 bytes (128 bits) of entropy, converted to hexadecimal.
     *
     * @return string A 32-character hexadecimal random salt
     */
    private function generateSalt(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate a unique fingerprint for this machine and process.
     *
     * Combines hostname, process ID, random data, and timestamp to create
     * a fingerprint that uniquely identifies this generator instance. This
     * ensures IDs are globally unique across distributed systems without
     * requiring centralized coordination or configuration.
     *
     * The fingerprint is generated once during constructor initialization
     * and reused for all IDs generated by this instance.
     *
     * @return string A SHA-256 hash of the combined fingerprint components
     */
    private function generateFingerprint(): string
    {
        $data = sprintf(
            '%s-%d-%s-%s',
            php_uname('n'),
            getmypid(),
            bin2hex(random_bytes(8)),
            (string) microtime(true),
        );

        return hash('sha256', $data);
    }
}
