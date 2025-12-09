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
 * Hashid value object for URL-safe encoded numeric identifiers.
 *
 * Encodes one or more integers into a short, URL-safe, non-sequential string
 * using the Hashids algorithm. Useful for obfuscating database IDs in URLs
 * while maintaining deterministic encoding/decoding. Supports both standard
 * numeric encoding and hexadecimal string encoding.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://hashids.org/
 */
final class Hashid extends AbstractIdentifier
{
    /**
     * Create a new Hashid instance.
     *
     * @param string                 $value   The encoded Hashid string representation, URL-safe
     *                                        and using a configurable alphabet for obfuscation
     * @param string                 $bytes   Binary representation of the identifier for storage
     *                                        and low-level operations requiring raw byte access
     * @param array<int, int|string> $numbers Array of original values that were encoded
     *                                        into this Hashid. Can contain one or more integers
     *                                        (or strings for values > PHP_INT_MAX), allowing
     *                                        multiple IDs to be encoded into a single string.
     * @param null|string            $hex     Original hexadecimal string if this Hashid was created
     *                                        via hex encoding rather than integer encoding. Used to
     *                                        preserve the original format for round-trip conversion.
     */
    public function __construct(
        string $value,
        string $bytes,
        private readonly array $numbers,
        private readonly ?string $hex = null,
    ) {
        parent::__construct($value, $bytes);
    }

    /**
     * Get the timestamp component.
     *
     * Hashids are not time-based and contain no temporal information,
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
     * Hashids are non-sequential and cannot be sorted chronologically
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
     * Returns the complete array of values that were encoded into this
     * Hashid, preserving the original order and values.
     *
     * @return array<int, int|string>
     */
    public function getNumbers(): array
    {
        return $this->numbers;
    }

    /**
     * Get the first encoded number.
     *
     * Convenience method for retrieving the primary value when encoding
     * a single value. Returns null if the Hashid was created from an
     * empty array.
     */
    public function getNumber(): int|string|null
    {
        return $this->numbers[0] ?? null;
    }

    /**
     * Get the original hexadecimal string.
     *
     * Returns the source hex string if this Hashid was created via hex
     * encoding, otherwise null for integer-encoded Hashids.
     */
    public function getHex(): ?string
    {
        return $this->hex;
    }

    /**
     * Check if this Hashid was created from a hexadecimal string.
     */
    public function isHexEncoded(): bool
    {
        return $this->hex !== null;
    }

    /**
     * Decode the Hashid back to its original numbers.
     *
     * Returns the array of values that were encoded into this Hashid.
     * This is an alias for getNumbers() to provide a more semantic API
     * that mirrors the encode/decode terminology.
     *
     * @return array<int, int|string>
     */
    public function decode(): array
    {
        return $this->numbers;
    }
}
