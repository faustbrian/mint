<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Generators;

use Cline\Mint\Algorithms\HashidAlgorithm;
use Cline\Mint\Contracts\GeneratorInterface;
use Cline\Mint\Exceptions\InvalidHashidFormatException;
use Cline\Mint\Support\Identifiers\Hashid;
use Override;

/**
 * Hashid generator.
 *
 * Generates short, URL-safe IDs from numbers using the Hashids algorithm.
 * Supports encoding single or multiple integers, as well as hexadecimal strings.
 * The algorithm produces obfuscated, non-sequential IDs while maintaining
 * reversibility through deterministic encoding.
 *
 * Note: Hashids is a legacy format that remains widely used. For new projects,
 * consider using Sqids which offers improved randomization and automatic
 * profanity filtering.
 *
 * ```php
 * $generator = new HashidGenerator(salt: 'my-app-salt', minLength: 8);
 * $hashid = $generator->generate();
 * echo $hashid->toString(); // e.g., "gB0NV05e"
 * ```
 *
 * @api
 * @author Brian Faust <brian@cline.sh>
 * @see https://hashids.org/
 * @psalm-immutable
 */
final readonly class HashidGenerator implements GeneratorInterface
{
    /**
     * The Hashid algorithm instance.
     */
    private HashidAlgorithm $algorithm;

    /**
     * Create a new Hashid generator.
     *
     * The salt makes IDs unique to your application, preventing reverse-engineering
     * through rainbow tables. The minimum length pads IDs to a consistent size,
     * useful for maintaining fixed-width identifiers in user-facing contexts.
     *
     * @param string $salt      Application-specific salt value that makes generated IDs
     *                          unique and prevents decoding without knowledge of the salt.
     *                          Use a consistent salt across your application to ensure
     *                          IDs can be decoded reliably.
     * @param int    $minLength Minimum length of generated ID strings. IDs shorter than
     *                          this will be padded. Set to 0 for variable-length IDs
     *                          based on the input numbers. Common values: 8-16 characters.
     * @param string $alphabet  Custom character set for encoding. Must contain at least
     *                          16 unique characters. Defaults to alphanumeric characters.
     *                          Avoid ambiguous characters (0/O, 1/l/I) for user-facing IDs.
     */
    public function __construct(
        string $salt = '',
        int $minLength = 0,
        string $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
    ) {
        $this->algorithm = new HashidAlgorithm($salt, $minLength, $alphabet);
    }

    /**
     * Generate a new Hashid from an auto-incremented counter.
     *
     * Creates a unique identifier by combining the current timestamp in milliseconds
     * with an incrementing counter. This ensures uniqueness even when generating
     * multiple IDs within the same millisecond.
     *
     * @return Hashid The generated Hashid instance
     */
    #[Override()]
    public function generate(): Hashid
    {
        $data = $this->algorithm->generate();
        $numbers = $this->algorithm->decode($data['value']);

        return new Hashid($data['value'], $data['bytes'], $numbers);
    }

    /**
     * Encode one or more numbers into a Hashid.
     *
     * Multiple numbers can be encoded into a single Hashid, which is useful for
     * representing composite keys or related identifiers in a compact format.
     *
     * @param array<int, int|string> $numbers Array of non-negative integers to encode. Order matters
     *                                        for decoding. All numbers must be non-negative integers
     *                                        (strings accepted for values > PHP_INT_MAX).
     *
     * @return Hashid The encoded Hashid instance containing the original numbers
     */
    public function encode(array $numbers): Hashid
    {
        $value = $this->algorithm->encode($numbers);

        return new Hashid($value, $value, $numbers);
    }

    /**
     * Encode a single number into a Hashid.
     *
     * Convenience method for encoding a single integer value.
     *
     * @param int $number Non-negative integer to encode
     *
     * @return Hashid The encoded Hashid instance
     */
    public function encodeNumber(int $number): Hashid
    {
        return $this->encode([$number]);
    }

    /**
     * Encode a hex string into a Hashid.
     *
     * Useful for encoding UUIDs or other hexadecimal identifiers into a more
     * compact, URL-safe format. Note that hex-encoded Hashids cannot be decoded
     * back to the original integer array.
     *
     * @param string $hex Hexadecimal string to encode (without 0x prefix)
     *
     * @return Hashid The encoded Hashid instance with the original hex value preserved
     */
    public function encodeHex(string $hex): Hashid
    {
        $value = $this->algorithm->encodeHex($hex);
        $numbers = []; // Hex encoding doesn't map to numeric array

        return new Hashid($value, $value, $numbers, $hex);
    }

    /**
     * Parse a Hashid string back to its original numbers.
     *
     * Validates and decodes a Hashid string, recovering the original integer values
     * that were encoded. The salt and alphabet used during encoding must match the
     * current generator configuration for successful decoding.
     *
     * @param string $value The Hashid string to parse
     *
     * @throws InvalidHashidFormatException When the value is not a valid Hashid or cannot be decoded
     * @return Hashid                       The parsed Hashid instance with decoded numbers
     */
    #[Override()]
    public function parse(string $value): Hashid
    {
        $data = $this->algorithm->parse($value);
        $numbers = $this->algorithm->decode($data['value']);

        return new Hashid($data['value'], $data['bytes'], $numbers);
    }

    /**
     * Check if a string is a valid Hashid.
     *
     * Validates by attempting to decode and re-encode the value. A valid Hashid
     * will produce the same string when re-encoded. Supports both number-encoded
     * and hex-encoded Hashids.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid Hashid that can be decoded
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        return $this->algorithm->isValid($value);
    }

    /**
     * Get the generator name.
     *
     * @return string The identifier 'hashid'
     */
    #[Override()]
    public function getName(): string
    {
        return 'hashid';
    }

    /**
     * Decode a Hashid to its original numbers.
     *
     * Returns the raw numeric array without creating a Hashid instance. Useful when
     * you only need the decoded values without the additional metadata.
     *
     * @param string $value The Hashid string to decode
     *
     * @return array<int, int|string> Array of decoded integers in their original order (strings for values > PHP_INT_MAX)
     */
    public function decode(string $value): array
    {
        return $this->algorithm->decode($value);
    }

    /**
     * Decode a Hashid to its original hex string.
     *
     * Only works with Hashids created via encodeHex(). Returns the original
     * hexadecimal string that was encoded.
     *
     * @param string $value The Hashid string to decode
     *
     * @return string The decoded hexadecimal string (without 0x prefix)
     */
    public function decodeHex(string $value): string
    {
        return $this->algorithm->decodeHex($value);
    }
}
