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

/**
 * Sqid (Short Unique ID) value object for obfuscated numeric identifiers.
 *
 * The successor to Hashids, offering improved encoding with better randomization
 * and shorter output. Encodes one or more integers into a compact, URL-safe,
 * non-sequential string that obscures the original numeric values. Ideal for
 * public-facing URLs where you want to hide sequential database IDs while
 * maintaining deterministic encoding/decoding.
 *
 * Unlike Hashids, Sqids has improved alphabet handling and produces shorter
 * identifiers for the same input values.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/sqids/sqids-php
 */
final class Sqid extends AbstractIdentifier
{
    /**
     * Create a new Sqid instance.
     *
     * @param string     $value   Encoded Sqid string representation, URL-safe and
     *                            using a configurable alphabet for compact obfuscation
     * @param string     $bytes   Binary representation of the identifier for storage
     *                            and low-level operations requiring raw byte access
     * @param array<int> $numbers Array of original integer values that were encoded
     *                            into this Sqid. Can contain one or more integers,
     *                            enabling multiple database IDs to be packed into
     *                            a single compact identifier string.
     */
    public function __construct(
        string $value,
        string $bytes,
        private readonly array $numbers,
    ) {
        parent::__construct($value, $bytes);
    }

    /**
     * Get the timestamp component.
     *
     * Sqids are not time-based and contain no temporal information,
     * so this always returns null.
     */
    #[Override()]
    public function getTimestamp(): ?int
    {
        return null;
    }

    /**
     * Check if this identifier is sortable by creation time.
     *
     * Sqids are non-sequential and cannot be sorted chronologically
     * as they contain no timestamp component.
     */
    #[Override()]
    public function isSortable(): bool
    {
        return false;
    }

    /**
     * Get the original numbers that were encoded.
     *
     * Returns the complete array of integers that were encoded into this
     * Sqid, preserving the original order and values.
     *
     * @return array<int>
     */
    public function getNumbers(): array
    {
        return $this->numbers;
    }

    /**
     * Get the first encoded number.
     *
     * Convenience method for retrieving the primary number when encoding
     * a single value. Returns null if the Sqid was created from an
     * empty array.
     */
    public function getNumber(): ?int
    {
        return $this->numbers[0] ?? null;
    }

    /**
     * Decode the Sqid back to its original numbers.
     *
     * Returns the array of integers that were encoded into this Sqid.
     * This is an alias for getNumbers() to provide a more semantic API
     * that mirrors the encode/decode terminology.
     *
     * @return array<int>
     */
    public function decode(): array
    {
        return $this->numbers;
    }
}
