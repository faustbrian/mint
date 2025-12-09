<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms;

use Cline\Mint\Contracts\AlgorithmInterface;
use Cline\Mint\Exceptions\InvalidUlidFormatException;
use Cline\Mint\Support\Base32;
use Override;

use function bccomp;
use function chr;
use function mb_str_split;
use function mb_strlen;
use function mb_strtoupper;
use function mb_substr;
use function microtime;
use function ord;
use function preg_match;
use function random_bytes;

/**
 * ULID algorithm implementation.
 *
 * Generates 128-bit identifiers following the ULID specification.
 * Designed for distributed systems requiring lexicographically sortable,
 * time-ordered IDs with high entropy. Each ID contains a 48-bit timestamp
 * and 80 bits of randomness.
 *
 * Structure (128 bits):
 * - 48 bits: Timestamp (milliseconds since Unix epoch)
 * - 80 bits: Randomness (cryptographically secure random bytes)
 *
 * Encoded as 26 characters using Crockford Base32:
 * - 10 characters: Timestamp portion
 * - 16 characters: Random portion
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/ulid/spec
 */
final class UlidAlgorithm implements AlgorithmInterface
{
    /**
     * ULID string pattern for validation (Crockford Base32).
     */
    private const string PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/i';

    /**
     * Maximum timestamp value (48 bits).
     */
    private const int MAX_TIMESTAMP = 281_474_976_710_655;

    /**
     * Last timestamp used for monotonic generation.
     */
    private int $lastTimestamp = 0;

    /**
     * Last random value for monotonic increment.
     */
    private string $lastRandom = '';

    /**
     * Create a new ULID algorithm instance.
     *
     * @param bool $monotonic Whether to generate monotonically increasing ULIDs
     */
    public function __construct(
        private readonly bool $monotonic = true,
    ) {}

    /**
     * Generate raw ULID data.
     *
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function generate(): array
    {
        $timestamp = (int) (microtime(true) * 1_000);

        if ($this->monotonic && $timestamp === $this->lastTimestamp && $this->lastRandom !== '') {
            // Increment the random portion
            $random = $this->incrementRandom($this->lastRandom);
        } else {
            $random = random_bytes(10);
        }

        $this->lastTimestamp = $timestamp;
        $this->lastRandom = $random;

        // Encode timestamp (first 10 characters)
        $timestampEncoded = Base32::encode($timestamp, 10);

        // Encode randomness (last 16 characters)
        $randomEncoded = $this->encodeRandom($random);

        $value = mb_strtoupper($timestampEncoded.$randomEncoded);

        // Build bytes: 6 bytes timestamp + 10 bytes random
        $bytes = $this->timestampToBytes($timestamp).$random;

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Parse a ULID string into raw data.
     *
     * @param string $value The ULID string
     *
     * @throws InvalidUlidFormatException          When the value is not valid
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function parse(string $value): array
    {
        if (!$this->isValid($value)) {
            throw InvalidUlidFormatException::forValue($value);
        }

        $value = mb_strtoupper($value);
        $bytes = Base32::decodeBytes($value);

        // Trim to 16 bytes if needed
        if (mb_strlen($bytes, '8bit') > 16) {
            $bytes = mb_substr($bytes, -16, null, '8bit');
        }

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Check if a string is a valid ULID.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid ULID format
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        if (preg_match(self::PATTERN, $value) !== 1) {
            return false;
        }

        // Check timestamp doesn't exceed max value
        $timestampPart = mb_substr(mb_strtoupper($value), 0, 10);
        $timestamp = Base32::decode($timestampPart);
        /** @var numeric-string $timestamp */

        return bccomp($timestamp, (string) self::MAX_TIMESTAMP) <= 0;
    }

    /**
     * Generate a ULID from a specific timestamp.
     *
     * @param int $timestamp Unix timestamp in milliseconds
     *
     * @return array{value: string, bytes: string}
     */
    public function fromTimestamp(int $timestamp): array
    {
        $random = random_bytes(10);

        $timestampEncoded = Base32::encode($timestamp, 10);
        $randomEncoded = $this->encodeRandom($random);

        $value = mb_strtoupper($timestampEncoded.$randomEncoded);
        $bytes = $this->timestampToBytes($timestamp).$random;

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Get whether monotonic generation is enabled.
     */
    public function isMonotonic(): bool
    {
        return $this->monotonic;
    }

    /**
     * Convert timestamp to 6 bytes.
     */
    private function timestampToBytes(int $timestamp): string
    {
        $bytes = '';

        for ($i = 5; $i >= 0; --$i) {
            $bytes = chr(($timestamp >> ($i * 8)) & 0xFF).$bytes;
        }

        return $bytes;
    }

    /**
     * Encode 10 random bytes to 16 Base32 characters.
     */
    private function encodeRandom(string $bytes): string
    {
        // Convert 10 bytes to Base32 (16 characters)
        $chars = mb_str_split(Base32::ALPHABET);
        $result = '';

        // Process 5 bytes at a time
        for ($i = 0; $i < 10; $i += 5) {
            $chunk = mb_substr($bytes, $i, 5, '8bit');
            $n = 0;

            for ($j = 0; $j < 5; ++$j) {
                $n = ($n << 8) | ord($chunk[$j]);
            }

            for ($j = 7; $j >= 0; --$j) {
                $result .= $chars[($n >> ($j * 5)) & 0x1F];
            }
        }

        return $result;
    }

    /**
     * Increment random bytes for monotonic generation.
     */
    private function incrementRandom(string $bytes): string
    {
        $result = $bytes;
        $length = mb_strlen($result, '8bit');

        for ($i = $length - 1; $i >= 0; --$i) {
            $byte = ord($result[$i]);

            if ($byte < 255) {
                $result[$i] = chr($byte + 1);

                break;
            }

            $result[$i] = chr(0);
        }

        return $result;
    }
}
