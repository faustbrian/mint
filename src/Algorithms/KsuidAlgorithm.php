<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms;

use Cline\Mint\Contracts\AlgorithmInterface;
use Cline\Mint\Exceptions\InvalidKsuidFormatException;
use Cline\Mint\Support\Base62;
use Cline\Mint\Support\Identifiers\Ksuid;
use Illuminate\Support\Facades\Date;
use Override;

use function mb_strlen;
use function pack;
use function preg_match;
use function random_bytes;
use function str_repeat;

/**
 * KSUID (K-Sortable Unique IDentifier) algorithm implementation.
 *
 * Generates 160-bit identifiers with 32-bit timestamp and 128-bit random payload.
 * KSUIDs are lexicographically sortable by generation time, making them ideal for
 * distributed systems where chronological ordering is important. The timestamp
 * component uses a custom epoch (2014-05-13T16:53:20Z) to maximize the usable
 * date range.
 *
 * Structure (160 bits):
 * - 32 bits: Timestamp (seconds since KSUID epoch 2014-05-13T16:53:20Z)
 * - 128 bits: Cryptographically secure random payload
 *
 * Encoded as 27 base62 characters (0-9, A-Z, a-z), providing excellent density
 * while remaining URL-safe and case-sensitive.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/segmentio/ksuid
 * @psalm-immutable
 */
final readonly class KsuidAlgorithm implements AlgorithmInterface
{
    /**
     * KSUID string pattern for validation (27 base62 characters).
     */
    private const string PATTERN = '/^[0-9A-Za-z]{27}$/';

    /**
     * KSUID byte length (20 bytes = 160 bits).
     */
    private const int BYTE_LENGTH = 20;

    /**
     * KSUID string length after base62 encoding.
     */
    private const int STRING_LENGTH = 27;

    /**
     * Create a new KSUID algorithm instance.
     *
     * @param int $epoch Custom epoch timestamp in seconds (default: KSUID epoch 1400000000)
     */
    public function __construct(
        private int $epoch = Ksuid::EPOCH,
    ) {}

    /**
     * Generate raw KSUID data.
     *
     * Creates a KSUID using the current timestamp and cryptographically secure
     * random bytes for the payload. The timestamp is adjusted relative to the
     * configured epoch to maximize the usable date range.
     *
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function generate(): array
    {
        // 32-bit timestamp in seconds, adjusted from KSUID epoch
        $timestamp = Date::now()->getTimestamp() - $this->epoch;
        $timestampBytes = pack('N', $timestamp); // Big-endian unsigned long

        // 128-bit cryptographically secure random payload
        $payload = random_bytes(16);

        $bytes = $timestampBytes.$payload;
        $value = Base62::encodeBytes($bytes, self::STRING_LENGTH);

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Generate a KSUID from a specific timestamp.
     *
     * Useful for backfilling historical data or testing with deterministic timestamps
     * while maintaining randomness in the payload portion.
     *
     * @param int $timestamp Unix timestamp in seconds (standard Unix epoch, not KSUID epoch)
     *
     * @return array{value: string, bytes: string}
     */
    public function fromTimestamp(int $timestamp): array
    {
        $adjustedTimestamp = $timestamp - $this->epoch;
        $timestampBytes = pack('N', $adjustedTimestamp);
        $payload = random_bytes(16);

        $bytes = $timestampBytes.$payload;
        $value = Base62::encodeBytes($bytes, self::STRING_LENGTH);

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Parse a KSUID string into raw data.
     *
     * Validates and decodes a KSUID string back into its binary representation.
     *
     * @param string $value The KSUID string to parse (must be exactly 27 base62 characters)
     *
     * @throws InvalidKsuidFormatException         When the value is not a valid KSUID format
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function parse(string $value): array
    {
        if (!$this->isValid($value)) {
            throw InvalidKsuidFormatException::forValue($value);
        }

        $bytes = Base62::decodeBytes($value, self::BYTE_LENGTH);

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Check if a string is a valid KSUID.
     *
     * Validates both the length and character set. A valid KSUID must be exactly
     * 27 characters long and contain only base62 characters (0-9, A-Z, a-z).
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid KSUID format
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        if (mb_strlen($value) !== self::STRING_LENGTH) {
            return false;
        }

        return preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * Get the configured epoch.
     */
    public function getEpoch(): int
    {
        return $this->epoch;
    }

    /**
     * Get the minimum KSUID (all zeros in payload).
     *
     * Returns the smallest possible KSUID value, useful for range queries or
     * establishing boundaries in distributed systems.
     *
     * @return array{value: string, bytes: string}
     */
    public function min(): array
    {
        $bytes = str_repeat("\x00", self::BYTE_LENGTH);
        $value = Base62::encodeBytes($bytes, self::STRING_LENGTH);

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Get the maximum KSUID (all ones in payload).
     *
     * Returns the largest possible KSUID value, useful for range queries or
     * establishing upper boundaries in distributed systems.
     *
     * @return array{value: string, bytes: string}
     */
    public function max(): array
    {
        $bytes = str_repeat("\xFF", self::BYTE_LENGTH);
        $value = Base62::encodeBytes($bytes, self::STRING_LENGTH);

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }
}
