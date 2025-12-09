<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms;

use Cline\Mint\Contracts\AlgorithmInterface;
use Cline\Mint\Exceptions\InvalidPushIdFormatException;
use Override;

use function mb_strlen;
use function microtime;
use function preg_match;
use function preg_quote;
use function random_int;
use function sprintf;

/**
 * Firebase Push ID algorithm implementation.
 *
 * Generates 120-bit identifiers used by Firebase Realtime Database for ordered
 * child nodes. Push IDs are designed to be lexicographically sortable while
 * maintaining high collision resistance in distributed environments.
 *
 * Structure (20 characters):
 * - 8 characters: Timestamp (milliseconds since Unix epoch, base64-encoded)
 * - 12 characters: Random data (72 bits of randomness)
 *
 * Uses a custom 64-character alphabet that sorts correctly in lexicographic
 * order when timestamps are encoded. When multiple IDs are generated within
 * the same millisecond, the random portion is incremented monotonically to
 * ensure proper ordering.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://firebase.googleblog.com/2015/02/the-2120-ways-to-ensure-unique_68.html
 */
final class PushIdAlgorithm implements AlgorithmInterface
{
    /**
     * The alphabet used for encoding (64 characters).
     *
     * Ordered to ensure lexicographic sorting matches chronological ordering.
     * Starts with '-' and '_' for URL safety, followed by digits and letters.
     */
    private const string ALPHABET = '-0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';

    /**
     * Push ID length (20 characters).
     */
    private const int LENGTH = 20;

    /**
     * Last timestamp used for ID generation (in milliseconds).
     *
     * Tracked to detect when multiple IDs are generated within the same
     * millisecond, triggering monotonic increment of the random portion.
     */
    private int $lastTimestamp = 0;

    /**
     * Last random values (for monotonic increment).
     *
     * Stores the random portion as an array of 12 integers (0-63), representing
     * indices into the alphabet. When generating multiple IDs in the same
     * millisecond, this array is incremented to maintain ordering.
     *
     * @var array<int>
     */
    private array $lastRandom = [];

    /**
     * Generate raw Push ID data.
     *
     * Creates a chronologically sortable ID with the current timestamp encoded
     * in the first 8 characters. If multiple IDs are generated in the same
     * millisecond, the random portion is incremented monotonically rather than
     * regenerated, ensuring proper ordering.
     *
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function generate(): array
    {
        $timestamp = $this->currentTimestamp();
        $value = $this->encodeId($timestamp, true);

        return [
            'value' => $value,
            'bytes' => $value,
        ];
    }

    /**
     * Generate a Push ID from a specific timestamp.
     *
     * Creates a Push ID with a specific timestamp but random payload. Useful
     * for backfilling data or testing. Does not participate in monotonic
     * increment behavior.
     *
     * @param int $timestamp Unix timestamp in milliseconds
     *
     * @return string The generated Push ID value
     */
    public function generateFromTimestamp(int $timestamp): string
    {
        return $this->encodeId($timestamp, false);
    }

    /**
     * Parse a Push ID string into raw data.
     *
     * @param string $value The Push ID string to parse (must be exactly 20 characters)
     *
     * @throws InvalidPushIdFormatException        When the value is not a valid Push ID format
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function parse(string $value): array
    {
        if (!$this->isValid($value)) {
            throw InvalidPushIdFormatException::forValue($value);
        }

        return [
            'value' => $value,
            'bytes' => $value,
        ];
    }

    /**
     * Check if a string is a valid Push ID.
     *
     * Validates that the string is exactly 20 characters and contains only
     * characters from the Push ID alphabet.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid Push ID format
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        if (mb_strlen($value) !== self::LENGTH) {
            return false;
        }

        $pattern = sprintf('/^[%s]{%d}$/', preg_quote(self::ALPHABET, '/'), self::LENGTH);

        return preg_match($pattern, $value) === 1;
    }

    /**
     * Encode a Push ID from a timestamp.
     *
     * @param int  $timestamp          Unix timestamp in milliseconds
     * @param bool $useMonotonicRandom Whether to use monotonic increment for same millisecond
     *
     * @return string The encoded Push ID value
     */
    private function encodeId(int $timestamp, bool $useMonotonicRandom): string
    {
        // Encode timestamp as 8 base64-like characters (most significant first)
        $timestampChars = '';
        $ts = $timestamp;

        for ($i = 7; $i >= 0; --$i) {
            $timestampChars = self::ALPHABET[$ts % 64].$timestampChars;
            $ts = (int) ($ts / 64);
        }

        // Handle random portion
        if ($useMonotonicRandom && $timestamp !== $this->lastTimestamp) {
            // New millisecond: generate fresh random values
            $this->lastRandom = [];

            for ($i = 0; $i < 12; ++$i) {
                $this->lastRandom[$i] = random_int(0, 63);
            }

            $this->lastTimestamp = $timestamp;
        } elseif ($useMonotonicRandom && $timestamp === $this->lastTimestamp) {
            // Same millisecond: increment monotonically for ordering
            $this->incrementRandom();
        }

        // Generate random portion
        $randomChars = '';

        if ($useMonotonicRandom) {
            for ($i = 0; $i < 12; ++$i) {
                $randomChars .= self::ALPHABET[$this->lastRandom[$i]];
            }
        } else {
            // Fresh random for fromTimestamp
            for ($i = 0; $i < 12; ++$i) {
                $randomChars .= self::ALPHABET[random_int(0, 63)];
            }
        }

        return $timestampChars.$randomChars;
    }

    /**
     * Get current timestamp in milliseconds.
     */
    private function currentTimestamp(): int
    {
        return (int) (microtime(true) * 1_000);
    }

    /**
     * Increment the random portion for monotonic generation.
     *
     * Increments the random array as a base-64 number, carrying from least
     * significant to most significant position. This ensures that IDs generated
     * in the same millisecond maintain lexicographic ordering.
     *
     * @internal
     */
    private function incrementRandom(): void
    {
        // Increment from rightmost (least significant) position
        for ($i = 11; $i >= 0; --$i) {
            if ($this->lastRandom[$i] < 63) {
                ++$this->lastRandom[$i];

                return;
            }

            // Carry to next position
            $this->lastRandom[$i] = 0;
        }
    }
}
