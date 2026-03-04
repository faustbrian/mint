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
 * MongoDB ObjectID value object for BSON document identifiers.
 *
 * A 96-bit (12 byte) identifier following MongoDB's BSON ObjectID specification,
 * designed for distributed systems where unique IDs must be generated without
 * coordination. Encoded as 24 hexadecimal characters. The timestamp-first
 * structure provides natural insertion ordering in MongoDB collections.
 *
 * Structure:
 * - 4 bytes: timestamp (seconds since Unix epoch, big-endian)
 * - 5 bytes: random value (unique per machine/process, generated once at startup)
 * - 3 bytes: incrementing counter (big-endian, initialized with random value)
 *
 * This design ensures uniqueness across distributed nodes while maintaining
 * rough chronological ordering for efficient indexing.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://www.mongodb.com/docs/manual/reference/method/ObjectId/
 */
final class ObjectId extends AbstractIdentifier
{
    /**
     * Get the timestamp component in milliseconds since Unix epoch.
     *
     * Extracts the 32-bit timestamp from the first 4 bytes using big-endian
     * byte order and converts from seconds to milliseconds for consistency
     * with JavaScript Date objects and other identifier formats.
     */
    #[Override()]
    public function getTimestamp(): int
    {
        /** @var array{1: int} $unpacked */
        $unpacked = unpack('N', mb_substr($this->bytes, 0, 4, '8bit'));

        return (int) $unpacked[1] * 1_000;
    }

    /**
     * Get the random value component as a hexadecimal string.
     *
     * Returns the 5-byte machine/process identifier that was generated once
     * at process startup. This value remains constant for all ObjectIDs
     * created by the same process, helping identify the source of the ID.
     */
    public function getRandomValue(): string
    {
        return bin2hex(mb_substr($this->bytes, 4, 5, '8bit'));
    }

    /**
     * Get the counter component value.
     *
     * Extracts the 3-byte incrementing counter that ensures uniqueness when
     * multiple ObjectIDs are generated within the same second by the same
     * process. The counter is initialized with a random value at startup.
     */
    public function getCounter(): int
    {
        $counterBytes = "\x00".mb_substr($this->bytes, 9, 3, '8bit');

        /** @var array{1: int} $unpacked */
        $unpacked = unpack('N', $counterBytes);

        return (int) $unpacked[1];
    }

    /**
     * Check if this identifier is sortable by creation time.
     *
     * ObjectIDs are inherently sortable due to their timestamp-first structure,
     * making them suitable for time-ordered queries and efficient range scans.
     */
    #[Override()]
    public function isSortable(): bool
    {
        return true;
    }
}
