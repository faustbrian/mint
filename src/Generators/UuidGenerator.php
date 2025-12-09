<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Generators;

use Cline\Mint\Algorithms\UuidAlgorithm;
use Cline\Mint\Contracts\GeneratorInterface;
use Cline\Mint\Enums\UuidVersion;
use Cline\Mint\Exceptions\InvalidUuidFormatException;
use Cline\Mint\Support\Identifiers\Uuid;
use Override;

use function str_repeat;

/**
 * UUID (Universally Unique Identifier) generator.
 *
 * Supports UUID versions 1, 3, 4, 5, 6, 7, and 8.
 * Default version is v7 (recommended for modern applications).
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class UuidGenerator implements GeneratorInterface
{
    /**
     * DNS namespace UUID.
     */
    public const string NAMESPACE_DNS = UuidAlgorithm::NAMESPACE_DNS;

    /**
     * URL namespace UUID.
     */
    public const string NAMESPACE_URL = UuidAlgorithm::NAMESPACE_URL;

    /**
     * OID namespace UUID.
     */
    public const string NAMESPACE_OID = UuidAlgorithm::NAMESPACE_OID;

    /**
     * X.500 DN namespace UUID.
     */
    public const string NAMESPACE_X500 = UuidAlgorithm::NAMESPACE_X500;

    /**
     * The underlying UUID algorithm.
     */
    private UuidAlgorithm $algorithm;

    /**
     * Create a new UUID generator instance.
     *
     * @param UuidVersion $version   The UUID version to generate. Defaults to v7 (recommended
     *                               for modern applications with millisecond-precision timestamps
     *                               and lexicographic sorting capabilities). Version 4 provides
     *                               maximum randomness, while versions 3 and 5 generate
     *                               deterministic name-based UUIDs.
     * @param null|string $namespace Namespace UUID for v3/v5 generation. Must be a valid UUID
     *                               string (predefined constants available: NAMESPACE_DNS,
     *                               NAMESPACE_URL, NAMESPACE_OID, NAMESPACE_X500). Only used
     *                               when generating v3 or v5 UUIDs. Ignored for other versions.
     * @param null|string $name      Name value to hash with namespace for v3/v5 generation.
     *                               Combined with namespace UUID to produce deterministic output.
     *                               Only used when generating v3 or v5 UUIDs. Ignored for other
     *                               versions.
     */
    public function __construct(
        UuidVersion $version = UuidVersion::V7,
        ?string $namespace = null,
        ?string $name = null,
    ) {
        $this->algorithm = new UuidAlgorithm($version, $namespace, $name);
    }

    /**
     * Generate a new UUID based on the configured version.
     *
     * Delegates to the underlying algorithm for generation, then wraps the
     * result in a UUID value object. The output format follows RFC 4122
     * specification with 8-4-4-4-12 hexadecimal structure.
     *
     * @return Uuid A new UUID value object containing the string representation
     *              and binary bytes
     */
    #[Override()]
    public function generate(): Uuid
    {
        $data = $this->algorithm->generate();

        return new Uuid($data['value'], $data['bytes'], $this->algorithm->getVersion());
    }

    /**
     * Parse a UUID string into a Uuid value object.
     *
     * Converts a UUID string representation into a structured value object
     * containing both string and binary formats. Automatically detects the
     * UUID version from the version nibble in the string.
     *
     * @param string $value The UUID string to parse in standard 8-4-4-4-12
     *                      hyphenated format (case-insensitive)
     *
     * @throws InvalidUuidFormatException When the string doesn't match UUID format
     * @return Uuid                       The parsed UUID value object with detected version
     */
    #[Override()]
    public function parse(string $value): Uuid
    {
        $data = $this->algorithm->parse($value);
        $version = $this->algorithm->detectVersion($value);

        return new Uuid($data['value'], $data['bytes'], $version);
    }

    /**
     * Validate whether a string matches UUID format.
     *
     * Checks for RFC 4122 compliance using the standard 8-4-4-4-12 hexadecimal
     * pattern with hyphens. Accepts both uppercase and lowercase.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid UUID format, false otherwise
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        return $this->algorithm->isValid($value);
    }

    /**
     * Get the generator identifier name.
     *
     * @return string The string 'uuid' identifying this generator type
     */
    #[Override()]
    public function getName(): string
    {
        return 'uuid';
    }

    /**
     * Generate a nil UUID representing the zero value.
     *
     * Returns the special nil UUID (00000000-0000-0000-0000-000000000000)
     * as defined in RFC 4122. Useful as a null placeholder or default value.
     *
     * @return Uuid The nil UUID value object with all bits set to zero
     */
    public function nil(): Uuid
    {
        return new Uuid(
            '00000000-0000-0000-0000-000000000000',
            str_repeat("\x00", 16),
            UuidVersion::V4,
        );
    }

    /**
     * Generate a max UUID representing the maximum value.
     *
     * Returns the special max UUID (ffffffff-ffff-ffff-ffff-ffffffffffff)
     * with all bits set to one. Useful for range comparisons and boundaries.
     *
     * @return Uuid The max UUID value object with all bits set to one
     */
    public function max(): Uuid
    {
        return new Uuid(
            'ffffffff-ffff-ffff-ffff-ffffffffffff',
            str_repeat("\xff", 16),
            UuidVersion::V4,
        );
    }
}
