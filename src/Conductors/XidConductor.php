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
use Cline\Mint\Generators\XidGenerator;
use Cline\Mint\MintManager;
use Cline\Mint\Support\Identifiers\Xid;

/**
 * Fluent conductor for XID generation and parsing.
 *
 * XIDs are globally unique, sortable identifiers inspired by MongoDB ObjectIDs,
 * designed for distributed systems. Each XID is a 96-bit (12-byte) value combining
 * a 4-byte timestamp, 3-byte machine identifier, 2-byte process ID, and 3-byte counter,
 * encoded as a 20-character base32 string for URL-safe, compact representation.
 *
 * ```php
 * // Generate a new XID
 * $xid = Mint::xid()->generate();
 * echo $xid->toString(); // "9m4e2mr0ui3e8a215n4g"
 *
 * // Parse existing XID string
 * $parsed = Mint::xid()->parse('9m4e2mr0ui3e8a215n4g');
 * $timestamp = $parsed->getTimestamp();
 *
 * // Validate XID format
 * if (Mint::xid()->isValid($input)) {
 *     // Process valid XID
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class XidConductor
{
    /**
     * Create a new XID conductor instance.
     *
     * @param MintManager $manager Central manager for accessing XID generator instances
     *                             and coordinating identifier generation operations across
     *                             distributed systems. Manages generator lifecycle and
     *                             ensures uniqueness across processes and machines.
     */
    public function __construct(
        private MintManager $manager,
    ) {}

    /**
     * Generate a new XID with current timestamp and machine context.
     *
     * Creates a globally unique, sortable identifier suitable for distributed
     * systems, database sharding, and scenarios requiring compact, time-ordered
     * IDs with embedded machine and process context.
     *
     * @return Xid The generated XID instance containing timestamp, machine ID, process ID,
     *             and counter components encoded in a 20-character base32 string
     */
    public function generate(): Xid
    {
        /** @var XidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Xid);

        return $generator->generate();
    }

    /**
     * Parse an XID string representation into a structured identifier object.
     *
     * Validates and decodes a 20-character base32 XID string, extracting timestamp,
     * machine identifier, process ID, and counter components for programmatic access.
     *
     * @param string $value The 20-character XID string to parse using base32 encoding
     *                      (case-insensitive). Must conform to XID specification format
     *                      with valid timestamp, machine ID, process ID, and counter sections.
     *
     * @throws InvalidIdentifierException If the string format is invalid,
     *                                    contains illegal base32 characters,
     *                                    has incorrect length, or represents
     *                                    an out-of-range value
     * @return Xid                        The parsed XID instance with accessible component data including
     *                                    creation timestamp and machine/process context information
     */
    public function parse(string $value): Xid
    {
        /** @var XidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Xid);

        return $generator->parse($value);
    }

    /**
     * Check if a string conforms to valid XID format.
     *
     * Validates length, character set, and structural requirements without fully
     * parsing the identifier. Useful for input validation, filtering, and
     * pre-processing before database operations.
     *
     * @param string $value The string to validate against XID format rules including
     *                      exact length (20 characters) and valid base32 character set
     *                      (0-9, a-v in lowercase or uppercase). Structural validation
     *                      ensures proper timestamp, machine, process, and counter encoding.
     *
     * @return bool True if the string is a valid XID format, false otherwise
     */
    public function isValid(string $value): bool
    {
        return $this->manager->getGenerator(IdentifierType::Xid)->isValid($value);
    }
}
