<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Conductors;

use Cline\Mint\Enums\IdentifierType;
use Cline\Mint\Generators\SqidGenerator;
use Cline\Mint\MintManager;
use Cline\Mint\Support\Identifiers\Sqid;

/**
 * Fluent conductor for Sqid encoding and decoding.
 *
 * Sqids (formerly Hashids v2) encode integers into short, URL-safe strings.
 * They are reversible, allowing original numbers to be decoded.
 *
 * ```php
 * $sqid = Mint::sqid()->encode([1, 2, 3]);
 * $sqid = Mint::sqid()->minLength(10)->encode([42]);
 * $decoded = Mint::sqid()->decode($string);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class SqidConductor
{
    /**
     * Create a new Sqid conductor instance.
     *
     * @param MintManager        $manager   Central manager instance that coordinates identifier
     *                                      generation across the Mint library. Handles generator
     *                                      instantiation, configuration management, and provides
     *                                      access to the underlying generator implementations.
     * @param null|string        $alphabet  Custom character set to use for encoding. Must contain
     *                                      at least 3 unique characters. When null, uses the default
     *                                      Sqids alphabet. Allows customization of output appearance
     *                                      and compatibility with specific character constraints.
     * @param null|int           $minLength Minimum length for generated Sqid strings. Shorter encodings
     *                                      are padded to reach this length. Useful for maintaining
     *                                      consistent ID lengths across your application. When null,
     *                                      no minimum length is enforced.
     * @param null|array<string> $blocklist Array of lowercase words to avoid in generated Sqids. The
     *                                      encoder automatically rearranges the alphabet if a generated
     *                                      ID would contain a blocklisted word. Helps prevent accidental
     *                                      generation of profanity or other undesirable patterns. When
     *                                      null, uses the default blocklist.
     */
    public function __construct(
        private MintManager $manager,
        private ?string $alphabet = null,
        private ?int $minLength = null,
        private ?array $blocklist = null,
    ) {}

    /**
     * Set a custom alphabet for encoding.
     *
     * Returns a new conductor instance with the specified alphabet. The alphabet
     * must contain at least 3 unique characters. Allows control over which
     * characters appear in the encoded output.
     *
     * @param  string $alphabet Custom character set (minimum 3 unique characters)
     * @return self   New conductor instance with updated alphabet
     */
    public function alphabet(string $alphabet): self
    {
        return new self($this->manager, $alphabet, $this->minLength, $this->blocklist);
    }

    /**
     * Set the minimum length of generated Sqids.
     *
     * Returns a new conductor instance with the specified minimum length.
     * Sqids shorter than this length are padded to reach the minimum.
     * Useful for maintaining consistent ID formatting.
     *
     * @param  int  $minLength Minimum length for generated Sqids
     * @return self New conductor instance with updated minimum length
     */
    public function minLength(int $minLength): self
    {
        return new self($this->manager, $this->alphabet, $minLength, $this->blocklist);
    }

    /**
     * Set words to avoid in generated Sqids.
     *
     * Returns a new conductor instance with the specified blocklist. When a
     * generated ID would contain a blocklisted word, the encoder rearranges
     * the alphabet to produce a different output. Prevents generation of
     * offensive or undesirable patterns.
     *
     * @param  array<string> $blocklist Array of lowercase words to block
     * @return self          New conductor instance with updated blocklist
     */
    public function blocklist(array $blocklist): self
    {
        return new self($this->manager, $this->alphabet, $this->minLength, $blocklist);
    }

    /**
     * Generate a new Sqid (uses timestamp + counter internally).
     *
     * Creates a Sqid by encoding the current timestamp and an internal counter.
     * This provides a convenient way to generate unique Sqids without manually
     * managing the numeric input.
     *
     * @return Sqid New Sqid identifier object
     */
    public function generate(): Sqid
    {
        /** @var SqidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Sqid, $this->buildConfig());

        return $generator->generate();
    }

    /**
     * Encode an array of numbers into a Sqid.
     *
     * Encodes multiple non-negative integers into a single short, URL-safe
     * string. The successor to Hashids with improved algorithm and blocklist
     * support. The encoding is deterministic and reversible.
     *
     * @param  array<int> $numbers Array of non-negative integers to encode
     * @return Sqid       Encoded Sqid identifier object
     */
    public function encode(array $numbers): Sqid
    {
        /** @var SqidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Sqid, $this->buildConfig());

        return $generator->encode($numbers);
    }

    /**
     * Encode a single number into a Sqid.
     *
     * Convenience method for encoding a single integer. Equivalent to calling
     * encode() with a single-element array.
     *
     * @param  int  $number Non-negative integer to encode
     * @return Sqid Encoded Sqid identifier object
     */
    public function encodeNumber(int $number): Sqid
    {
        /** @var SqidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Sqid, $this->buildConfig());

        return $generator->encodeNumber($number);
    }

    /**
     * Decode a Sqid string back to its original numbers.
     *
     * Reverses the encoding process to recover the original array of integers.
     * Returns an empty array if the Sqid is invalid or was encoded with
     * different configuration parameters.
     *
     * @param  string     $value Sqid string to decode
     * @return array<int> Array of decoded integers
     */
    public function decode(string $value): array
    {
        /** @var SqidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Sqid, $this->buildConfig());

        return $generator->decode($value);
    }

    /**
     * Parse a Sqid string into a Sqid object.
     *
     * Converts a Sqid string representation into a Sqid object for
     * inspection and manipulation. Does not decode the underlying numbers.
     *
     * @param  string $value Sqid string to parse
     * @return Sqid   Parsed Sqid identifier object
     */
    public function parse(string $value): Sqid
    {
        /** @var SqidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Sqid, $this->buildConfig());

        return $generator->parse($value);
    }

    /**
     * Check if a string is a valid Sqid.
     *
     * Validates whether a given string conforms to the Sqid format based
     * on the current alphabet configuration. Does not verify if it can be
     * successfully decoded.
     *
     * @param  string $value String to validate
     * @return bool   True if the string is a valid Sqid format, false otherwise
     */
    public function isValid(string $value): bool
    {
        return $this->manager->getGenerator(IdentifierType::Sqid, $this->buildConfig())->isValid($value);
    }

    /**
     * Build configuration array for generator.
     *
     * Assembles the current conductor configuration into an array format
     * expected by the SqidGenerator. Only includes non-null configuration
     * values to allow generator defaults to apply.
     *
     * @return array<string, mixed> Configuration array with alphabet, min_length, and blocklist
     */
    private function buildConfig(): array
    {
        $config = [];

        if ($this->alphabet !== null) {
            $config['alphabet'] = $this->alphabet;
        }

        if ($this->minLength !== null) {
            $config['min_length'] = $this->minLength;
        }

        if ($this->blocklist !== null) {
            $config['blocklist'] = $this->blocklist;
        }

        return $config;
    }
}
