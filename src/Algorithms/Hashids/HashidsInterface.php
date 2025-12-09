<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms\Hashids;

/**
 * Interface for Hashids encoder/decoder implementations.
 *
 * Defines the contract for encoding and decoding integers to/from
 * short, unique, non-sequential hashes suitable for public URLs.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://hashids.org/
 */
interface HashidsInterface
{
    /**
     * Encode one or more integers into a hash string.
     *
     * Accepts integers as variadic arguments or a single array.
     * Generated hashes are deterministic - the same input always
     * produces the same output for a given salt/alphabet configuration.
     *
     * @param array<int, int|string>|int|string ...$numbers One or more non-negative integers to encode
     *
     * @return string The generated hash string
     */
    public function encode(...$numbers): string;

    /**
     * Decode a hash string back to the original integers.
     *
     * Reverses the encoding process to recover original values.
     * Returns empty array if the hash is invalid or cannot be decoded.
     *
     * @param string $hash The hash string to decode
     *
     * @return array<int, int|string> Array of decoded integers (empty array if decoding fails)
     */
    public function decode(string $hash): array;

    /**
     * Encode a hexadecimal string into a hash.
     *
     * Useful for encoding long hex values (like MD5/SHA hashes) into
     * shorter, more URL-friendly representations.
     *
     * @param string $str Hexadecimal string to encode
     *
     * @return string Encoded hash string
     */
    public function encodeHex(string $str): string;

    /**
     * Decode a hash back to its original hexadecimal string.
     *
     * Reverses the encodeHex operation.
     *
     * @param string $hash The hash to decode
     *
     * @return string The original hexadecimal string
     */
    public function decodeHex(string $hash): string;
}
