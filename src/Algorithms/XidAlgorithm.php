<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms;

use Cline\Mint\Contracts\AlgorithmInterface;
use Cline\Mint\Exceptions\InvalidXidFormatException;
use Cline\Mint\Support\Base32Hex;
use Illuminate\Support\Facades\Date;
use Override;

use function mb_strlen;
use function mb_strtolower;
use function mb_substr;
use function pack;
use function preg_match;
use function random_bytes;

/**
 * XID algorithm implementation.
 *
 * Generates 96-bit (12 byte) globally unique identifiers based on
 * the MongoDB ObjectID algorithm with base32hex encoding.
 *
 * Structure:
 * - 4 bytes: timestamp (seconds since Unix epoch)
 * - 5 bytes: machine/process identifier
 * - 3 bytes: counter
 *
 * Encoded as 20 base32hex characters.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class XidAlgorithm implements AlgorithmInterface
{
    /**
     * XID string validation pattern (20 base32hex characters).
     *
     * Base32hex alphabet ranges from 0-9 and a-v (lowercase).
     */
    private const string PATTERN = '/^[0-9a-v]{20}$/';

    /**
     * XID binary byte length.
     *
     * Structure: 4 bytes timestamp + 5 bytes machine ID + 3 bytes counter.
     */
    private const int BYTE_LENGTH = 12;

    /**
     * XID encoded string length in base32hex characters.
     */
    private const int STRING_LENGTH = 20;

    /**
     * Maximum counter value (24-bit).
     */
    private const int MAX_COUNTER = 0xFF_FF_FF;

    /**
     * Cached machine/process identifier for this instance.
     *
     * Generated once per process and reused for all XIDs to maintain
     * consistency within the same machine/process context.
     */
    private static ?string $machineId = null;

    /**
     * Monotonic counter for sequence generation within the same second.
     *
     * Increments with each generated XID and resets to 0 after reaching
     * the maximum 24-bit value (16,777,215) to prevent overflow.
     */
    private static int $counter = 0;

    /**
     * Generate raw XID data with current timestamp.
     *
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function generate(): array
    {
        $timestamp = Date::now()->getTimestamp();

        return $this->generateFromTimestamp($timestamp);
    }

    /**
     * Generate raw XID data from a specific Unix timestamp.
     *
     * @param int $timestamp Unix timestamp in seconds (not milliseconds)
     *
     * @return array{value: string, bytes: string}
     */
    public function generateFromTimestamp(int $timestamp): array
    {
        // 4-byte timestamp
        $timestampBytes = pack('N', $timestamp);

        // 5-byte machine/process ID
        $machineId = $this->getMachineId();

        // 3-byte counter (big-endian)
        $counter = self::$counter++;

        if (self::$counter > self::MAX_COUNTER) {
            self::$counter = 0;
        }

        $counterBytes = pack('N', $counter);
        $counterBytes = mb_substr($counterBytes, 1, 3, '8bit'); // Take last 3 bytes

        $bytes = $timestampBytes.$machineId.$counterBytes;
        $value = Base32Hex::encode($bytes);

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Parse an XID string into raw data.
     *
     * @param string $value The XID string to parse (20 base32hex characters)
     *
     * @throws InvalidXidFormatException           When the string doesn't match XID format
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function parse(string $value): array
    {
        if (!$this->isValid($value)) {
            throw InvalidXidFormatException::forValue($value);
        }

        $bytes = Base32Hex::decode($value);

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Check if a string is a valid XID.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid XID format
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        if (mb_strlen($value) !== self::STRING_LENGTH) {
            return false;
        }

        return preg_match(self::PATTERN, mb_strtolower($value)) === 1;
    }

    /**
     * Get the byte length for XIDs.
     */
    public function getByteLength(): int
    {
        return self::BYTE_LENGTH;
    }

    /**
     * Get the string length for XIDs.
     */
    public function getStringLength(): int
    {
        return self::STRING_LENGTH;
    }

    /**
     * Get or lazily generate the machine/process identifier.
     *
     * Generates a random 5-byte identifier on first call and caches it
     * for subsequent XIDs in this process. In production, this could be
     * derived from hostname and process ID for better uniqueness.
     *
     * @return string The 5-byte machine/process identifier
     */
    private function getMachineId(): string
    {
        if (self::$machineId === null) {
            // Generate random 5-byte machine ID
            self::$machineId = random_bytes(5);
        }

        return self::$machineId;
    }
}
