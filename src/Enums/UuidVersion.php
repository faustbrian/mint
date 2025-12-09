<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Enums;

/**
 * Enum representing UUID versions defined in RFC 4122 and RFC 9562.
 *
 * Provides type-safe enumeration of UUID specification versions, each with distinct
 * generation algorithms and use cases. Modern applications should prefer version 7
 * for database primary keys and version 4 for security tokens or non-sequential needs.
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum UuidVersion: int
{
    /** Time-based UUID with MAC address and timestamp (RFC 4122) */
    case V1 = 1;

    /** Name-based UUID using MD5 hashing (RFC 4122, deprecated for security) */
    case V3 = 3;

    /** Random UUID with maximum entropy (RFC 4122) */
    case V4 = 4;

    /** Name-based UUID using SHA-1 hashing (RFC 4122) */
    case V5 = 5;

    /** Reordered time-based UUID optimized for database indexing (RFC 9562) */
    case V6 = 6;

    /** Unix Epoch time-based UUID with random entropy (RFC 9562, recommended) */
    case V7 = 7;

    /** Custom UUID format for application-specific implementations (RFC 9562) */
    case V8 = 8;

    /**
     * Check if this UUID version provides lexicographic sortability.
     *
     * Time-based versions embed timestamps in positions that enable lexicographic
     * ordering, improving database index performance and query efficiency. Random
     * and name-based versions sacrifice sortability for other properties like
     * unpredictability or deterministic generation.
     *
     * @return bool True for time-ordered versions (v1, v6, v7) that maintain chronological
     *              order when sorted as strings. False for random (v4), name-based (v3, v5),
     *              and custom (v8) versions. Version 7 is recommended for sortable database
     *              keys due to improved timestamp precision and layout optimization.
     */
    public function isSortable(): bool
    {
        return match ($this) {
            self::V1,
            self::V6,
            self::V7 => true,
            self::V3,
            self::V4,
            self::V5,
            self::V8 => false,
        };
    }

    /**
     * Get a human-readable description of this UUID version.
     *
     * Provides contextual information about the version's generation method,
     * characteristics, and recommended use cases. Useful for documentation,
     * logging, debugging, and user-facing configuration interfaces.
     *
     * @return string A concise description explaining the version's algorithm and purpose.
     *                Includes information about timestamp handling, randomness sources,
     *                hashing algorithms, or special characteristics. Highlights recommended
     *                versions (v7) and notes deprecated options (v3 due to MD5 weakness).
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::V1 => 'Time-based with MAC address',
            self::V3 => 'Name-based using MD5 hash',
            self::V4 => 'Random',
            self::V5 => 'Name-based using SHA-1 hash',
            self::V6 => 'Reordered time-based for database optimization',
            self::V7 => 'Unix Epoch time-based (recommended)',
            self::V8 => 'Custom implementation',
        };
    }
}
