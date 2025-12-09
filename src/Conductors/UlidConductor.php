<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Conductors;

use Cline\Mint\Enums\IdentifierType;
use Cline\Mint\Exceptions\InvalidIdentifierException;
use Cline\Mint\Generators\UlidGenerator;
use Cline\Mint\MintManager;
use Cline\Mint\Support\Identifiers\Ulid;

/**
 * Fluent conductor for ULID generation and parsing.
 *
 * ULIDs (Universally Unique Lexicographically Sortable Identifiers) are 128-bit
 * identifiers that combine the uniqueness of UUIDs with lexicographic sortability
 * based on creation time. They consist of a 48-bit timestamp and 80 bits of randomness,
 * encoded as a 26-character case-insensitive string using Crockford's Base32 alphabet.
 *
 * ```php
 * // Generate a new ULID
 * $ulid = Mint::ulid()->generate();
 * echo $ulid->toString(); // "01ARZ3NDEKTSV4RRFFQ69G5FAV"
 *
 * // Parse an existing ULID string
 * $parsed = Mint::ulid()->parse('01ARZ3NDEKTSV4RRFFQ69G5FAV');
 * $timestamp = $parsed->getTimestamp();
 *
 * // Validate ULID format
 * if (Mint::ulid()->isValid($input)) {
 *     // Process valid ULID
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class UlidConductor
{
    /**
     * Create a new ULID conductor instance.
     *
     * @param MintManager $manager Central manager for accessing ULID generator instances
     *                             and coordinating identifier generation operations across
     *                             the Mint library. Provides dependency injection and
     *                             generator lifecycle management.
     */
    public function __construct(
        private MintManager $manager,
    ) {}

    /**
     * Generate a new ULID with current timestamp and random entropy.
     *
     * Creates a time-ordered, globally unique identifier suitable for database
     * primary keys, distributed systems, and scenarios requiring sortable IDs.
     *
     * @return Ulid The generated ULID instance containing timestamp and entropy components
     */
    public function generate(): Ulid
    {
        /** @var UlidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Ulid);

        return $generator->generate();
    }

    /**
     * Parse a ULID string representation into a structured identifier object.
     *
     * Validates and decodes a 26-character ULID string, extracting timestamp
     * and entropy components for programmatic access and manipulation.
     *
     * @param string $value The 26-character ULID string to parse using Crockford's
     *                      Base32 alphabet (case-insensitive). Must conform to ULID
     *                      specification format with valid timestamp and entropy sections.
     *
     * @throws InvalidIdentifierException If the string format is invalid,
     *                                    contains illegal characters, or
     *                                    represents an out-of-range value
     * @return Ulid                       The parsed ULID instance with accessible timestamp and entropy data
     */
    public function parse(string $value): Ulid
    {
        /** @var UlidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Ulid);

        return $generator->parse($value);
    }

    /**
     * Check if a string conforms to valid ULID format.
     *
     * Validates length, character set, and structural requirements without
     * fully parsing the identifier. Useful for input validation and filtering.
     *
     * @param string $value The string to validate against ULID format rules including
     *                      length (26 characters), character set (Crockford Base32), and
     *                      timestamp/entropy component structure requirements.
     *
     * @return bool True if the string is a valid ULID, false otherwise
     */
    public function isValid(string $value): bool
    {
        return $this->manager->getGenerator(IdentifierType::Ulid)->isValid($value);
    }
}
