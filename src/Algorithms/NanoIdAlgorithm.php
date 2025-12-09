<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms;

use Cline\Mint\Contracts\AlgorithmInterface;
use Cline\Mint\Exceptions\InvalidNanoIdFormatException;
use Override;

use const M_LN2;

use function assert;
use function ceil;
use function log;
use function mb_strlen;
use function ord;
use function preg_match;
use function preg_quote;
use function random_bytes;
use function sprintf;

/**
 * NanoID algorithm implementation.
 *
 * Generates compact, URL-friendly unique identifiers with configurable length
 * and alphabet. NanoIDs provide similar security to UUIDs but with shorter,
 * more human-readable strings. The default configuration produces 21-character
 * IDs with URL-safe characters (_-A-Za-z0-9), offering collision resistance
 * comparable to UUID v4 with ~10^41 possible values.
 *
 * The algorithm uses cryptographically secure random bytes and applies bitwise
 * masking for uniform distribution across the alphabet.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/ai/nanoid
 * @psalm-immutable
 */
final readonly class NanoIdAlgorithm implements AlgorithmInterface
{
    /**
     * Default alphabet (URL-safe).
     *
     * Contains 64 characters: underscore, hyphen, digits 0-9, lowercase a-z,
     * and uppercase A-Z. This alphabet is optimized for URL safety and avoids
     * ambiguous characters.
     */
    public const string DEFAULT_ALPHABET = '_-0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Default ID length.
     *
     * 21 characters provides approximately the same collision probability as
     * UUID v4 (126 bits of randomness) while being significantly shorter.
     */
    public const int DEFAULT_LENGTH = 21;

    /**
     * The alphabet size (number of unique characters).
     *
     * Calculated during construction based on the provided alphabet. Used for
     * mask calculation and random byte filtering to ensure uniform distribution.
     */
    private int $alphabetSize;

    /**
     * The bitmask for random byte filtering.
     *
     * Calculated as the smallest power of 2 that encompasses the alphabet size,
     * minus 1. This mask is applied to random bytes to efficiently filter values
     * into the valid alphabet range while minimizing rejected bytes.
     */
    private int $mask;

    /**
     * Create a new NanoID algorithm instance.
     *
     * The alphabet must contain at least 2 unique characters. Longer alphabets
     * provide better compression and shorter IDs for equivalent security levels.
     *
     * @param int    $length   Length of generated IDs in characters. Higher values increase
     *                         collision resistance exponentially. Common values: 10-32 characters.
     * @param string $alphabet Character set for ID generation. Must contain at least 2 unique
     *                         characters. Use the predefined constants for common alphabets.
     */
    public function __construct(
        private int $length = self::DEFAULT_LENGTH,
        private string $alphabet = self::DEFAULT_ALPHABET,
    ) {
        $this->alphabetSize = mb_strlen($this->alphabet);
        $this->mask = (2 << (int) (log($this->alphabetSize - 1) / M_LN2)) - 1;
    }

    /**
     * Generate raw NanoID data.
     *
     * Uses cryptographically secure random bytes with bitwise masking to ensure
     * uniform distribution across the alphabet. The algorithm generates random
     * bytes in batches for efficiency while maintaining cryptographic security.
     *
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function generate(): array
    {
        $id = '';
        // Calculate optimal step size (1.6 * mask * length / alphabetSize) for efficiency
        $step = (int) ceil(1.6 * $this->mask * $this->length / $this->alphabetSize);

        // Ensure step is at least 1 for random_bytes
        assert($step >= 1, 'Step size must be at least 1');

        while (mb_strlen($id) < $this->length) {
            $bytes = random_bytes($step);

            for ($i = 0; $i < $step && mb_strlen($id) < $this->length; ++$i) {
                $byte = ord($bytes[$i]) & $this->mask;

                // Skip bytes outside alphabet range to maintain uniform distribution
                if ($byte >= $this->alphabetSize) {
                    continue;
                }

                $id .= $this->alphabet[$byte];
            }
        }

        return [
            'value' => $id,
            'bytes' => $id,
        ];
    }

    /**
     * Parse a NanoID string into raw data.
     *
     * Validates that all characters belong to the configured alphabet. Unlike
     * some ID formats, NanoIDs cannot be decoded to extract metadata since they
     * are purely random.
     *
     * @param string $value The NanoID string to parse
     *
     * @throws InvalidNanoIdFormatException        When the value contains characters not in the alphabet
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function parse(string $value): array
    {
        if (!$this->isValid($value)) {
            throw InvalidNanoIdFormatException::forValue($value);
        }

        return [
            'value' => $value,
            'bytes' => $value,
        ];
    }

    /**
     * Check if a string is a valid NanoID.
     *
     * Validates that the string is non-empty and contains only characters from
     * the configured alphabet. Note that this does not enforce a specific length,
     * allowing validation of NanoIDs generated with different length configurations.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string contains only valid alphabet characters
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        $length = mb_strlen($value);

        if ($length === 0) {
            return false;
        }

        // Verify all characters belong to the configured alphabet
        $pattern = sprintf('/^[%s]+$/', preg_quote($this->alphabet, '/'));

        return preg_match($pattern, $value) === 1;
    }

    /**
     * Get the configured length.
     *
     * Returns the number of characters that will be generated for each ID.
     *
     * @return int The ID length in characters
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * Get the configured alphabet.
     *
     * Returns the character set used for ID generation.
     *
     * @return string The alphabet string
     */
    public function getAlphabet(): string
    {
        return $this->alphabet;
    }
}
