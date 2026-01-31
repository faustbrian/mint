<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Support;

use function array_flip;
use function chr;
use function mb_str_split;
use function mb_strlen;
use function mb_strtolower;
use function mb_substr;
use function ord;

/**
 * Base32Hex encoding/decoding utilities per RFC 4648.
 *
 * Implements the Base32Hex variant (also known as "Extended Hex Alphabet")
 * which uses lowercase hexadecimal-style ordering (0-9, a-v) instead of the
 * standard Base32 alphabet. This provides better lexicographic sorting properties
 * compared to standard Base32.
 *
 * Primary use case is encoding XIDs (eXtensible Identifiers), which benefit from
 * the sortable nature of this alphabet. The lowercase format is URL-safe and
 * case-insensitive, making it suitable for database keys and API identifiers.
 *
 * Alphabet: 0123456789abcdefghijklmnopqrstuv (32 characters)
 * Encoding: 5 bits per character (8 bytes = 13 characters with padding)
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://datatracker.ietf.org/doc/html/rfc4648#section-7
 */
final class Base32Hex
{
    /**
     * Base32Hex alphabet in lowercase hexadecimal-style ordering.
     *
     * Uses 0-9 followed by a-v, providing natural lexicographic sorting
     * when encoded values are compared as strings. Excludes w-z to maintain
     * exactly 32 characters (2^5) for efficient 5-bit encoding.
     */
    public const string ALPHABET = '0123456789abcdefghijklmnopqrstuv';

    /**
     * Encode raw binary bytes to Base32Hex string.
     *
     * Converts arbitrary binary data into Base32Hex representation using
     * a streaming bit-packing algorithm. Processes input byte-by-byte,
     * accumulating bits in a buffer and emitting Base32Hex characters
     * whenever 5 bits are available. More memory-efficient than chunk-based
     * encoding for variable-length inputs.
     *
     * @param string $bytes The raw binary bytes to encode
     *
     * @return string The Base32Hex-encoded string in lowercase
     */
    public static function encode(string $bytes): string
    {
        $chars = mb_str_split(self::ALPHABET);
        $result = '';

        $length = mb_strlen($bytes, '8bit');
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0; $i < $length; ++$i) {
            $buffer = ($buffer << 8) | ord($bytes[$i]);
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $result .= $chars[($buffer >> $bitsLeft) & 0x1F];
            }
        }

        if ($bitsLeft > 0) {
            $result .= $chars[($buffer << (5 - $bitsLeft)) & 0x1F];
        }

        return $result;
    }

    /**
     * Decode a Base32Hex string to raw binary bytes.
     *
     * Converts a Base32Hex string back into its original binary representation
     * using a streaming bit-unpacking algorithm. Processes input character-by-character,
     * accumulating bits in a buffer and emitting bytes whenever 8 bits are available.
     * Invalid characters are silently skipped for error tolerance. Case-insensitive.
     *
     * @param string $value The Base32Hex string to decode (case-insensitive)
     *
     * @return string The raw binary bytes
     */
    public static function decode(string $value): string
    {
        $value = mb_strtolower($value);
        $chars = mb_str_split(self::ALPHABET);
        $charMap = array_flip($chars);

        $result = '';
        $buffer = 0;
        $bitsLeft = 0;
        $length = mb_strlen($value);

        for ($i = 0; $i < $length; ++$i) {
            $char = mb_substr($value, $i, 1);

            if (!isset($charMap[$char])) {
                continue;
            }

            $buffer = ($buffer << 5) | $charMap[$char];
            $bitsLeft += 5;

            if ($bitsLeft < 8) {
                continue;
            }

            $bitsLeft -= 8;
            $result .= chr(($buffer >> $bitsLeft) & 0xFF);
        }

        return $result;
    }
}
