<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Generators;

use Cline\Mint\Algorithms\SqidAlgorithm;
use Cline\Mint\Contracts\GeneratorInterface;
use Cline\Mint\Exceptions\InvalidSqidFormatException;
use Cline\Mint\Support\Identifiers\Sqid;
use Override;

/**
 * Sqid (Short Unique ID) generator.
 *
 * Generates short, URL-safe IDs from numbers using the Sqids algorithm. Sqids
 * is the modern successor to Hashids, featuring improved shuffle algorithms,
 * configurable profanity filtering, and better randomization. Unlike Hashids,
 * Sqids requires a minimum 3-character alphabet for security.
 *
 * Sqids maintains deterministic encoding (same numbers always produce the same
 * ID) while appearing random. The blocklist feature prevents accidental profanity
 * or sensitive words from appearing in generated IDs.
 *
 * ```php
 * $generator = new SqidGenerator(minLength: 8, blocklist: ['badword']);
 * $sqid = $generator->generate();
 * echo $sqid->toString(); // e.g., "86Rf07xd"
 * ```
 *
 * @api
 * @author Brian Faust <brian@cline.sh>
 * @see https://sqids.org/
 * @psalm-immutable
 */
final readonly class SqidGenerator implements GeneratorInterface
{
    /**
     * The Sqid algorithm instance used for encoding and decoding operations.
     */
    private SqidAlgorithm $algorithm;

    /**
     * Create a new Sqid generator.
     *
     * The alphabet must contain at least 3 unique characters for security.
     * Blocklist filtering occurs during encoding, potentially increasing ID
     * length when blocked patterns are detected.
     *
     * @param string        $alphabet  Custom character set for encoding. Must contain at least
     *                                 3 unique characters. Defaults to alphanumeric (a-z, A-Z, 0-9).
     *                                 Smaller alphabets result in longer IDs for equivalent numbers.
     * @param int           $minLength Minimum length of generated ID strings. IDs shorter than this
     *                                 will be padded. Set to 0 for variable-length IDs. Common values:
     *                                 6-16 characters for user-facing identifiers.
     * @param array<string> $blocklist Array of words to prevent from appearing in IDs. Words are
     *                                 case-insensitive. When a blocked pattern is detected, the ID
     *                                 is re-encoded with additional padding until no matches remain.
     */
    public function __construct(
        string $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
        int $minLength = 0,
        array $blocklist = [],
    ) {
        $this->algorithm = new SqidAlgorithm($alphabet, $minLength, $blocklist);
    }

    /**
     * Generate a new Sqid from an auto-incremented counter.
     *
     * Creates a unique identifier by combining the current timestamp in milliseconds
     * with an incrementing counter. This ensures uniqueness even when generating
     * multiple IDs within the same millisecond.
     *
     * @return Sqid The generated Sqid instance
     */
    #[Override()]
    public function generate(): Sqid
    {
        $data = $this->algorithm->generate();
        $numbers = $this->algorithm->decode($data['value']);

        return new Sqid($data['value'], $data['bytes'], $numbers);
    }

    /**
     * Encode one or more numbers into a Sqid.
     *
     * Multiple numbers can be encoded into a single Sqid, useful for representing
     * composite keys or related identifiers compactly. The order of numbers matters
     * for decoding.
     *
     * @param array<int> $numbers Array of non-negative integers to encode. Order is preserved
     *                            during decoding. All numbers must be non-negative.
     *
     * @return Sqid The encoded Sqid instance containing the original numbers
     */
    public function encode(array $numbers): Sqid
    {
        $data = $this->algorithm->encodeNumbers($numbers);

        return new Sqid($data['value'], $data['bytes'], $numbers);
    }

    /**
     * Encode a single number into a Sqid.
     *
     * Convenience method for encoding a single integer value.
     *
     * @param int $number Non-negative integer to encode
     *
     * @return Sqid The encoded Sqid instance
     */
    public function encodeNumber(int $number): Sqid
    {
        return $this->encode([$number]);
    }

    /**
     * Parse a Sqid string back to its original numbers.
     *
     * Validates and decodes a Sqid string, recovering the original integer values.
     * The alphabet and configuration used during encoding must match the current
     * generator for successful decoding.
     *
     * @param string $value The Sqid string to parse
     *
     * @throws InvalidSqidFormatException When the value is not a valid Sqid or cannot be decoded
     * @return Sqid                       The parsed Sqid instance with decoded numbers
     */
    #[Override()]
    public function parse(string $value): Sqid
    {
        $data = $this->algorithm->parse($value);
        $numbers = $this->algorithm->decode($data['value']);

        return new Sqid($data['value'], $data['bytes'], $numbers);
    }

    /**
     * Check if a string is a valid Sqid.
     *
     * Validates by attempting to decode and re-encode the value. A valid Sqid
     * will produce the same string when re-encoded, confirming it matches the
     * current alphabet and configuration.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid Sqid that can be decoded
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        return $this->algorithm->isValid($value);
    }

    /**
     * Get the generator name.
     *
     * @return string The identifier 'sqid'
     */
    #[Override()]
    public function getName(): string
    {
        return 'sqid';
    }

    /**
     * Decode a Sqid to its original numbers.
     *
     * Returns the raw numeric array without creating a Sqid instance. Useful when
     * you only need the decoded values without additional metadata.
     *
     * @param string $value The Sqid string to decode
     *
     * @return array<int> Array of decoded integers in their original order
     */
    public function decode(string $value): array
    {
        return $this->algorithm->decode($value);
    }
}
