<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms;

use Cline\Mint\Algorithms\Sqids\Sqids;
use Cline\Mint\Contracts\AlgorithmInterface;
use Cline\Mint\Exceptions\InvalidSqidFormatException;
use Override;

use function microtime;

/**
 * Sqid (Short Unique ID) algorithm implementation.
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
 * @author Brian Faust <brian@cline.sh>
 * @see https://sqids.org/
 */
final class SqidAlgorithm implements AlgorithmInterface
{
    /**
     * Counter for generating unique IDs within the same millisecond.
     *
     * Incremented with each call to ensure uniqueness when multiple IDs are
     * created in rapid succession. Wraps at 1000 to stay within millisecond
     * precision.
     */
    private static int $counter = 0;

    /**
     * The Sqids encoder instance used for encoding and decoding operations.
     */
    private readonly Sqids $sqids;

    /**
     * Create a new Sqid algorithm instance.
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
        $this->sqids = new Sqids($alphabet, $minLength, $blocklist);
    }

    /**
     * Generate raw Sqid data.
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
        // Combine timestamp with counter to ensure uniqueness
        $timestamp = (int) (microtime(true) * 1_000);
        $number = $timestamp * 1_000 + (self::$counter++ % 1_000);

        return $this->encodeNumbers([$number]);
    }

    /**
     * Parse a Sqid string into raw data.
     *
     * Validates and decodes a Sqid string, recovering the original integer values.
     * The alphabet and configuration used during encoding must match the current
     * algorithm for successful decoding.
     *
     * @param string $value The Sqid string to parse
     *
     * @throws InvalidSqidFormatException          When the value is not a valid Sqid or cannot be decoded
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function parse(string $value): array
    {
        if (!$this->isValid($value)) {
            throw InvalidSqidFormatException::forValue($value);
        }

        return [
            'value' => $value,
            'bytes' => $value,
        ];
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
        if ($value === '') {
            return false;
        }

        // Attempt to decode
        $numbers = $this->sqids->decode($value);

        if ($numbers === []) {
            return false;
        }

        // Verify round-trip encoding produces identical result
        return $this->sqids->encode($numbers) === $value;
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
     * @return array{value: string, bytes: string} The encoded Sqid data
     */
    public function encodeNumbers(array $numbers): array
    {
        $value = $this->sqids->encode($numbers);

        return [
            'value' => $value,
            'bytes' => $value,
        ];
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
        return $this->sqids->decode($value);
    }
}
