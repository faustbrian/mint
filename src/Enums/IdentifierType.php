<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Enums;

/**
 * Enum representing all supported identifier types in the Mint library.
 *
 * Provides a type-safe enumeration of available identifier formats, each with distinct
 * characteristics regarding sortability, length, bit size, and use cases. The enum cases
 * map to lowercase string values used for configuration, routing, and generator lookup.
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum IdentifierType: string
{
    /** RFC 4122/9562 Universally Unique Identifiers (128-bit, hyphenated) */
    case Uuid = 'uuid';

    /** Universally Unique Lexicographically Sortable Identifiers (128-bit, Base32) */
    case Ulid = 'ulid';

    /** Twitter Snowflake distributed ID (64-bit, numeric string) */
    case Snowflake = 'snowflake';

    /** Nano ID compact random string (configurable length, URL-safe) */
    case NanoId = 'nanoid';

    /** Sqids short unique ID from numbers (variable length, obfuscated) */
    case Sqid = 'sqid';

    /** Hashids short unique ID from numbers (variable length, customizable alphabet) */
    case Hashid = 'hashid';

    /** K-Sortable Unique Identifier (160-bit, timestamp + random) */
    case Ksuid = 'ksuid';

    /** CUID2 collision-resistant unique identifier (variable length, secure) */
    case Cuid2 = 'cuid2';

    /** TypeID prefixed UUID v7 (variable length: prefix + 26 chars) */
    case TypeId = 'typeid';

    /** XID globally unique sorted ID (96-bit, Base32, MongoDB-inspired) */
    case Xid = 'xid';

    /** MongoDB ObjectID (96-bit, hexadecimal) */
    case ObjectId = 'objectid';

    /** Firebase PushID (120-bit, time-ordered) */
    case PushId = 'pushid';

    /** Timeflake UUID v7-like sortable ID (128-bit, Base62) */
    case Timeflake = 'timeflake';

    /**
     * Check if this identifier type provides lexicographic sortability.
     *
     * Time-sortable identifiers (k-ordered) maintain chronological ordering when sorted
     * as strings, improving database index efficiency and enabling efficient range queries.
     * Non-sortable types prioritize randomness, privacy, or other characteristics over ordering.
     *
     * @return bool True for identifiers that embed timestamps in lexicographically-comparable
     *              positions (ULID, Snowflake, KSUID, TypeID, XID, ObjectID, PushID, Timeflake),
     *              false for random or hash-based types (UUID v4, NanoID, Sqid, Hashid, CUID2).
     *              Note: UUID sortability depends on version (v1/v6/v7 are sortable, v4 is not).
     */
    public function isSortable(): bool
    {
        return match ($this) {
            self::Ulid,
            self::Snowflake,
            self::Ksuid,
            self::TypeId,
            self::Xid,
            self::ObjectId,
            self::PushId,
            self::Timeflake => true,
            self::Uuid,
            self::NanoId,
            self::Sqid,
            self::Hashid,
            self::Cuid2 => false,
        };
    }

    /**
     * Get the typical string length for this identifier type.
     *
     * Returns the standard character count for the identifier's string representation,
     * or null for types with variable length based on configuration or encoded values.
     * Length includes all formatting characters (hyphens, prefixes, etc.).
     *
     * @return null|int The fixed string length in characters, or null for variable-length types.
     *                  Fixed lengths: UUID (36 with hyphens), ULID (26), KSUID (27), CUID2 (24),
     *                  XID (20), ObjectID (24 hex), PushID (20), Timeflake (26).
     *                  Variable: Snowflake (depends on numeric value), NanoID (configurable,
     *                  default 21), Sqid/Hashid (depends on input), TypeID (prefix + 26).
     */
    public function getLength(): ?int
    {
        return match ($this) {
            self::Uuid => 36,        // 8-4-4-4-12 hyphenated format
            self::Ulid => 26,        // Crockford Base32
            self::Snowflake => null, // Variable (numeric string representation)
            self::NanoId => 21,      // Default, configurable via alphabet/size
            self::Sqid => null,      // Variable based on encoded numbers
            self::Hashid => null,    // Variable based on encoded numbers
            self::Ksuid => 27,       // Base62 encoded
            self::Cuid2 => 24,       // Fixed length with entropy
            self::TypeId => null,    // Variable (custom prefix + 26-char suffix)
            self::Xid => 20,         // Base32 encoded
            self::ObjectId => 24,    // Hexadecimal representation
            self::PushId => 20,      // Base64-like encoding
            self::Timeflake => 26,   // Base62 encoded
        };
    }

    /**
     * Get the underlying bit size of this identifier type.
     *
     * Returns the number of bits in the identifier's binary representation, representing
     * the theoretical uniqueness space. Variable-length types (Sqid, Hashid, CUID2) return
     * 0 as their bit size depends on configuration or encoded values.
     *
     * @return int The bit size of the identifier's binary form. Common sizes:
     *             128-bit (UUID, ULID, Timeflake - maximum uniqueness),
     *             96-bit (XID, ObjectID - compact with high uniqueness),
     *             64-bit (Snowflake - efficient for distributed systems),
     *             160-bit (KSUID - extended entropy),
     *             120-bit (PushID - Firebase-optimized),
     *             126-bit (NanoID default - configurable),
     *             0 (variable-length types: Sqid, Hashid, CUID2).
     */
    public function getBitSize(): int
    {
        return match ($this) {
            self::Uuid,
            self::Ulid,
            self::Timeflake => 128,  // Standard 128-bit identifiers
            self::Snowflake => 64,   // 1 reserved + 41 timestamp + 10 machine + 12 sequence
            self::NanoId => 126,     // Default: 21 chars × 6 bits (configurable)
            self::Sqid => 0,         // Variable based on encoded integer values
            self::Hashid => 0,       // Variable based on encoded integer values
            self::Ksuid => 160,      // 32-bit timestamp + 128-bit random
            self::Cuid2 => 0,        // Variable entropy-based length
            self::TypeId => 128,     // UUIDv7 portion (prefix not counted)
            self::Xid,
            self::ObjectId => 96,    // 4-byte timestamp + 5-byte random + 3-byte counter
            self::PushId => 120,     // 8-char timestamp + 12-char random
        };
    }
}
