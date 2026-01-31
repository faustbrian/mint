<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Support;

use const STR_PAD_LEFT;

use function array_flip;
use function bcadd;
use function bccomp;
use function bcdiv;
use function bcmod;
use function bcmul;
use function chr;
use function mb_str_pad;
use function mb_str_split;
use function mb_strlen;
use function mb_strtoupper;
use function mb_substr;
use function ord;

/**
 * Crockford Base32 encoding/decoding utilities.
 *
 * Implements Douglas Crockford's Base32 encoding scheme designed to be
 * more human-friendly than standard Base32. The alphabet excludes letters
 * that can be confused with numbers (I/L with 1, O with 0) and vowels to
 * avoid forming accidental words.
 *
 * Primary use case is encoding ULIDs (Universally Unique Lexicographically
 * Sortable Identifiers), though it can encode any binary data or large numbers.
 *
 * Alphabet: 0123456789ABCDEFGHJKMNPQRSTVWXYZ (32 characters)
 * Excluded: I, L, O, U (to prevent confusion and accidental profanity)
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://www.crockford.com/base32.html
 */
final class Base32
{
    /**
     * Crockford Base32 alphabet containing 32 unambiguous characters.
     *
     * Designed to minimize transcription errors by excluding visually similar
     * characters and vowels that could form inappropriate words.
     */
    public const string ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * Encode a numeric value to Crockford Base32 string.
     *
     * Converts a decimal number (integer or arbitrary-precision string) into
     * Base32 representation. Uses BCMath for handling large numbers beyond
     * PHP's integer limits. Output is left-padded with zeros to meet minimum length.
     *
     * @param int|string $number The number to encode (accepts string for numbers > PHP_INT_MAX)
     * @param int        $length Minimum output length (pads with leading zeros if needed)
     *
     * @return string The Base32-encoded string
     */
    public static function encode(int|string $number, int $length = 0): string
    {
        $number = (string) $number;
        $result = '';
        $chars = mb_str_split(self::ALPHABET);

        while (bccomp($number, '0') > 0) {
            $remainder = (int) bcmod($number, '32');
            $result = $chars[$remainder].$result;
            $number = bcdiv($number, '32', 0);
        }

        if ($result === '') {
            $result = '0';
        }

        return mb_str_pad($result, $length, '0', STR_PAD_LEFT);
    }

    /**
     * Decode a Crockford Base32 string to a numeric value.
     *
     * Converts a Base32 string back to its decimal representation. Handles
     * common character substitutions (I/L → 1, O → 0) for error correction
     * when humans transcribe identifiers. Returns string to support large
     * numbers beyond PHP's integer limits.
     *
     * @param string $value The Base32 string to decode (case-insensitive)
     *
     * @return string The decoded number as an arbitrary-precision string
     */
    public static function decode(string $value): string
    {
        $value = mb_strtoupper($value);
        $chars = mb_str_split(self::ALPHABET);
        $charMap = array_flip($chars);

        $result = '0';
        $length = mb_strlen($value);

        for ($i = 0; $i < $length; ++$i) {
            $char = mb_substr($value, $i, 1);
            // Handle common substitutions
            $char = match ($char) {
                'I', 'L' => '1',
                'O' => '0',
                default => $char,
            };

            if (!isset($charMap[$char])) {
                continue;
            }

            $result = bcmul($result, '32');
            $result = bcadd($result, (string) $charMap[$char]);
        }

        return $result;
    }

    /**
     * Encode raw binary bytes to Crockford Base32 string.
     *
     * Converts arbitrary binary data into Base32 representation by processing
     * 5-byte chunks (40 bits) into 8 Base32 characters. More efficient than
     * numeric encoding for binary data like timestamps and random payloads.
     * Used internally by ULID encoding.
     *
     * @param string $bytes The raw binary bytes to encode
     *
     * @return string The Base32-encoded string representation
     */
    public static function encodeBytes(string $bytes): string
    {
        $chars = mb_str_split(self::ALPHABET);
        $result = '';

        // Process 5 bytes at a time (40 bits = 8 base32 chars)
        $length = mb_strlen($bytes, '8bit');
        $padding = (5 - ($length % 5)) % 5;
        $bytes = mb_str_pad($bytes, $length + $padding, "\x00", STR_PAD_LEFT, '8bit');
        $length = mb_strlen($bytes, '8bit');

        for ($i = 0; $i < $length; $i += 5) {
            $chunk = mb_substr($bytes, $i, 5, '8bit');
            $n = 0;

            for ($j = 0; $j < 5; ++$j) {
                $n = ($n << 8) | ord($chunk[$j]);
            }

            for ($j = 7; $j >= 0; --$j) {
                $result .= $chars[($n >> ($j * 5)) & 0x1F];
            }
        }

        return $result;
    }

    /**
     * Decode a Crockford Base32 string to raw binary bytes.
     *
     * Converts a Base32 string back into its original binary representation
     * by processing 8-character chunks (40 bits) into 5 bytes. Handles common
     * character substitutions for error correction. Used internally by ULID
     * parsing to extract timestamp and randomness components.
     *
     * @param string $value The Base32 string to decode (case-insensitive)
     *
     * @return string The raw binary bytes
     */
    public static function decodeBytes(string $value): string
    {
        $value = mb_strtoupper($value);
        $chars = mb_str_split(self::ALPHABET);
        $charMap = array_flip($chars);

        // Pad to multiple of 8 characters
        $length = mb_strlen($value);
        $padding = (8 - ($length % 8)) % 8;
        $value = mb_str_pad($value, $length + $padding, '0', STR_PAD_LEFT);
        $length = mb_strlen($value);

        $result = '';

        for ($i = 0; $i < $length; $i += 8) {
            $chunk = mb_substr($value, $i, 8);
            $n = 0;

            for ($j = 0; $j < 8; ++$j) {
                $char = mb_substr($chunk, $j, 1);
                $char = match ($char) {
                    'I', 'L' => '1',
                    'O' => '0',
                    default => $char,
                };
                $n = ($n << 5) | ($charMap[$char] ?? 0);
            }

            for ($j = 4; $j >= 0; --$j) {
                $result .= chr(($n >> ($j * 8)) & 0xFF);
            }
        }

        return $result;
    }
}
