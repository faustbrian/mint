<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms\Sqids;

/**
 * Interface for Sqids encoder/decoder implementations.
 *
 * Defines the contract for encoding and decoding integer arrays to/from
 * short, unique, URL-safe IDs with built-in profanity filtering.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://sqids.org/
 */
interface SqidsInterface
{
    /**
     * Encode an array of integers into a short ID.
     *
     * Generates a deterministic, URL-safe ID from the input integers.
     * IDs are automatically regenerated if they match blocklist patterns.
     * Returns empty string for empty input arrays.
     *
     * @param array<int> $numbers Array of non-negative integers to encode
     *
     * @return string The generated ID string
     */
    public function encode(array $numbers): string;

    /**
     * Decode an ID back to its original integer array.
     *
     * Reverses the encoding process to recover the original integers.
     * Returns empty array if the ID is invalid or cannot be decoded.
     *
     * @param string $id The ID string to decode
     *
     * @return array<int> Array of decoded integers (empty if decoding fails)
     */
    public function decode(string $id): array;
}
