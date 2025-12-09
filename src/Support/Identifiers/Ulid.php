<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Support\Identifiers;

use Cline\Mint\Support\AbstractIdentifier;
use Cline\Mint\Support\Base32;
use Override;

use function bin2hex;
use function mb_substr;
use function sprintf;

/**
 * ULID (Universally Unique Lexicographically Sortable Identifier) value object.
 *
 * A 128-bit identifier designed as a modern alternative to UUID with improved
 * sortability and human readability. Encoded in Crockford Base32 (26 characters),
 * ULIDs are case-insensitive, URL-safe, and naturally sort by creation time.
 * Combines millisecond-precision timestamp with random data for both uniqueness
 * and chronological ordering.
 *
 * Structure:
 * - 10 characters: timestamp (48 bits, milliseconds since Unix epoch)
 * - 16 characters: randomness (80 bits, cryptographically random)
 *
 * Benefits over UUIDs:
 * - Lexicographically sortable by timestamp
 * - More compact string representation (26 vs 36 characters)
 * - Case-insensitive (unlike hex UUIDs)
 * - No special characters except hyphens in UUID conversion
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/ulid/spec
 */
final class Ulid extends AbstractIdentifier
{
    /**
     * Get the timestamp component in milliseconds since Unix epoch.
     *
     * Decodes the first 10 Crockford Base32 characters to extract the
     * 48-bit millisecond timestamp, providing the creation time with
     * millisecond precision (~8,900 years from Unix epoch).
     */
    #[Override()]
    public function getTimestamp(): int
    {
        $timestampPart = mb_substr($this->value, 0, 10);

        return (int) Base32::decode($timestampPart);
    }

    /**
     * Get the randomness component.
     *
     * Returns the 16-character Crockford Base32 encoded random portion
     * that ensures uniqueness when multiple ULIDs are generated within
     * the same millisecond across distributed systems.
     */
    public function getRandomness(): string
    {
        return mb_substr($this->value, 10, 16);
    }

    /**
     * Check if this identifier is sortable by creation time.
     *
     * ULIDs are inherently sortable as both binary and string representations
     * maintain chronological order due to the timestamp-first structure and
     * Crockford Base32's lexicographic ordering properties.
     */
    #[Override()]
    public function isSortable(): bool
    {
        return true;
    }

    /**
     * Convert this ULID to standard UUID format.
     *
     * Returns the ULID as a hyphenated UUID string (8-4-4-4-12 format).
     * ULIDs and UUIDs share the same 128-bit space, making conversion
     * straightforward and enabling compatibility with UUID-based systems.
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
}
