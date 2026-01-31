<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Generators;

use Cline\Mint\Algorithms\NanoIdAlgorithm;
use Cline\Mint\Contracts\GeneratorInterface;
use Cline\Mint\Support\Identifiers\NanoId;
use Override;

/**
 * NanoID generator.
 *
 * Generates compact, URL-friendly unique identifiers with configurable length
 * and alphabet. NanoIDs provide similar security to UUIDs but with shorter,
 * more human-readable strings. The default configuration produces 21-character
 * IDs with URL-safe characters (_-A-Za-z0-9), offering collision resistance
 * comparable to UUID v4 with ~10^41 possible values.
 *
 * The generator uses cryptographically secure random bytes and applies bitwise
 * masking for uniform distribution across the alphabet. Multiple predefined
 * alphabets are available for specialized use cases.
 *
 * ```php
 * $generator = new NanoIdGenerator();
 * $nanoid = $generator->generate();
 * echo $nanoid->toString(); // e.g., "V1StGXR8_Z5jdHi6B-myT"
 *
 * // Custom length and alphabet
 * $short = NanoIdGenerator::numeric(10);
 * echo $short->generate()->toString(); // e.g., "4817364921"
 * ```
 *
 * @psalm-immutable
 * @api
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/ai/nanoid
 */
final readonly class NanoIdGenerator implements GeneratorInterface
{
    /**
     * Default alphabet (URL-safe).
     *
     * Contains 64 characters: underscore, hyphen, digits 0-9, lowercase a-z,
     * and uppercase A-Z. This alphabet is optimized for URL safety and avoids
     * ambiguous characters.
     */
    public const string DEFAULT_ALPHABET = NanoIdAlgorithm::DEFAULT_ALPHABET;

    /**
     * Default ID length.
     *
     * 21 characters provides approximately the same collision probability as
     * UUID v4 (126 bits of randomness) while being significantly shorter.
     */
    public const int DEFAULT_LENGTH = NanoIdAlgorithm::DEFAULT_LENGTH;

    /**
     * The underlying NanoID algorithm.
     */
    private NanoIdAlgorithm $algorithm;

    /**
     * Create a new NanoID generator.
     *
     * The alphabet must contain at least 2 unique characters. Longer alphabets
     * provide better compression and shorter IDs for equivalent security levels.
     *
     * @param int    $length   Length of generated IDs in characters. Higher values increase
     *                         collision resistance exponentially. Common values: 10-32 characters.
     * @param string $alphabet Character set for ID generation. Must contain at least 2 unique
     *                         characters. Use the predefined constants or factory methods for
     *                         common alphabets, or provide a custom alphabet for specialized needs.
     */
    public function __construct(
        int $length = self::DEFAULT_LENGTH,
        string $alphabet = self::DEFAULT_ALPHABET,
    ) {
        $this->algorithm = new NanoIdAlgorithm($length, $alphabet);
    }

    /**
     * Create a generator with a custom alphabet.
     *
     * Factory method for creating generators with custom character sets. Useful
     * for domain-specific requirements like avoiding profanity or similar-looking
     * characters.
     *
     * @param string $alphabet Custom character set (minimum 2 unique characters)
     * @param int    $length   Desired ID length in characters
     *
     * @return self Configured NanoID generator instance
     */
    public static function withAlphabet(string $alphabet, int $length = self::DEFAULT_LENGTH): self
    {
        return new self($length, $alphabet);
    }

    /**
     * Create a generator for lowercase alphanumeric IDs.
     *
     * Produces IDs using only lowercase letters and digits (36 characters).
     * Useful for case-insensitive systems or when visual simplicity is preferred.
     *
     * @param int $length Desired ID length in characters
     *
     * @return self Configured NanoID generator instance
     */
    public static function alphanumeric(int $length = self::DEFAULT_LENGTH): self
    {
        return new self($length, '0123456789abcdefghijklmnopqrstuvwxyz');
    }

    /**
     * Create a generator for numeric-only IDs.
     *
     * Produces IDs using only digits 0-9. Useful for PIN-like identifiers or
     * systems requiring purely numeric values. Note that numeric IDs require
     * longer lengths for equivalent security.
     *
     * @param int $length Desired ID length in characters
     *
     * @return self Configured NanoID generator instance
     */
    public static function numeric(int $length = self::DEFAULT_LENGTH): self
    {
        return new self($length, '0123456789');
    }

    /**
     * Create a generator for hex IDs.
     *
     * Produces lowercase hexadecimal IDs (0-9, a-f). Compatible with hexadecimal
     * parsing and useful for compact representation of binary data.
     *
     * @param int $length Desired ID length in characters
     *
     * @return self Configured NanoID generator instance
     */
    public static function hex(int $length = self::DEFAULT_LENGTH): self
    {
        return new self($length, '0123456789abcdef');
    }

    /**
     * Generate a new NanoID.
     *
     * Uses cryptographically secure random bytes with bitwise masking to ensure
     * uniform distribution across the alphabet. The algorithm generates random
     * bytes in batches for efficiency while maintaining cryptographic security.
     *
     * @return NanoId The generated NanoID instance
     */
    #[Override()]
    public function generate(): NanoId
    {
        $data = $this->algorithm->generate();

        return new NanoId($data['value'], $data['bytes']);
    }

    /**
     * Parse a NanoID string.
     *
     * Validates that all characters belong to the configured alphabet. Unlike
     * some ID formats, NanoIDs cannot be decoded to extract metadata since they
     * are purely random.
     *
     * @param string $value The NanoID string to parse
     *
     * @return NanoId The parsed NanoID instance
     */
    #[Override()]
    public function parse(string $value): NanoId
    {
        $data = $this->algorithm->parse($value);

        return new NanoId($data['value'], $data['bytes']);
    }

    /**
     * Check if a string is a valid NanoID.
     *
     * Validates that the string is non-empty and contains only characters from
     * the configured alphabet. Note that this does not enforce a specific length,
     * allowing validation of NanoIDs generated with different length configurations.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string contains only valid alphabet characters
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        return $this->algorithm->isValid($value);
    }

    /**
     * Get the generator name.
     *
     * @return string The identifier 'nanoid'
     */
    #[Override()]
    public function getName(): string
    {
        return 'nanoid';
    }

    /**
     * Get the configured length.
     *
     * Returns the number of characters that will be generated for each ID.
     *
     * @return int The ID length in characters
     */
    public function getLength(): int
    {
        return $this->algorithm->getLength();
    }

    /**
     * Get the configured alphabet.
     *
     * Returns the character set used for ID generation.
     *
     * @return string The alphabet string
     */
    public function getAlphabet(): string
    {
        return $this->algorithm->getAlphabet();
    }
}
