<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Generators;

use Cline\Mint\Algorithms\Cuid2Algorithm;
use Cline\Mint\Contracts\GeneratorInterface;
use Cline\Mint\Support\Identifiers\Cuid2;
use Override;

/**
 * CUID2 (Collision-resistant Unique IDentifier v2) generator.
 *
 * Generates secure, collision-resistant identifiers using SHA-3 hashing
 * of multiple entropy sources including timestamp, random salt, monotonic
 * counter, and machine fingerprint. Designed for horizontal scaling across
 * distributed systems with minimal coordination requirements.
 *
 * CUID2 improvements over CUID v1:
 * - Configurable length (2-32 characters, default 24)
 * - SHA-3 hashing for improved security
 * - Better collision resistance through additional entropy
 * - No discernible patterns in generated IDs
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/paralleldrive/cuid2
 * @psalm-immutable
 */
final readonly class Cuid2Generator implements GeneratorInterface
{
    /**
     * Default identifier length in characters.
     *
     * 24 characters provides strong collision resistance while maintaining
     * reasonable length for most use cases.
     */
    private const int DEFAULT_LENGTH = 24;

    /**
     * The underlying CUID2 algorithm instance.
     */
    private Cuid2Algorithm $algorithm;

    /**
     * Create a new CUID2 generator instance.
     *
     * @param int $length The desired length of generated identifiers in characters.
     *                    Must be between 2 and 32 (inclusive). Longer lengths provide
     *                    better collision resistance but result in larger identifiers.
     *                    Defaults to 24 for balanced performance and collision safety.
     */
    public function __construct(
        int $length = self::DEFAULT_LENGTH,
    ) {
        $this->algorithm = new Cuid2Algorithm($length);
    }

    /**
     * Generate a new CUID2 identifier.
     *
     * Combines multiple entropy sources (timestamp, random salt, counter,
     * and fingerprint) through SHA-3 hashing to produce a collision-resistant
     * identifier. The first character is always a letter for compatibility
     * with systems requiring alphabetic prefixes.
     *
     * @return Cuid2 The generated CUID2 identifier object
     */
    #[Override()]
    public function generate(): Cuid2
    {
        $data = $this->algorithm->generate();

        return new Cuid2($data['value'], $data['bytes']);
    }

    /**
     * Parse and validate a CUID2 string.
     *
     * Validates the input string against CUID2 format requirements and
     * returns a Cuid2 object if valid. The string must start with a letter
     * and contain only base36 characters.
     *
     * @param string $value The CUID2 string to parse
     *
     * @return Cuid2 The parsed CUID2 identifier object
     */
    #[Override()]
    public function parse(string $value): Cuid2
    {
        $data = $this->algorithm->parse($value);

        return new Cuid2($data['value'], $data['bytes']);
    }

    /**
     * Validate whether a string conforms to CUID2 format.
     *
     * Checks three validation rules:
     * 1. Length must be between 2 and 32 characters
     * 2. First character must be a lowercase letter (a-z)
     * 3. Remaining characters must be base36 (0-9, a-z)
     *
     * @param string $value The string to validate
     *
     * @return bool True if the value is a valid CUID2 format, false otherwise
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        return $this->algorithm->isValid($value);
    }

    /**
     * Get the generator's identifying name.
     *
     * @return string The generator type identifier 'cuid2'
     */
    #[Override()]
    public function getName(): string
    {
        return 'cuid2';
    }

    /**
     * Get the configured identifier length.
     *
     * @return int The length in characters for generated identifiers
     */
    public function getLength(): int
    {
        return $this->algorithm->getLength();
    }
}
