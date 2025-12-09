<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Support\Identifiers;

use Cline\Mint\Enums\UuidVersion;
use Cline\Mint\Support\AbstractIdentifier;
use Override;

use function dechex;
use function hexdec;
use function mb_substr;
use function str_replace;

/**
 * UUID (Universally Unique Identifier) value object.
 *
 * Represents a 128-bit UUID conforming to RFC 4122 and RFC 9562 standards
 * in standard format (xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx). Supports multiple
 * UUID versions with version-specific timestamp extraction for time-based variants.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://datatracker.ietf.org/doc/html/rfc4122
 * @see https://datatracker.ietf.org/doc/html/rfc9562
 */
final class Uuid extends AbstractIdentifier
{
    /**
     * Create a new UUID instance.
     *
     * @param string      $value   The UUID string in standard format (e.g., "550e8400-e29b-41d4-a716-446655440000")
     * @param string      $bytes   The binary representation of the UUID (16 bytes)
     * @param UuidVersion $version The UUID version (V1, V3, V4, V5, V6, V7, or V8)
     */
    public function __construct(
        string $value,
        string $bytes,
        private readonly UuidVersion $version,
    ) {
        parent::__construct($value, $bytes);
    }

    /**
     * Get the UUID version.
     *
     * Returns the version identifier indicating the UUID generation algorithm
     * used (e.g., V4 for random, V7 for timestamp-based).
     *
     * @return UuidVersion The UUID version enum value
     */
    public function getVersion(): UuidVersion
    {
        return $this->version;
    }

    /**
     * Get the timestamp component if this is a time-based UUID (v1, v6, v7).
     *
     * Extracts and converts the embedded timestamp to Unix milliseconds for
     * time-based UUID versions. Returns null for non-time-based versions (v3, v4, v5, v8).
     *
     * @return null|int Unix timestamp in milliseconds, or null if not time-based
     */
    #[Override()]
    public function getTimestamp(): ?int
    {
        return match ($this->version) {
            UuidVersion::V1 => $this->getV1Timestamp(),
            UuidVersion::V6 => $this->getV6Timestamp(),
            UuidVersion::V7 => $this->getV7Timestamp(),
            default => null,
        };
    }

    /**
     * Check if this UUID is sortable by timestamp.
     *
     * Sortable UUIDs have their timestamp bits arranged for lexicographic
     * ordering (v6, v7), enabling efficient database indexing and range queries.
     *
     * @return bool True if the UUID version supports sortable ordering
     */
    #[Override()]
    public function isSortable(): bool
    {
        return $this->version->isSortable();
    }

    /**
     * Extract timestamp from UUID v1.
     *
     * UUID v1 encodes timestamp as 100-nanosecond intervals since October 15, 1582
     * (Gregorian calendar epoch), split across three fields in the UUID structure.
     * This method reconstructs the original timestamp and converts it to Unix milliseconds.
     *
     * @return int Unix timestamp in milliseconds
     */
    private function getV1Timestamp(): int
    {
        $hex = str_replace('-', '', $this->value);
        // UUID v1 timestamp is in 100-nanosecond intervals since Oct 15, 1582
        $timeHigh = mb_substr($hex, 12, 4);
        $timeMid = mb_substr($hex, 8, 4);
        $timeLow = mb_substr($hex, 0, 8);

        // Remove version nibble (4 bits) from the time_hi_and_version field
        $timeHigh = dechex(hexdec($timeHigh) & 0x0F_FF);

        $timestamp = hexdec($timeHigh.$timeMid.$timeLow);

        // Convert from 100-nanosecond intervals since Oct 15, 1582 to milliseconds since Unix epoch
        $gregorianOffset = 122_192_928_000_000_000;

        return (int) (($timestamp - $gregorianOffset) / 10_000);
    }

    /**
     * Extract timestamp from UUID v6.
     *
     * UUID v6 improves on v1 by reordering timestamp bits for better sorting,
     * placing high-order time bits first. This maintains time-based generation
     * while enabling database-friendly lexicographic ordering.
     *
     * @return int Unix timestamp in milliseconds
     */
    private function getV6Timestamp(): int
    {
        $hex = str_replace('-', '', $this->value);
        // UUID v6 has reordered timestamp for better sorting
        $timeHigh = mb_substr($hex, 0, 8);
        $timeMid = mb_substr($hex, 8, 4);
        $timeLow = mb_substr($hex, 13, 3);

        $timestamp = hexdec($timeHigh.$timeMid.$timeLow);

        // Convert from 100-nanosecond intervals since Oct 15, 1582 to milliseconds since Unix epoch
        $gregorianOffset = 122_192_928_000_000_000;

        return (int) (($timestamp - $gregorianOffset) / 10_000);
    }

    /**
     * Extract timestamp from UUID v7.
     *
     * UUID v7 stores Unix timestamp in milliseconds directly in the first 48 bits,
     * providing the most straightforward timestamp extraction and optimal sorting
     * characteristics for modern databases.
     *
     * @return int Unix timestamp in milliseconds
     */
    private function getV7Timestamp(): int
    {
        $hex = str_replace('-', '', $this->value);
        // UUID v7 stores Unix timestamp in milliseconds in the first 48 bits
        $timestampHex = mb_substr($hex, 0, 12);

        return (int) hexdec($timestampHex);
    }
}
