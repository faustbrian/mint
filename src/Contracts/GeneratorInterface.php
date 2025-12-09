<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Contracts;

use Cline\Mint\Exceptions\InvalidIdentifierException;

/**
 * Interface for identifier generators.
 *
 * Defines the contract for all identifier generation implementations in the Mint library.
 * Each generator produces a specific type of unique identifier (UUID, ULID, Snowflake, etc.)
 * with consistent generation, parsing, and validation operations. Generators are stateless
 * and can be safely reused across multiple identifier creation operations.
 *
 * Implementations should ensure:
 * - Thread-safe generation (when applicable)
 * - Deterministic parsing for valid inputs
 * - Fast validation without full parsing overhead
 * - Consistent error handling through InvalidIdentifierException
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface GeneratorInterface
{
    /**
     * Generate a new unique identifier instance.
     *
     * Creates a fresh identifier using the generator's specific algorithm and configuration.
     * Depending on the identifier type, may incorporate timestamp, random entropy, machine
     * context, or other components to ensure global uniqueness and desired characteristics.
     *
     * @return IdentifierInterface The newly generated identifier instance implementing the
     *                             standard interface. The concrete type depends on the
     *                             generator (Ulid, Uuid, Snowflake, etc.). Generated
     *                             identifiers are immutable value objects.
     */
    public function generate(): IdentifierInterface;

    /**
     * Parse a string representation into a structured identifier object.
     *
     * Validates the input format and constructs an identifier instance from its string
     * representation. Parsing extracts embedded components (timestamp, entropy, etc.)
     * for programmatic access while verifying structural integrity.
     *
     * @param string $value The string representation to parse. Format and length requirements
     *                      are specific to each identifier type (e.g., 36 chars for UUID,
     *                      26 chars for ULID). Case sensitivity varies by implementation.
     *                      Must conform to the identifier's specification format.
     *
     * @throws InvalidIdentifierException If the string format is invalid, contains illegal
     *                                    characters, has incorrect length, or represents an
     *                                    out-of-range value for this identifier type. Exception
     *                                    message provides details about the validation failure.
     * @return IdentifierInterface        The parsed identifier instance with extracted components
     *                                    accessible through the interface methods. Returns the same
     *                                    concrete type as generate().
     */
    public function parse(string $value): IdentifierInterface;

    /**
     * Check if a string is a valid representation of this identifier type.
     *
     * Performs format validation without the overhead of full parsing and object construction.
     * Useful for input filtering, validation middleware, and pre-flight checks before
     * database operations. Does not throw exceptions for invalid inputs.
     *
     * @param string $value The string to validate against this identifier type's format rules.
     *                      Checks include length requirements, character set constraints, and
     *                      structural patterns specific to the identifier specification.
     *
     * @return bool True if the string conforms to valid format, false otherwise. A true result
     *              guarantees that parse() will succeed without throwing InvalidIdentifierException,
     *              though the identifier's semantic validity (e.g., future timestamp) is not guaranteed.
     */
    public function isValid(string $value): bool;

    /**
     * Get the canonical name of this generator type.
     *
     * Returns a lowercase identifier name matching the IdentifierType enum value
     * (e.g., "uuid", "ulid", "snowflake"). Used for generator registry lookups,
     * logging, debugging, and configuration mapping.
     *
     * @return string The generator's type name in lowercase. Matches the corresponding
     *                IdentifierType enum case for consistent type identification across
     *                the library (e.g., "uuid", "ulid", "xid", "nanoid").
     */
    public function getName(): string;
}
