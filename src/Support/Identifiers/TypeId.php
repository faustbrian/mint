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
use function mb_strpos;
use function mb_substr;
use function sprintf;

/**
 * TypeID value object for type-safe, sortable identifiers with semantic prefixes.
 *
 * A modern alternative to traditional UUIDs that combines type safety with
 * sortability through a human-readable prefix and base32-encoded UUIDv7 suffix.
 * The prefix provides context about what type of entity the ID represents (e.g.,
 * "user", "order", "post"), improving code readability and preventing ID misuse.
 *
 * Format: prefix_base32suffix (e.g., user_01h455vb4pex5vsknk084sn02q)
 *
 * Benefits over plain UUIDs:
 * - Type safety: prevents passing wrong ID types to functions
 * - Readability: prefix makes logs and debugging easier
 * - Sortability: UUIDv7 base ensures chronological ordering
 * - Compactness: base32 encoding shorter than hex UUIDs
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/jetify-com/typeid
 */
final class TypeId extends AbstractIdentifier
{
    /**
     * Create a new TypeID instance.
     *
     * @param string $value  Full TypeID string in prefix_suffix format, providing
     *                       both type context and unique identifier in a single
     *                       value suitable for URLs, APIs, and database storage
     * @param string $bytes  Binary representation of the underlying UUIDv7 portion,
     *                       used for low-level operations and conversion to UUID
     *                       format when needed
     * @param string $prefix Type prefix identifying the entity type (e.g., "user",
     *                       "post", "order"). Typically lowercase alphanumeric,
     *                       providing semantic context and preventing ID type
     *                       confusion in codebases.
     * @param string $suffix Base32-encoded UUIDv7 portion providing the actual
     *                       unique identifier with millisecond timestamp and random
     *                       components. Uses Crockford base32 for URL safety and
     *                       human readability.
     */
    public function __construct(
        string $value,
        string $bytes,
        private readonly string $prefix,
        private readonly string $suffix,
    ) {
        parent::__construct($value, $bytes);
    }

    /**
     * Parse a TypeID string into its prefix and suffix components.
     *
     * Splits a TypeID string at the underscore delimiter. If no underscore
     * is found, treats the entire string as a suffix with an empty prefix,
     * allowing for prefix-less TypeIDs in special cases.
     *
     * @param  string                                $value The TypeID string to parse
     * @return array{prefix: string, suffix: string}
     */
    public static function parseString(string $value): array
    {
        $underscorePos = mb_strpos($value, '_');

        if ($underscorePos === false) {
            return ['prefix' => '', 'suffix' => $value];
        }

        return [
            'prefix' => mb_substr($value, 0, $underscorePos),
            'suffix' => mb_substr($value, $underscorePos + 1),
        ];
    }

    /**
     * Get the type prefix.
     *
     * Returns the semantic prefix that identifies the entity type this
     * identifier represents (e.g., "user", "post", "order").
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get the base32-encoded UUIDv7 suffix.
     *
     * Returns the unique identifier portion encoded in Crockford base32,
     * which is more compact and human-readable than hexadecimal UUIDs.
     */
    public function getSuffix(): string
    {
        return $this->suffix;
    }

    /**
     * Get the timestamp component in milliseconds since Unix epoch.
     *
     * Extracts the 48-bit millisecond timestamp from the underlying UUIDv7
     * structure, providing the creation time with millisecond precision.
     */
    #[Override()]
    public function getTimestamp(): int
    {
        $timestampHex = bin2hex(mb_substr($this->bytes, 0, 6, '8bit'));

        return (int) hexdec($timestampHex);
    }

    /**
     * Check if this identifier is sortable by creation time.
     *
     * TypeIDs are inherently sortable due to the underlying UUIDv7 structure,
     * where lexicographic ordering of the suffix matches chronological order.
     * IDs with the same prefix sort chronologically.
     */
    #[Override()]
    public function isSortable(): bool
    {
        return true;
    }

    /**
     * Convert this TypeID to standard UUID format.
     *
     * Returns the underlying UUIDv7 in standard hyphenated format
     * (8-4-4-4-12), useful for systems that require UUID compatibility
     * or when migrating from plain UUIDs to TypeIDs.
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
