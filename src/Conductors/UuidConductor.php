<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Conductors;

use Cline\Mint\Enums\IdentifierType;
use Cline\Mint\Enums\UuidVersion;
use Cline\Mint\Exceptions\InvalidIdentifierException;
use Cline\Mint\Generators\UuidGenerator;
use Cline\Mint\MintManager;
use Cline\Mint\Support\Identifiers\Uuid;

/**
 * Fluent conductor for UUID generation and parsing.
 *
 * Provides a chainable API for generating Universally Unique Identifiers (UUIDs)
 * conforming to RFC 4122 and RFC 9562 specifications. Supports versions 1, 4, and 7,
 * with version 7 recommended for most use cases due to its time-ordered sortability
 * and improved database indexing performance.
 *
 * ```php
 * // Generate time-ordered UUID v7 (recommended for databases)
 * $uuid = Mint::uuid()->v7()->generate();
 * echo $uuid->toString(); // "018c5d6e-5f89-7a9b-9c1d-2e3f4a5b6c7d"
 *
 * // Generate random UUID v4 (maximum entropy)
 * $uuid = Mint::uuid()->v4()->generate();
 *
 * // Generate time-based UUID v1 (includes MAC address)
 * $uuid = Mint::uuid()->v1()->generate();
 *
 * // Parse existing UUID string
 * $parsed = Mint::uuid()->parse('018c5d6e-5f89-7a9b-9c1d-2e3f4a5b6c7d');
 * $timestamp = $parsed->getTimestamp(); // v1, v6, v7 only
 *
 * // Special UUIDs for edge cases
 * $nil = Mint::uuid()->nil(); // 00000000-0000-0000-0000-000000000000
 * $max = Mint::uuid()->max(); // ffffffff-ffff-ffff-ffff-ffffffffffff
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class UuidConductor
{
    /**
     * Create a new UUID conductor instance.
     *
     * @param MintManager $manager Central manager for accessing UUID generator instances
     *                             and coordinating identifier generation operations. Handles
     *                             generator lifecycle and configuration management.
     * @param UuidVersion $version The UUID version to generate (1, 4, or 7). Defaults to v7
     *                             (Unix Epoch time-based) which provides optimal database
     *                             performance through natural sorting and reduced index
     *                             fragmentation compared to random UUIDs.
     */
    public function __construct(
        private MintManager $manager,
        private UuidVersion $version = UuidVersion::V7,
    ) {}

    /**
     * Configure conductor to generate UUID version 1.
     *
     * Version 1 UUIDs combine timestamp with MAC address, providing temporal
     * ordering but potentially exposing hardware information. Suitable for
     * scenarios requiring timestamp extraction and hardware traceability.
     *
     * @return self New immutable conductor instance configured for UUID v1
     */
    public function v1(): self
    {
        return new self($this->manager, UuidVersion::V1);
    }

    /**
     * Configure conductor to generate UUID version 4.
     *
     * Version 4 UUIDs are purely random, offering maximum entropy and no
     * information leakage. Recommended when sortability is not required
     * and privacy is paramount.
     *
     * @return self New immutable conductor instance configured for UUID v4
     */
    public function v4(): self
    {
        return new self($this->manager, UuidVersion::V4);
    }

    /**
     * Configure conductor to generate UUID version 7.
     *
     * Version 7 UUIDs use Unix Epoch timestamps with random entropy, providing
     * chronological sortability ideal for database primary keys. Reduces index
     * fragmentation and improves query performance compared to random UUIDs.
     *
     * @return self New immutable conductor instance configured for UUID v7
     */
    public function v7(): self
    {
        return new self($this->manager, UuidVersion::V7);
    }

    /**
     * Generate a new UUID using the configured version.
     *
     * Creates a 128-bit identifier following RFC 4122/9562 specifications,
     * formatted as a 36-character hyphenated string in 8-4-4-4-12 format.
     *
     * @return Uuid The generated UUID instance with version-specific properties
     */
    public function generate(): Uuid
    {
        /** @var UuidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Uuid, [
            'version' => $this->version,
        ]);

        return $generator->generate();
    }

    /**
     * Parse a UUID string into a structured identifier object.
     *
     * Validates format and extracts version information automatically. Accepts
     * standard hyphenated format (8-4-4-4-12) and performs structural validation.
     *
     * @param string $value The UUID string to parse in standard 8-4-4-4-12 hyphenated
     *                      format (e.g., "550e8400-e29b-41d4-a716-446655440000").
     *                      Case-insensitive, must be exactly 36 characters including
     *                      hyphens at positions 8, 13, 18, and 23.
     *
     * @throws InvalidIdentifierException If the string format is invalid,
     *                                    contains illegal characters, or
     *                                    does not conform to UUID structure
     * @return Uuid                       The parsed UUID instance with accessible version and variant fields
     */
    public function parse(string $value): Uuid
    {
        /** @var UuidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Uuid);

        return $generator->parse($value);
    }

    /**
     * Check if a string conforms to valid UUID format.
     *
     * Validates RFC 4122 structure including length, hyphen placement, and
     * hexadecimal character set without fully parsing the identifier.
     *
     * @param string $value The string to validate against UUID format requirements including
     *                      36-character length, hyphen positions (8, 13, 18, 23), and valid
     *                      hexadecimal characters (0-9, a-f, A-F) in all segments.
     *
     * @return bool True if the string is a valid UUID format, false otherwise
     */
    public function isValid(string $value): bool
    {
        return $this->manager->getGenerator(IdentifierType::Uuid)->isValid($value);
    }

    /**
     * Generate a nil UUID representing the absence of a value.
     *
     * Creates the special "nil" UUID (all 128 bits set to zero) defined in RFC 4122.
     * Useful for representing null/empty UUID values in databases or protocols that
     * require explicit UUID placeholders rather than nullable fields.
     *
     * @return Uuid The nil UUID (00000000-0000-0000-0000-000000000000)
     */
    public function nil(): Uuid
    {
        /** @var UuidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Uuid);

        return $generator->nil();
    }

    /**
     * Generate a max UUID representing the maximum possible value.
     *
     * Creates the special "max" UUID (all 128 bits set to one) defined in RFC 4122.
     * Useful for range queries, sentinel values, or representing infinity in
     * UUID-based ordered data structures.
     *
     * @return Uuid The max UUID (ffffffff-ffff-ffff-ffff-ffffffffffff)
     */
    public function max(): Uuid
    {
        /** @var UuidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Uuid);

        return $generator->max();
    }
}
