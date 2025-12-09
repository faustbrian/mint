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
 * KSUID (K-Sortable Unique IDentifier) value object.
 *
 * A 160-bit identifier optimized for distributed systems with K-sortability
 * (lexicographic sorting matches chronological order). Encoded as 27 base62
 * characters for URL-safe, human-readable identifiers. Combines a timestamp
 * with random data to ensure uniqueness across distributed nodes without
 * coordination.
 *
 * Structure:
 * - 32 bits: timestamp (seconds since custom epoch: May 13, 2014)
 * - 128 bits: cryptographically random payload
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/segmentio/ksuid
 */
final class Ksuid extends AbstractIdentifier
{
    /**
     * KSUID epoch starting point: May 13, 2014 00:00:00 UTC.
     *
     * This custom epoch provides approximately 136 years of timestamp range
     * and keeps encoded values shorter than using Unix epoch.
     */
    public const int EPOCH = 1_400_000_000;

    /**
     * Get the timestamp component in milliseconds since Unix epoch.
     *
     * Extracts the 32-bit timestamp from the first 4 bytes, adds the KSUID
     * epoch offset, and converts from seconds to milliseconds for consistency
     * with JavaScript timestamps and other identifier formats.
     */
    #[Override()]
    public function getTimestamp(): int
    {
        /** @var array{1: int} $unpacked */
        $unpacked = unpack('N', mb_substr($this->bytes, 0, 4, '8bit'));

        return (int) ($unpacked[1] + self::EPOCH) * 1_000;
    }

    /**
     * Get the random payload component as a hexadecimal string.
     *
     * Returns the 128-bit cryptographically random portion that ensures
     * uniqueness when multiple KSUIDs are generated within the same second.
     */
    public function getPayload(): string
    {
        return bin2hex(mb_substr($this->bytes, 4, 16, '8bit'));
    }

    /**
     * Check if this identifier is sortable by creation time.
     *
     * KSUIDs are inherently sortable due to their timestamp-first structure,
     * allowing lexicographic string comparison to match chronological order.
     */
    #[Override()]
    public function isSortable(): bool
    {
        return true;
    }
}
