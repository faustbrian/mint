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

use function mb_strlen;

/**
 * CUID2 (Collision-resistant Unique IDentifier v2) value object.
 *
 * Represents a secure, collision-resistant identifier optimized for
 * horizontal scaling and distributed systems. Unlike version 1, CUID2
 * uses SHA-3 (or SHA-256 fallback) hashing of multiple entropy sources
 * including timestamp, random salt, counter, and machine fingerprint.
 *
 * CUID2 identifiers are not timestamp-based in their string representation
 * (unlike ULID or Snowflake), prioritizing security and collision resistance
 * over sortability. The hash-based approach prevents information leakage
 * about generation time or sequence.
 *
 * Length is configurable (2-32 characters), with 24 being the recommended
 * default for production use, providing sufficient entropy while remaining
 * compact.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://github.com/paralleldrive/cuid2
 */
final class Cuid2 extends AbstractIdentifier
{
    /**
     * Get the timestamp component from this CUID2.
     *
     * CUID2 identifiers do not expose timestamp information in a parseable
     * format. While the timestamp is used as entropy during generation,
     * it's hashed and not recoverable from the final identifier.
     */
    #[Override()]
    public function getTimestamp(): ?int
    {
        return null;
    }

    /**
     * Check if this CUID2 is lexicographically sortable.
     *
     * CUID2 identifiers are not sortable by design. The hash-based generation
     * produces randomized output that cannot be sorted chronologically. This
     * is a security feature to prevent information leakage about generation
     * order or timing.
     *
     * @return bool Always returns false as CUID2s are not sortable
     */
    #[Override()]
    public function isSortable(): bool
    {
        return false;
    }

    /**
     * Get the length of this CUID2 string.
     *
     * Returns the actual character length, which depends on the configured
     * length parameter used during generation (2-32 characters, default 24).
     *
     * @return int The number of characters in the CUID2 string
     */
    public function getLength(): int
    {
        return mb_strlen($this->value);
    }
}
