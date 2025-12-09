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
use function mb_substr;
use function ord;

/**
 * Base62 encoding/decoding utilities for compact identifier representation.
 *
 * Implements Base62 encoding using the alphanumeric character set (0-9, A-Z, a-z)
 * which provides efficient encoding with 62 possible values per character. More
 * compact than Base32/Base36 while remaining human-readable and URL-safe without
 * requiring percent-encoding.
 *
 * Primary use case is encoding KSUIDs (K-Sortable Unique IDentifiers) and other
 * large numeric identifiers into shorter strings. Case-sensitive encoding where
 * uppercase and lowercase letters are distinct values.
 *
 * Alphabet: 0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz (62 characters)
 * Encoding efficiency: log2(62) ≈ 5.95 bits per character
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Base62
{
    /**
     * Base62 alphabet containing digits, uppercase, and lowercase letters.
     *
     * Ordered as digits (0-9), uppercase letters (A-Z), then lowercase letters (a-z)
     * for consistent encoding/decoding. Case-sensitive - 'A' and 'a' represent
     * different values (10 and 36 respectively).
     */
    public const string ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * Encode a numeric value to Base62 string.
     *
     * Converts a decimal number (integer or arbitrary-precision string) into
     * Base62 representation. Uses BCMath for handling large numbers beyond
     * PHP's integer limits. Output is left-padded with zeros to meet minimum length.
     * Case-sensitive encoding.
     *
     * @param int|string $number The number to encode (accepts string for numbers > PHP_INT_MAX)
     * @param int        $length Minimum output length (pads with leading zeros if needed)
     *
     * @return string The Base62-encoded string (case-sensitive)
     */
    public static function encode(int|string $number, int $length = 0): string
    {
        $number = (string) $number;
        $result = '';
        $chars = mb_str_split(self::ALPHABET);

        while (bccomp($number, '0') > 0) {
            $remainder = (int) bcmod($number, '62');
            $result = $chars[$remainder].$result;
            $number = bcdiv($number, '62', 0);
        }

        if ($result === '') {
            $result = '0';
        }

        return mb_str_pad($result, $length, '0', STR_PAD_LEFT);
    }

    /**
     * Decode a Base62 string to a numeric value.
     *
     * Converts a Base62 string back to its decimal representation. Case-sensitive
     * decoding where uppercase and lowercase letters represent different values.
     * Invalid characters are silently skipped. Returns string to support large
     * numbers beyond PHP's integer limits.
     *
     * @param string $value The Base62 string to decode (case-sensitive)
     *
     * @return string The decoded number as an arbitrary-precision string
     */
    public static function decode(string $value): string
    {
        $chars = mb_str_split(self::ALPHABET);
        $charMap = array_flip($chars);

        $result = '0';
        $length = mb_strlen($value);

        for ($i = 0; $i < $length; ++$i) {
            $char = mb_substr($value, $i, 1);

            if (!isset($charMap[$char])) {
                continue;
            }

            $result = bcmul($result, '62');
            $result = bcadd($result, (string) $charMap[$char]);
        }

        return $result;
    }

    /**
     * Encode raw binary bytes to Base62 string.
     *
     * Converts arbitrary binary data into Base62 representation by first
     * converting the bytes to a large integer (treating bytes as big-endian),
     * then encoding that integer. Used by KSUID for encoding the combined
     * timestamp and random payload.
     *
     * @param string $bytes  The raw binary bytes to encode
     * @param int    $length Minimum output length (pads with leading zeros if needed)
     *
     * @return string The Base62-encoded string representation
     */
    public static function encodeBytes(string $bytes, int $length = 0): string
    {
        // Convert bytes to a big integer
        $number = '0';
        $byteLength = mb_strlen($bytes, '8bit');

        for ($i = 0; $i < $byteLength; ++$i) {
            $number = bcmul($number, '256');
            $number = bcadd($number, (string) ord($bytes[$i]));
        }

        return self::encode($number, $length);
    }

    /**
     * Decode a Base62 string to raw binary bytes.
     *
     * Converts a Base62 string back to its binary representation by first
     * decoding to a large integer, then converting that integer to bytes
     * (big-endian). Optionally pads the result to a specific byte length
     * with leading zero bytes. Used by KSUID for parsing encoded identifiers.
     *
     * @param string $value  The Base62 string to decode (case-sensitive)
     * @param int    $length Expected byte length for padding (0 for no padding)
     *
     * @return string The raw binary bytes
     */
    public static function decodeBytes(string $value, int $length = 0): string
    {
        $number = self::decode($value);
        $result = '';

        while (bccomp($number, '0') > 0) {
            $remainder = (int) bcmod($number, '256');
            $result = chr($remainder).$result;
            $number = bcdiv($number, '256', 0);
        }

        if ($length > 0) {
            return mb_str_pad($result, $length, "\x00", STR_PAD_LEFT, '8bit');
        }

        return $result;
    }
}
