<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Contracts;

use Stringable;

/**
 * Interface for all identifier value objects.
 *
 * Provides a unified contract for all identifier types in the Mint library, ensuring
 * consistent behavior across UUIDs, ULIDs, Snowflakes, and other implementations.
 * All identifiers are immutable value objects that support string conversion, binary
 * representation, equality comparison, and metadata extraction.
 *
 * This interface extends Stringable to enable implicit string casting, allowing identifiers
 * to be used directly in string contexts (e.g., echo, concatenation, type declarations).
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface IdentifierInterface extends Stringable
{
    /**
     * Get the canonical string representation of the identifier.
     *
     * Returns the identifier in its standard, human-readable format suitable for
     * storage, transmission, and display. Format varies by type (e.g., hyphenated
     * UUID, base32 ULID, numeric Snowflake).
     *
     * @return string The identifier's canonical string representation. Format is
     *                type-specific: UUIDs use 8-4-4-4-12 hyphenated format, ULIDs
     *                use 26-character Crockford Base32, Snowflakes use decimal strings.
     *                Always returns the same value for the same identifier instance.
     */
    public function toString(): string;

    /**
     * Get the binary representation of the identifier.
     *
     * Returns the raw byte sequence representing the identifier's value, suitable for
     * compact storage, cryptographic operations, or binary protocols. Length varies by
     * identifier type (e.g., 16 bytes for UUID/ULID, 8 bytes for Snowflake).
     *
     * @return string The identifier's binary representation as a raw byte string.
     *                Length is type-specific: 16 bytes for 128-bit identifiers
     *                (UUID, ULID, Timeflake), 12 bytes for 96-bit (XID, ObjectID),
     *                8 bytes for 64-bit (Snowflake). Not guaranteed to be UTF-8.
     */
    public function toBytes(): string;

    /**
     * Check if this identifier equals another identifier instance.
     *
     * Performs value-based equality comparison, comparing the underlying identifier
     * values rather than object identity. Returns true only if both identifiers
     * represent the exact same value.
     *
     * @param self $other The identifier to compare against. Can be of different concrete
     *                    type; equality is based on value, not type. However, different
     *                    identifier types (UUID vs ULID) will typically never equal each
     *                    other even with identical byte representations.
     *
     * @return bool True if both identifiers represent the same value, false otherwise.
     *              Comparison is type-aware: a UUID never equals a ULID even if their
     *              byte representations coincidentally match.
     */
    public function equals(self $other): bool;

    /**
     * Extract the timestamp component from time-based identifiers.
     *
     * Returns the embedded creation time for identifiers that incorporate temporal
     * information (ULID, UUID v1/v6/v7, Snowflake, etc.). Non-time-based identifiers
     * (UUID v4, NanoID) return null as they lack timestamp components.
     *
     * @return null|int Unix timestamp in milliseconds since epoch (1970-01-01 00:00:00 UTC),
     *                  or null for non-time-based identifiers. For time-based types, the
     *                  timestamp reflects the identifier's creation time with millisecond
     *                  precision. Can be converted to seconds via intdiv($timestamp, 1000).
     */
    public function getTimestamp(): ?int;

    /**
     * Check if this identifier type provides lexicographic sortability.
     *
     * Determines whether identifiers of this type are k-ordered (sortable by creation time).
     * Sortable identifiers maintain chronological order when sorted lexicographically as
     * strings, improving database index performance and range query efficiency.
     *
     * @return bool True if identifiers are time-ordered and sortable (ULID, Snowflake,
     *              UUID v7, etc.), false for random or non-temporal types (UUID v4, NanoID).
     *              Sortability implies that string comparison reflects temporal ordering.
     */
    public function isSortable(): bool;
}
