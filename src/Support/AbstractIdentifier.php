<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Support;

use Cline\Mint\Contracts\IdentifierInterface;
use Override;
use Stringable;

/**
 * Abstract base class for all identifier value objects.
 *
 * Provides common functionality for identifier implementations including
 * string conversion, binary representation access, and equality comparison.
 * All concrete identifier classes should extend this base class to inherit
 * standardized behavior and ensure consistent API across identifier types.
 *
 * Identifiers are immutable value objects - once created, their values
 * cannot be changed. This ensures thread safety and prevents accidental
 * modification.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class AbstractIdentifier implements IdentifierInterface, Stringable
{
    /**
     * Create a new identifier value object.
     *
     * @param string $value The human-readable string representation of the identifier,
     *                      typically in an encoded format like base32, base62, UUID, etc.
     *                      This is the primary representation used for display, logging,
     *                      and database storage.
     * @param string $bytes The binary representation of the identifier containing the raw
     *                      bytes before encoding. Used for timestamp extraction, binary
     *                      operations, and efficient storage/comparison when needed.
     */
    public function __construct(
        protected readonly string $value,
        protected readonly string $bytes,
    ) {}

    /**
     * Get the string representation when cast to string.
     *
     * Enables automatic string conversion when using the identifier in
     * string contexts like echo, concatenation, or string interpolation.
     *
     * @return string The encoded string representation
     */
    #[Override()]
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Get the string representation of the identifier.
     *
     * Returns the encoded string value suitable for display, logging,
     * API responses, and database storage.
     *
     * @return string The encoded string representation
     */
    #[Override()]
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Get the binary representation of the identifier.
     *
     * Returns the raw bytes before any encoding was applied. Useful for
     * extracting timestamp components, performing binary operations, or
     * when more compact storage is needed.
     *
     * @return string The raw binary bytes
     */
    #[Override()]
    public function toBytes(): string
    {
        return $this->bytes;
    }

    /**
     * Check if this identifier equals another identifier.
     *
     * Performs string-based equality comparison. Two identifiers are
     * considered equal if their string representations match exactly.
     * Type safety is not enforced - a UUID could technically equal a
     * ULID if their string values match (though this is unlikely).
     *
     * @param IdentifierInterface $other The identifier to compare against
     *
     * @return bool True if the identifiers have identical string values
     */
    #[Override()]
    public function equals(IdentifierInterface $other): bool
    {
        return $this->value === $other->toString();
    }

    /**
     * Get data for JSON serialization.
     *
     * Returns a structured array for JSON encoding. When the identifier
     * is passed to json_encode(), it will be serialized as an object with
     * a 'value' property containing the string representation.
     *
     * @return array{value: string} Array with 'value' key for JSON encoding
     */
    public function jsonSerialize(): array
    {
        return ['value' => $this->value];
    }
}
