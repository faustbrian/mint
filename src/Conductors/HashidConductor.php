<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Conductors;

use Cline\Mint\Enums\IdentifierType;
use Cline\Mint\Generators\HashidGenerator;
use Cline\Mint\MintManager;
use Cline\Mint\Support\Identifiers\Hashid;

/**
 * Fluent conductor for Hashid encoding and decoding.
 *
 * Hashids encode integers into short, URL-safe strings using a salt
 * for uniqueness. They are reversible, allowing original numbers to be decoded.
 *
 * ```php
 * $hashid = Mint::hashid()->salt('my-salt')->encode([1, 2, 3]);
 * $hashid = Mint::hashid()->minLength(10)->encode([42]);
 * $decoded = Mint::hashid()->decode($string);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class HashidConductor
{
    /**
     * Create a new Hashid conductor instance.
     *
     * @param MintManager $manager   Central manager instance that coordinates identifier
     *                               generation across the Mint library. Handles generator
     *                               instantiation, configuration management, and provides
     *                               access to the underlying generator implementations.
     * @param null|string $salt      Optional salt string that makes the encoded output unique
     *                               to your application. Different salts produce different
     *                               encodings for the same numbers, preventing decoding by
     *                               parties who don't know your salt.
     * @param null|int    $minLength Minimum length for generated Hashid strings. Shorter
     *                               encodings are padded to reach this length. Useful for
     *                               maintaining consistent ID lengths across your application.
     * @param null|string $alphabet  Custom character set to use for encoding. Must contain
     *                               at least 16 unique characters. Allows customization of
     *                               the output character set to meet specific requirements
     *                               or constraints (e.g., avoiding ambiguous characters).
     */
    public function __construct(
        private MintManager $manager,
        private ?string $salt = null,
        private ?int $minLength = null,
        private ?string $alphabet = null,
    ) {}

    /**
     * Set a salt for encoding (makes output unique to your application).
     *
     * Returns a new conductor instance with the specified salt. The salt acts
     * as a secret key that makes your Hashids unique and prevents unauthorized
     * decoding. Different salts produce completely different encodings.
     *
     * @param  string $salt Salt string to use for encoding
     * @return self   New conductor instance with updated salt configuration
     */
    public function salt(string $salt): self
    {
        return new self($this->manager, $salt, $this->minLength, $this->alphabet);
    }

    /**
     * Set the minimum length of generated Hashids.
     *
     * Returns a new conductor instance with the specified minimum length.
     * Hashids shorter than this length are padded to reach the minimum.
     * Useful for maintaining consistent ID formatting.
     *
     * @param  int  $minLength Minimum length for generated Hashids
     * @return self New conductor instance with updated minimum length
     */
    public function minLength(int $minLength): self
    {
        return new self($this->manager, $this->salt, $minLength, $this->alphabet);
    }

    /**
     * Set a custom alphabet for encoding.
     *
     * Returns a new conductor instance with the specified alphabet. The alphabet
     * must contain at least 16 unique characters. Allows control over which
     * characters appear in the encoded output.
     *
     * @param  string $alphabet Custom character set (minimum 16 unique characters)
     * @return self   New conductor instance with updated alphabet
     */
    public function alphabet(string $alphabet): self
    {
        return new self($this->manager, $this->salt, $this->minLength, $alphabet);
    }

    /**
     * Generate a new Hashid (uses timestamp + counter internally).
     *
     * Creates a Hashid by encoding the current timestamp and an internal counter.
     * This provides a convenient way to generate unique Hashids without manually
     * managing the numeric input.
     *
     * @return Hashid New Hashid identifier object
     */
    public function generate(): Hashid
    {
        /** @var HashidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Hashid, $this->buildConfig());

        return $generator->generate();
    }

    /**
     * Encode an array of numbers into a Hashid.
     *
     * Encodes multiple non-negative integers into a single short, URL-safe
     * string. The encoding is deterministic and reversible - the same numbers
     * with the same configuration always produce the same Hashid.
     *
     * @param  array<int, int|string> $numbers Array of non-negative integers to encode (strings accepted for values > PHP_INT_MAX)
     * @return Hashid                 Encoded Hashid identifier object
     */
    public function encode(array $numbers): Hashid
    {
        /** @var HashidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Hashid, $this->buildConfig());

        return $generator->encode($numbers);
    }

    /**
     * Encode a single number into a Hashid.
     *
     * Convenience method for encoding a single integer. Equivalent to calling
     * encode() with a single-element array.
     *
     * @param  int    $number Non-negative integer to encode
     * @return Hashid Encoded Hashid identifier object
     */
    public function encodeNumber(int $number): Hashid
    {
        /** @var HashidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Hashid, $this->buildConfig());

        return $generator->encodeNumber($number);
    }

    /**
     * Encode a hex string into a Hashid.
     *
     * Encodes a hexadecimal string into a Hashid. Useful for encoding UUIDs
     * or other hex-based identifiers into a more compact, URL-safe format.
     *
     * @param  string $hex Hexadecimal string to encode (without 0x prefix)
     * @return Hashid Encoded Hashid identifier object
     */
    public function encodeHex(string $hex): Hashid
    {
        /** @var HashidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Hashid, $this->buildConfig());

        return $generator->encodeHex($hex);
    }

    /**
     * Decode a Hashid string back to its original numbers.
     *
     * Reverses the encoding process to recover the original array of integers.
     * Returns an empty array if the Hashid is invalid or was encoded with
     * different configuration parameters.
     *
     * @param  string                 $value Hashid string to decode
     * @return array<int, int|string> Array of decoded integers in their original order (strings for values > PHP_INT_MAX)
     */
    public function decode(string $value): array
    {
        /** @var HashidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Hashid, $this->buildConfig());

        return $generator->decode($value);
    }

    /**
     * Decode a Hashid string back to its original hex string.
     *
     * Reverses hex encoding to recover the original hexadecimal string.
     * Useful for decoding Hashids that were created from UUIDs or other
     * hex-based identifiers.
     *
     * @param  string $value Hashid string to decode
     * @return string Decoded hexadecimal string (without 0x prefix)
     */
    public function decodeHex(string $value): string
    {
        /** @var HashidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Hashid, $this->buildConfig());

        return $generator->decodeHex($value);
    }

    /**
     * Parse a Hashid string into a Hashid object.
     *
     * Converts a Hashid string representation into a Hashid object for
     * inspection and manipulation. Does not decode the underlying numbers.
     *
     * @param  string $value Hashid string to parse
     * @return Hashid Parsed Hashid identifier object
     */
    public function parse(string $value): Hashid
    {
        /** @var HashidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Hashid, $this->buildConfig());

        return $generator->parse($value);
    }

    /**
     * Check if a string is a valid Hashid.
     *
     * Validates whether a given string conforms to the Hashid format based
     * on the current alphabet configuration. Does not verify if it can be
     * successfully decoded.
     *
     * @param  string $value String to validate
     * @return bool   True if the string is a valid Hashid format, false otherwise
     */
    public function isValid(string $value): bool
    {
        return $this->manager->getGenerator(IdentifierType::Hashid, $this->buildConfig())->isValid($value);
    }

    /**
     * Build configuration array for generator.
     *
     * Assembles the current conductor configuration into an array format
     * expected by the HashidGenerator. Only includes non-null configuration
     * values to allow generator defaults to apply.
     *
     * @return array<string, mixed> Configuration array with salt, min_length, and alphabet
     */
    private function buildConfig(): array
    {
        $config = [];

        if ($this->salt !== null) {
            $config['salt'] = $this->salt;
        }

        if ($this->minLength !== null) {
            $config['min_length'] = $this->minLength;
        }

        if ($this->alphabet !== null) {
            $config['alphabet'] = $this->alphabet;
        }

        return $config;
    }
}
