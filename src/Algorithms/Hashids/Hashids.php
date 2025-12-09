<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms\Hashids;

use Cline\Mint\Exceptions\AlphabetContainsSpacesException;
use Cline\Mint\Exceptions\AlphabetTooShortException;
use Cline\Mint\Exceptions\MissingMathExtensionException;
use Cline\Mint\Support\Math\BCMath;
use Cline\Mint\Support\Math\Gmp;
use Cline\Mint\Support\Math\MathInterface;

use const PHP_INT_MAX;

use function array_diff;
use function array_intersect;
use function array_unique;
use function ceil;
use function chunk_split;
use function count;
use function ctype_digit;
use function ctype_xdigit;
use function dechex;
use function explode;
use function extension_loaded;
use function hexdec;
use function implode;
use function is_array;
use function mb_convert_encoding;
use function mb_detect_encoding;
use function mb_detect_order;
use function mb_ord;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function mb_trim;
use function preg_split;
use function str_replace;

/**
 * Hashids encoder/decoder for obfuscating integer IDs.
 *
 * Generates short, unique, non-sequential, URL-safe hashes from integer IDs.
 * Useful for hiding database IDs in URLs while maintaining reversibility.
 * Uses customizable alphabet and salt for security through obscurity.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://hashids.org/
 */
final class Hashids implements HashidsInterface
{
    /**
     * Divisor for calculating number of guard characters from alphabet length.
     */
    public const int GUARD_DIV = 12;

    /**
     * Divisor for calculating separator-to-alphabet ratio.
     */
    public const float SEP_DIV = 3.5;

    /**
     * Cache of shuffled alphabets to avoid redundant shuffle operations.
     *
     * @var array<string, string> Key is "alphabet salt", value is shuffled result
     */
    private array $shuffledAlphabets;

    /**
     * Working alphabet after removing separators and guards.
     */
    private string $alphabet;

    /**
     * Guard characters used to pad hashes to minimum length.
     */
    private string $guards;

    /**
     * Separator characters used between encoded numbers.
     */
    private string $seps = 'cfhistuCFHISTU';

    /**
     * Math implementation for arbitrary-precision arithmetic (GMP or BCMath).
     */
    private readonly MathInterface $math;

    /**
     * Salt used for alphabet shuffling to ensure unique hash outputs.
     */
    private readonly string $salt;

    /**
     * Create a new Hashids encoder/decoder instance.
     *
     * Initializes the alphabet by removing duplicates, extracting separators,
     * and configuring guards for minimum hash length padding.
     *
     * @param string $salt          Secret salt for alphabet shuffling. Different salts produce different hashes.
     * @param int    $minHashLength Minimum hash length. Shorter hashes are padded to this length.
     * @param string $alphabet      Character set for encoding. Must contain at least 16 unique characters
     *                              without spaces. Defaults to alphanumeric characters.
     *
     * @throws AlphabetContainsSpacesException If alphabet contains space characters
     * @throws AlphabetTooShortException       If alphabet contains fewer than 16 characters
     */
    public function __construct(
        string $salt = '',
        private readonly int $minHashLength = 0,
        string $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
    ) {
        $this->salt = mb_convert_encoding($salt, 'UTF-8', mb_detect_encoding($salt, mb_detect_order(), true));
        $alphabet = mb_convert_encoding($alphabet, 'UTF-8', mb_detect_encoding($alphabet, mb_detect_order(), true));
        $this->alphabet = implode('', array_unique($this->multiByteSplit($alphabet)));

        $this->math = $this->getMathExtension();

        if (mb_strlen($this->alphabet) < 16) {
            throw AlphabetTooShortException::forMinimum(16);
        }

        if (mb_strpos($this->alphabet, ' ') !== false) {
            throw AlphabetContainsSpacesException::create();
        }

        $alphabetArray = $this->multiByteSplit($this->alphabet);
        $sepsArray = $this->multiByteSplit($this->seps);
        $this->seps = implode('', array_intersect($sepsArray, $alphabetArray));
        $this->alphabet = implode('', array_diff($alphabetArray, $sepsArray));
        $this->seps = $this->shuffle($this->seps, $this->salt);

        if (!$this->seps || (mb_strlen($this->alphabet) / mb_strlen($this->seps)) > self::SEP_DIV) {
            $sepsLength = (int) ceil(mb_strlen($this->alphabet) / self::SEP_DIV);

            if ($sepsLength > mb_strlen($this->seps)) {
                $diff = $sepsLength - mb_strlen($this->seps);
                $this->seps .= mb_substr($this->alphabet, 0, $diff);
                $this->alphabet = mb_substr($this->alphabet, $diff);
            }
        }

        $this->alphabet = $this->shuffle($this->alphabet, $this->salt);
        $guardCount = (int) ceil(mb_strlen($this->alphabet) / self::GUARD_DIV);

        if (mb_strlen($this->alphabet) < 3) {
            $this->guards = mb_substr($this->seps, 0, $guardCount);
            $this->seps = mb_substr($this->seps, $guardCount);
        } else {
            $this->guards = mb_substr($this->alphabet, 0, $guardCount);
            $this->alphabet = mb_substr($this->alphabet, $guardCount);
        }
    }

    /**
     * Encode one or more integers into a hash string.
     *
     * Accepts integers as variadic arguments or a single array.
     * Returns empty string if input is invalid (non-numeric or empty).
     *
     * @param array<int, int|string>|int|string ...$numbers One or more non-negative integers to encode
     *
     * @return string The generated hash, or empty string if encoding fails
     */
    public function encode(...$numbers): string
    {
        $ret = '';

        if (count($numbers) === 1 && is_array($numbers[0])) {
            $numbers = $numbers[0];
        }

        if ($numbers === []) {
            return $ret;
        }

        foreach ($numbers as $number) {
            $isNumber = ctype_digit((string) $number);

            if (!$isNumber) {
                return $ret;
            }
        }

        $alphabet = $this->alphabet;
        $numbersSize = count($numbers);
        $numbersHashInt = 0;

        foreach ($numbers as $i => $number) {
            $numbersHashInt += $this->math->intval($this->math->mod($number, $i + 100));
        }

        $lottery = mb_substr($alphabet, $numbersHashInt % mb_strlen($alphabet), 1);
        $ret = $lottery;

        foreach ($numbers as $i => $number) {
            $alphabet = $this->shuffle($alphabet, mb_substr($lottery.$this->salt.$alphabet, 0, mb_strlen($alphabet)));
            $ret .= $last = $this->hash($number, $alphabet);

            if ($i + 1 >= $numbersSize) {
                continue;
            }

            $number %= (mb_ord($last, 'UTF-8') + $i);
            $sepsIndex = $this->math->intval($this->math->mod($number, mb_strlen($this->seps)));
            $ret .= mb_substr($this->seps, $sepsIndex, 1);
        }

        if (mb_strlen($ret) < $this->minHashLength) {
            $guardIndex = ($numbersHashInt + mb_ord(mb_substr($ret, 0, 1), 'UTF-8')) % mb_strlen($this->guards);

            $guard = mb_substr($this->guards, $guardIndex, 1);
            $ret = $guard.$ret;

            if (mb_strlen($ret) < $this->minHashLength) {
                $guardIndex = ($numbersHashInt + mb_ord(mb_substr($ret, 2, 1), 'UTF-8')) % mb_strlen($this->guards);
                $guard = mb_substr($this->guards, $guardIndex, 1);

                $ret .= $guard;
            }
        }

        $halfLength = (int) (mb_strlen($alphabet) / 2);

        while (mb_strlen($ret) < $this->minHashLength) {
            $alphabet = $this->shuffle($alphabet, $alphabet);
            $ret = mb_substr($alphabet, $halfLength).$ret.mb_substr($alphabet, 0, $halfLength);

            $excess = mb_strlen($ret) - $this->minHashLength;

            if ($excess <= 0) {
                continue;
            }

            $ret = mb_substr($ret, (int) ($excess / 2), $this->minHashLength);
        }

        return $ret;
    }

    /**
     * Decode a hash string back to the original integers.
     *
     * Reverses the encoding process to recover the original integer values.
     * Returns empty array if the hash is invalid or cannot be decoded.
     *
     * @param string $hash The hash string to decode
     *
     * @return array<int, int|string> Array of decoded integers (may contain strings for values > PHP_INT_MAX)
     */
    public function decode(string $hash): array
    {
        $ret = [];

        if (($hash = mb_trim($hash)) === '' || ($hash = mb_trim($hash)) === '0') {
            return $ret;
        }

        $alphabet = $this->alphabet;

        $hashBreakdown = str_replace($this->multiByteSplit($this->guards), ' ', $hash);
        $hashArray = explode(' ', $hashBreakdown);

        $i = count($hashArray) === 3 || count($hashArray) === 2 ? 1 : 0;

        $hashBreakdown = $hashArray[$i];

        if ($hashBreakdown !== '') {
            $lottery = mb_substr($hashBreakdown, 0, 1);
            $hashBreakdown = mb_substr($hashBreakdown, 1);

            $hashBreakdown = str_replace($this->multiByteSplit($this->seps), ' ', $hashBreakdown);
            $hashArray = explode(' ', $hashBreakdown);

            foreach ($hashArray as $subHash) {
                $alphabet = $this->shuffle($alphabet, mb_substr($lottery.$this->salt.$alphabet, 0, mb_strlen($alphabet)));
                $result = $this->unhash($subHash, $alphabet);

                $ret[] = $this->math->greaterThan($result, PHP_INT_MAX) ? $this->math->strval($result) : $this->math->intval($result);
            }

            if ($this->encode($ret) !== $hash) {
                $ret = [];
            }
        }

        return $ret;
    }

    /**
     * Encode a hexadecimal string into a hash.
     *
     * Converts a hex string to integers (in 12-character chunks) and encodes them.
     * Useful for encoding long hex values like MD5/SHA hashes into shorter IDs.
     *
     * @param string $str Hexadecimal string (case-insensitive)
     *
     * @return string Encoded hash, or empty string if input is not valid hexadecimal
     */
    public function encodeHex(string $str): string
    {
        if (!ctype_xdigit($str)) {
            return '';
        }

        $numbers = mb_trim(chunk_split($str, 12, ' '));
        $numbers = explode(' ', $numbers);

        foreach ($numbers as $i => $number) {
            $numbers[$i] = hexdec('1'.$number);
        }

        return $this->encode(...$numbers);
    }

    /**
     * Decode a hash back to its original hexadecimal string.
     *
     * Reverses the encodeHex operation to recover the original hex value.
     *
     * @param string $hash The hash to decode
     *
     * @return string The original hexadecimal string
     */
    public function decodeHex(string $hash): string
    {
        $ret = '';
        $numbers = $this->decode($hash);

        foreach ($numbers as $number) {
            $ret .= mb_substr(dechex($number), 1);
        }

        return $ret;
    }

    /**
     * Shuffle alphabet using consistent salt-based permutation.
     *
     * Implements a deterministic shuffle algorithm that produces the same
     * output for the same alphabet-salt combination. Results are cached
     * to improve performance for repeated operations.
     *
     * @param string $alphabet The alphabet string to shuffle
     * @param string $salt     The salt used for shuffling determinism
     *
     * @return string The shuffled alphabet
     */
    private function shuffle(string $alphabet, string $salt): string
    {
        $key = $alphabet.' '.$salt;

        if (isset($this->shuffledAlphabets[$key])) {
            return $this->shuffledAlphabets[$key];
        }

        $saltLength = mb_strlen($salt);
        $saltArray = $this->multiByteSplit($salt);

        if ($saltLength === 0) {
            return $alphabet;
        }

        $alphabetArray = $this->multiByteSplit($alphabet);

        for ($i = mb_strlen($alphabet) - 1, $v = 0, $p = 0; $i > 0; $i--, $v++) {
            $v %= $saltLength;
            $p += $int = mb_ord($saltArray[$v], 'UTF-8');
            $j = ($int + $v + $p) % $i;

            $temp = $alphabetArray[$j];
            $alphabetArray[$j] = $alphabetArray[$i];
            $alphabetArray[$i] = $temp;
        }

        $alphabet = implode('', $alphabetArray);
        $this->shuffledAlphabets[$key] = $alphabet;

        return $alphabet;
    }

    /**
     * Encode an integer into a hash string using base conversion.
     *
     * Converts the integer to the custom base defined by the alphabet length,
     * building the hash string from right to left.
     *
     * @param int|string $input    The integer to encode (may be string for large values)
     * @param string     $alphabet The alphabet to use for encoding
     *
     * @return string The encoded hash string
     */
    private function hash(int|string $input, string $alphabet): string
    {
        $hash = '';
        $alphabetLength = mb_strlen($alphabet);

        do {
            $hash = mb_substr($alphabet, $this->math->intval($this->math->mod($input, $alphabetLength)), 1).$hash;

            $input = $this->math->divide($input, $alphabetLength);
        } while ($this->math->greaterThan($input, 0));

        return $hash;
    }

    /**
     * Decode a hash string back to its integer value using base conversion.
     *
     * Reverses the hash() operation by converting from the custom alphabet
     * base back to a decimal integer.
     *
     * @param string $input    The hash string to decode
     * @param string $alphabet The alphabet used for encoding
     *
     * @return int|string The decoded integer (string if value exceeds PHP_INT_MAX)
     */
    private function unhash(string $input, string $alphabet): int|string
    {
        $number = 0;
        $inputLength = mb_strlen($input);

        if ($inputLength && $alphabet) {
            $alphabetLength = mb_strlen($alphabet);
            $inputChars = $this->multiByteSplit($input);

            foreach ($inputChars as $char) {
                $position = mb_strpos($alphabet, $char);

                if ($position === false) {
                    continue;
                }

                $number = $this->math->multiply($number, $alphabetLength);
                $number = $this->math->add($number, $position);
            }
        }

        return $number;
    }

    /**
     * Get the best available arbitrary-precision math extension.
     *
     * Prefers GMP over BCMath for better performance. Throws exception
     * if neither extension is available.
     *
     * @throws MissingMathExtensionException If neither GMP nor BCMath is loaded
     * @return MathInterface                 The math implementation instance
     */
    private function getMathExtension(): MathInterface
    {
        if (extension_loaded('gmp')) {
            return new Gmp();
        }

        if (extension_loaded('bcmath')) {
            return new BCMath();
        }

        throw MissingMathExtensionException::forComponent('Hashids');
    }

    /**
     * Split a multibyte UTF-8 string into an array of individual characters.
     *
     * Uses Unicode-aware regex splitting to correctly handle multibyte characters.
     *
     * @param string $string The string to split
     *
     * @return array<int, string> Array of individual characters
     */
    private function multiByteSplit(string $string): array
    {
        return preg_split('/(?!^)(?=.)/u', $string) ?: [];
    }
}
