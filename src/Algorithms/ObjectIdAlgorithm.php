<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms;

use Cline\Mint\Contracts\AlgorithmInterface;
use Cline\Mint\Exceptions\InvalidObjectIdFormatException;
use Illuminate\Support\Facades\Date;
use Override;

use function bin2hex;
use function hex2bin;
use function mb_strlen;
use function mb_strtolower;
use function mb_substr;
use function pack;
use function preg_match;
use function random_bytes;
use function random_int;

/**
 * MongoDB ObjectID algorithm implementation.
 *
 * Generates 96-bit (12 byte) BSON ObjectIDs following the MongoDB specification.
 * ObjectIDs are designed for distributed systems where central coordination is
 * impractical. They provide reasonable global uniqueness without requiring network
 * communication.
 *
 * Structure (12 bytes):
 * - 4 bytes: Unix timestamp in seconds (big-endian)
 * - 5 bytes: Random value (unique per machine/process, cached during lifetime)
 * - 3 bytes: Incrementing counter (big-endian, initialized with random value)
 *
 * The timestamp component makes ObjectIDs roughly sortable by creation time.
 * Encoded as 24 lowercase hexadecimal characters.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://www.mongodb.com/docs/manual/reference/method/ObjectId/
 */
final class ObjectIdAlgorithm implements AlgorithmInterface
{
    /**
     * ObjectID string pattern for validation (24 hex characters).
     */
    private const string PATTERN = '/^[0-9a-f]{24}$/i';

    /**
     * Random value cached per process (5 bytes).
     *
     * Generated once per process lifetime to ensure uniqueness across processes
     * and machines. Combines machine identifier, process ID, and additional
     * randomness for collision resistance.
     */
    private static ?string $randomValue = null;

    /**
     * Incrementing counter for uniqueness within the same second (3 bytes).
     *
     * Initialized with a random value and incremented for each generated ID.
     * Wraps at 2^24 (16,777,215) to stay within 3 bytes. This allows up to
     * ~16 million IDs per second per process.
     */
    private static ?int $counter = null;

    /**
     * Generate raw ObjectID data.
     *
     * Combines current timestamp, cached random value, and incrementing counter
     * to produce a globally unique identifier with high probability.
     *
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function generate(): array
    {
        // 4-byte timestamp (seconds since Unix epoch, big-endian)
        $timestamp = Date::now()->getTimestamp();
        $timestampBytes = pack('N', $timestamp);

        // 5-byte random value (cached per process for performance)
        $randomValue = $this->getRandomValue();

        // 3-byte incrementing counter (ensures uniqueness within same second)
        $counter = $this->incrementCounter();
        $counterBytes = pack('N', $counter);
        $counterBytes = mb_substr($counterBytes, 1, 3, '8bit'); // Extract last 3 bytes

        $bytes = $timestampBytes.$randomValue.$counterBytes;
        $value = bin2hex($bytes);

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Generate ObjectID data from a specific timestamp.
     *
     * Useful for backfilling historical data, testing, or creating ObjectIDs
     * that sort to a specific time range. The random value and counter are
     * still generated normally.
     *
     * @param int $timestamp Unix timestamp in seconds
     *
     * @return array{value: string, bytes: string}
     */
    public function fromTimestamp(int $timestamp): array
    {
        $timestampBytes = pack('N', $timestamp);
        $randomValue = $this->getRandomValue();

        $counter = $this->incrementCounter();
        $counterBytes = pack('N', $counter);
        $counterBytes = mb_substr($counterBytes, 1, 3, '8bit');

        $bytes = $timestampBytes.$randomValue.$counterBytes;
        $value = bin2hex($bytes);

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Parse an ObjectID string into raw data.
     *
     * Validates and decodes an ObjectID hex string into its component parts,
     * extracting the timestamp, random value, and counter for inspection.
     * Normalizes the input to lowercase.
     *
     * @param string $value The ObjectID hex string to parse (24 characters)
     *
     * @throws InvalidObjectIdFormatException      When the value is not a valid ObjectID format
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function parse(string $value): array
    {
        if (!$this->isValid($value)) {
            throw InvalidObjectIdFormatException::forValue($value);
        }

        $normalizedValue = mb_strtolower($value);
        $bytes = hex2bin($normalizedValue);

        // hex2bin() should never return false here since isValid() already confirmed valid hex
        if ($bytes === false) {
            throw InvalidObjectIdFormatException::forValue($value);
        }

        return [
            'value' => $normalizedValue,
            'bytes' => $bytes,
        ];
    }

    /**
     * Check if a string is a valid ObjectID.
     *
     * Validates both the length (must be exactly 24 characters) and that all
     * characters are valid hexadecimal digits. Case-insensitive validation.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid ObjectID format
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        if (mb_strlen($value) !== 24) {
            return false;
        }

        return preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * Get or generate the cached random value.
     *
     * Generates the random value once per process and caches it for subsequent
     * calls. This 5-byte value represents a combination of machine, process, and
     * random data to ensure uniqueness across distributed systems.
     *
     * @internal
     * @return string The 5-byte random value
     */
    private function getRandomValue(): string
    {
        if (self::$randomValue === null) {
            self::$randomValue = random_bytes(5);
        }

        return self::$randomValue;
    }

    /**
     * Increment and return the counter value.
     *
     * Initializes the counter with a random value on first call to prevent
     * predictability. Increments for each call and wraps at 2^24 to maintain
     * the 3-byte constraint. Returns the value before incrementing.
     *
     * @internal
     * @return int The current counter value (0-16,777,215)
     */
    private function incrementCounter(): int
    {
        if (self::$counter === null) {
            // Initialize with random value for unpredictability
            self::$counter = random_int(0, 0xFF_FF_FF);
        }

        $counter = self::$counter;
        self::$counter = (self::$counter + 1) & 0xFF_FF_FF; // Wrap at 2^24

        return $counter;
    }
}
