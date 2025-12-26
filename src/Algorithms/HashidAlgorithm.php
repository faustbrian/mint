<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms;

use Cline\Mint\Algorithms\Hashids\Hashids;
use Cline\Mint\Contracts\AlgorithmInterface;
use Cline\Mint\Exceptions\InvalidHashidFormatException;
use Override;

use function microtime;

/**
 * Hashid algorithm implementation.
 *
 * Generates short, URL-safe IDs from numbers using the Hashids algorithm.
 * Supports encoding single or multiple integers, as well as hexadecimal strings.
 * The algorithm produces obfuscated, non-sequential IDs while maintaining
 * reversibility through deterministic encoding.
 *
 * Structure:
 * - Base: Timestamp (milliseconds) * 1000 + counter
 * - Encoding: Variable-length alphanumeric string
 * - Uniqueness: Counter-based (wraps at 1000 per millisecond)
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://hashids.org/
 */
final class HashidAlgorithm implements AlgorithmInterface
{
    /**
     * Counter for generating unique IDs within the same millisecond.
     *
     * Incremented with each call to generate() to ensure uniqueness when multiple
     * IDs are created in rapid succession. Wraps at 1000 to stay within a single
     * millisecond precision window.
     */
    private static int $counter = 0;

    /**
     * The Hashids encoder instance used for encoding and decoding operations.
     */
    private readonly Hashids $hashids;

    /**
     * Create a new Hashid algorithm instance.
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
        $this->hashids = new Hashids($salt, $minLength, $alphabet);
    }

    /**
     * Generate raw Hashid data.
     *
     * Creates a unique identifier by combining the current timestamp in milliseconds
     * with an incrementing counter. This ensures uniqueness even when generating
     * multiple IDs within the same millisecond.
     *
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function generate(): array
    {
        // Combine timestamp with counter to ensure uniqueness within same millisecond
        $timestamp = (int) (microtime(true) * 1_000);
        $number = $timestamp * 1_000 + (self::$counter++ % 1_000);

        $value = $this->hashids->encode($number);

        return [
            'value' => $value,
            'bytes' => $value,
        ];
    }

    /**
     * Parse a Hashid string into raw data.
     *
     * Validates and decodes a Hashid string, recovering the original integer values
     * that were encoded. The salt and alphabet used during encoding must match the
     * current algorithm configuration for successful decoding.
     *
     * @param string $value The Hashid string to parse
     *
     * @throws InvalidHashidFormatException        When the value is not a valid Hashid or cannot be decoded
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function parse(string $value): array
    {
        if (!$this->isValid($value)) {
            throw InvalidHashidFormatException::forValue($value);
        }

        return [
            'value' => $value,
            'bytes' => $value,
        ];
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
        if ($value === '') {
            return false;
        }

        // Attempt numeric decoding first
        $numbers = $this->hashids->decode($value);

        if ($numbers === []) {
            // Try hex decoding as fallback
            $hex = $this->hashids->decodeHex($value);

            if ($hex === '') {
                return false;
            }

            return $this->hashids->encodeHex($hex) === $value;
        }

        // Verify round-trip encoding produces identical result
        return $this->hashids->encode(...$numbers) === $value;
    }

    /**
     * Encode one or more numbers into a Hashid string.
     *
     * Multiple numbers can be encoded into a single Hashid, which is useful for
     * representing composite keys or related identifiers in a compact format.
     *
     * @param array<int, int|string> $numbers Array of non-negative integers to encode. Order matters
     *                                        for decoding. All numbers must be non-negative integers
     *                                        (strings accepted for values > PHP_INT_MAX).
     *
     * @return string The encoded Hashid string
     */
    public function encode(array $numbers): string
    {
        return $this->hashids->encode(...$numbers);
    }

    /**
     * Encode a hex string into a Hashid string.
     *
     * Useful for encoding UUIDs or other hexadecimal identifiers into a more
     * compact, URL-safe format. Note that hex-encoded Hashids cannot be decoded
     * back to the original integer array.
     *
     * @param string $hex Hexadecimal string to encode (without 0x prefix)
     *
     * @return string The encoded Hashid string
     */
    public function encodeHex(string $hex): string
    {
        return $this->hashids->encodeHex($hex);
    }

    /**
     * Decode a Hashid to its original numbers.
     *
     * Returns the raw numeric array. Useful when you only need the decoded values
     * without the additional metadata.
     *
     * @param string $value The Hashid string to decode
     *
     * @return array<int, int|string> Array of decoded integers in their original order (strings for values > PHP_INT_MAX)
     */
    public function decode(string $value): array
    {
        return $this->hashids->decode($value);
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
        return $this->hashids->decodeHex($value);
    }
}
