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
use function mb_substr;
use function unpack;

/**
 * XID value object.
 *
 * Represents a 96-bit (12 byte) globally unique, sortable identifier based on
 * the MongoDB ObjectID algorithm but with base32hex encoding. XIDs are compact,
 * URL-safe, and lexicographically sortable by creation time.
 *
 * Structure (12 bytes total):
 * - 4 bytes: Unix timestamp in seconds (big-endian)
 * - 5 bytes: Machine/process identifier for uniqueness across systems
 * - 3 bytes: Monotonic counter for same-second collisions
 *
 * Encoded as 20 base32hex characters (lowercase), making them shorter than
 * UUIDs while maintaining uniqueness guarantees.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/rs/xid
 */
final class Xid extends AbstractIdentifier
{
    /**
     * Get the timestamp component.
     *
     * Extracts the Unix timestamp in seconds from the first 4 bytes and
     * converts it to milliseconds for consistency with other identifier types.
     *
     * @return int Unix timestamp in milliseconds
     */
    #[Override()]
    public function getTimestamp(): int
    {
        // First 4 bytes are the timestamp (big-endian)
        /** @var array{1: int} $unpacked */
        $unpacked = unpack('N', mb_substr($this->bytes, 0, 4, '8bit'));

        return (int) $unpacked[1] * 1_000; // Convert to milliseconds
    }

    /**
     * Get the machine/process identifier as hexadecimal string.
     *
     * Returns the 5-byte machine/process identifier portion that ensures
     * uniqueness across different machines and processes. This value is
     * typically derived from hostname and process ID.
     *
     * @return string 10-character hexadecimal string representing the machine/process ID
     */
    public function getMachineId(): string
    {
        return bin2hex(mb_substr($this->bytes, 4, 5, '8bit'));
    }

    /**
     * Get the monotonic counter value.
     *
     * Extracts the 3-byte counter that increments for each XID generated
     * within the same second, preventing collisions in high-throughput scenarios.
     *
     * @return int Counter value (0-16777215)
     */
    public function getCounter(): int
    {
        $counterBytes = "\x00".mb_substr($this->bytes, 9, 3, '8bit');

        /** @var array{1: int} $unpacked */
        $unpacked = unpack('N', $counterBytes);

        return (int) $unpacked[1];
    }

    /**
     * Check if this XID is sortable by timestamp.
     *
     * XIDs are always sortable since their timestamp is stored in the
     * first 4 bytes, allowing lexicographic sorting to match chronological order.
     *
     * @return bool Always returns true
     */
    #[Override()]
    public function isSortable(): bool
    {
        return true;
    }
}
