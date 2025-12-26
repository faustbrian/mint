<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Contracts;

/**
 * Interface for bidirectional number-to-string encoders.
 *
 * Defines the contract for encoding numeric values into compact, URL-safe string
 * representations and decoding them back. Used by identifier generators like Sqids
 * and Hashids that transform sequential integers into obfuscated, shareable strings
 * suitable for public URLs, short links, and human-readable identifiers.
 *
 * Implementations must ensure:
 * - Deterministic encoding (same input always produces same output)
 * - Reversible transformation (decode(encode(x)) === x)
 * - URL-safe output (no special characters requiring encoding)
 * - Consistent length for similar-sized inputs (implementation-specific)
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface EncoderInterface
{
    /**
     * Encode one or more integers into a compact string representation.
     *
     * Transforms numeric values into a URL-safe, obfuscated string suitable for
     * public exposure. Multiple numbers can be encoded together to create composite
     * identifiers that embed related data (e.g., user ID + timestamp).
     *
     * @param array<int> $numbers Non-negative integers to encode. Order is preserved
     *                            during encoding and affects the output string. Supports
     *                            single values or multiple values for composite identifiers.
     *                            Values must be within the encoder's supported range
     *                            (typically 0 to PHP_INT_MAX).
     *
     * @return string The encoded representation as a URL-safe string using the
     *                configured alphabet. Length varies based on input magnitude
     *                and encoder configuration (alphabet, padding, etc.).
     */
    public function encode(array $numbers): string;

    /**
     * Decode a string back into the original numeric values.
     *
     * Reverses the encoding transformation to recover the original integers.
     * The decoded array maintains the same order as the original input.
     *
     * @param string $value The encoded string to decode. Must contain only characters
     *                      from the encoder's alphabet. Case sensitivity depends on
     *                      implementation configuration. Invalid characters or malformed
     *                      strings may throw exceptions or return empty arrays based on
     *                      implementation-specific error handling.
     *
     * @return array<int> The decoded integers in their original order. Returns an empty
     *                    array if the input string is empty or invalid (implementation-specific).
     *                    For composite identifiers, the array contains multiple values in
     *                    the same sequence as originally encoded.
     */
    public function decode(string $value): array;
}
