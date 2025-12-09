<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms;

use Cline\Mint\Contracts\AlgorithmInterface;
use Cline\Mint\Exceptions\InvalidTimeflakeFormatException;
use Cline\Mint\Support\Base62;
use Override;

use function bin2hex;
use function chr;
use function hex2bin;
use function mb_strlen;
use function microtime;
use function preg_match;
use function random_bytes;

/**
 * Timeflake algorithm implementation.
 *
 * Generates 128-bit identifiers similar to ULID and UUIDv7. Timeflakes combine
 * a 48-bit timestamp with 80 bits of randomness, providing both chronological
 * sorting and collision resistance. Unlike UUIDs, Timeflakes support multiple
 * encoding formats for flexibility.
 *
 * Structure (16 bytes):
 * - 6 bytes: Timestamp (milliseconds since Unix epoch, big-endian)
 * - 10 bytes: Cryptographically secure random data
 *
 * Encoding formats:
 * - Base62: Compact URL-safe representation (~22 characters)
 * - Hexadecimal: UUID-compatible format (32 characters)
 * - Raw bytes: 16-byte binary format
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class TimeflakeAlgorithm implements AlgorithmInterface
{
    /**
     * Timeflake hex pattern (32 hex characters).
     */
    private const string HEX_PATTERN = '/^[0-9a-f]{32}$/i';

    /**
     * Timeflake base62 pattern.
     */
    private const string BASE62_PATTERN = '/^[0-9A-Za-z]+$/';

    /**
     * Timeflake byte length.
     */
    private const int BYTE_LENGTH = 16;

    /**
     * Generate raw Timeflake data.
     *
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function generate(): array
    {
        // 48-bit timestamp (milliseconds since epoch)
        $timestamp = $this->currentTimestamp();
        $timestampBytes = $this->timestampToBytes($timestamp);

        // 80-bit random data
        $random = random_bytes(10);

        $bytes = $timestampBytes.$random;
        $value = Base62::encodeBytes($bytes, 22); // ~22 chars for 128 bits

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Generate raw Timeflake data from a specific timestamp.
     *
     * @param int $timestamp Unix timestamp in milliseconds
     *
     * @return array{value: string, bytes: string}
     */
    public function generateFromTimestamp(int $timestamp): array
    {
        $timestampBytes = $this->timestampToBytes($timestamp);
        $random = random_bytes(10);

        $bytes = $timestampBytes.$random;
        $value = Base62::encodeBytes($bytes, 22);

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Generate raw Timeflake data in hexadecimal format.
     *
     * @return array{value: string, bytes: string}
     */
    public function generateHex(): array
    {
        $timestamp = $this->currentTimestamp();
        $timestampBytes = $this->timestampToBytes($timestamp);
        $random = random_bytes(10);

        $bytes = $timestampBytes.$random;
        $value = bin2hex($bytes);

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Parse a Timeflake string (base62 or hex) into raw data.
     *
     * @param string $value The Timeflake string to parse
     *
     * @throws InvalidTimeflakeFormatException     When the value is not valid
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function parse(string $value): array
    {
        if (!$this->isValid($value)) {
            throw InvalidTimeflakeFormatException::forValue($value);
        }

        $bytes = $this->decodeValue($value);

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Check if a string is a valid Timeflake.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid Timeflake format
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        // Check hex format (32 chars)
        if (mb_strlen($value) === 32 && preg_match(self::HEX_PATTERN, $value) === 1) {
            return true;
        }

        // Check base62 format (18-22 chars typically)
        $length = mb_strlen($value);

        return $length >= 18 && $length <= 26 && preg_match(self::BASE62_PATTERN, $value) === 1;
    }

    /**
     * Get current timestamp in milliseconds.
     */
    private function currentTimestamp(): int
    {
        return (int) (microtime(true) * 1_000);
    }

    /**
     * Convert timestamp to 6 bytes (big-endian).
     */
    private function timestampToBytes(int $timestamp): string
    {
        $bytes = '';

        for ($i = 5; $i >= 0; --$i) {
            $bytes .= chr(($timestamp >> ($i * 8)) & 0xFF);
        }

        return $bytes;
    }

    /**
     * Decode a Timeflake value (hex or base62) to bytes.
     *
     * @throws InvalidTimeflakeFormatException When hex decoding fails
     */
    private function decodeValue(string $value): string
    {
        // Try hex first
        if (mb_strlen($value) === 32 && preg_match(self::HEX_PATTERN, $value) === 1) {
            $bytes = hex2bin($value);

            if ($bytes === false) {
                throw InvalidTimeflakeFormatException::forValue($value);
            }

            return $bytes;
        }

        // Assume base62
        return Base62::decodeBytes($value, self::BYTE_LENGTH);
    }
}
