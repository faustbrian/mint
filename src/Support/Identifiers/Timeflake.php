<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Support\Identifiers;

use Cline\Mint\Support\AbstractIdentifier;
use Override;

use function bin2hex;
use function hexdec;
use function mb_substr;
use function sprintf;

/**
 * Timeflake value object for timestamp-based 128-bit identifiers.
 *
 * A 128-bit identifier similar to ULID and UUIDv7, optimized for distributed
 * systems requiring both sortability and UUID compatibility. Combines a
 * millisecond-precision timestamp with random data, providing chronological
 * ordering while maintaining uniqueness across distributed nodes.
 *
 * Structure:
 * - 48 bits: timestamp (milliseconds since Unix epoch, ~8,900 years range)
 * - 80 bits: cryptographically random data
 *
 * Can be represented as UUID-compatible hex format, base62 encoding, or
 * raw bytes, making it interoperable with systems expecting UUID values.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/anthonybudd/timeflake
 */
final class Timeflake extends AbstractIdentifier
{
    /**
     * Get the timestamp component in milliseconds since Unix epoch.
     *
     * Extracts the 48-bit timestamp from the first 6 bytes, providing
     * millisecond precision for approximately 8,900 years from Unix epoch.
     */
    #[Override()]
    public function getTimestamp(): int
    {
        $timestampHex = bin2hex(mb_substr($this->bytes, 0, 6, '8bit'));

        return (int) hexdec($timestampHex);
    }

    /**
     * Get the random component as a hexadecimal string.
     *
     * Returns the 80-bit cryptographically random portion that ensures
     * uniqueness when multiple Timeflakes are generated within the same
     * millisecond across distributed systems.
     */
    public function getRandomness(): string
    {
        return bin2hex(mb_substr($this->bytes, 6, 10, '8bit'));
    }

    /**
     * Convert this Timeflake to standard UUID format.
     *
     * Formats the 128-bit identifier as a hyphenated UUID string
     * (8-4-4-4-12 hexadecimal pattern), enabling compatibility with
     * systems and databases that expect UUID format.
     */
    public function toUuid(): string
    {
        $hex = bin2hex($this->bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            mb_substr($hex, 0, 8),
            mb_substr($hex, 8, 4),
            mb_substr($hex, 12, 4),
            mb_substr($hex, 16, 4),
            mb_substr($hex, 20, 12),
        );
    }

    /**
     * Check if this identifier is sortable by creation time.
     *
     * Timeflakes are inherently sortable due to their timestamp-first
     * structure, ensuring both lexicographic and numeric ordering match
     * chronological order.
     */
    #[Override()]
    public function isSortable(): bool
    {
        return true;
    }
}
