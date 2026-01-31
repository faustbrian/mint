<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms\Sqids;

use Cline\Mint\Exceptions\AlphabetContainsDuplicatesException;
use Cline\Mint\Exceptions\AlphabetContainsMultibyteException;
use Cline\Mint\Exceptions\AlphabetTooShortException;
use Cline\Mint\Exceptions\MaxRegenerationAttemptsException;
use Cline\Mint\Exceptions\MinLengthOutOfRangeException;
use Cline\Mint\Exceptions\MissingMathExtensionException;
use Cline\Mint\Exceptions\NumberOutOfRangeException;
use Cline\Mint\Support\Math\BCMath;
use Cline\Mint\Support\Math\Gmp;
use Cline\Mint\Support\Math\MathInterface;

use const PHP_INT_MAX;

use function array_key_exists;
use function count;
use function explode;
use function extension_loaded;
use function implode;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function min;
use function ord;
use function preg_match;
use function preg_quote;
use function strrev;
use function strtr;

/**
 * Sqids encoder/decoder for generating short, unique, URL-safe IDs.
 *
 * Sqids (pronounced "squids") generates short, unique, URL-safe identifiers
 * from integer arrays. Unlike Hashids, Sqids includes a profanity filter
 * (blocklist) to prevent offensive words in generated IDs.
 *
 * Key features:
 * - URL-safe: uses alphanumeric characters only
 * - Customizable alphabet and minimum length
 * - Built-in profanity filter with configurable blocklist
 * - Deterministic: same input always produces same output
 * - No sequential patterns (unlike database auto-increment IDs)
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://sqids.org/
 */
final class Sqids implements SqidsInterface
{
    /**
     * Default alphabet for encoding (alphanumeric characters).
     */
    public const string DEFAULT_ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * Default minimum length for generated IDs (no minimum).
     */
    public const int DEFAULT_MIN_LENGTH = 0;

    /**
     * Default profanity blocklist.
     *
     * Contains offensive words across multiple languages that will be
     * filtered from generated IDs. IDs matching these patterns are
     * regenerated with a different configuration.
     */
    public const array DEFAULT_BLOCKLIST = [
        'aand',
        'ahole',
        'allupato',
        'anal',
        'anale',
        'anus',
        'arrapato',
        'arsch',
        'arse',
        'ass',
        'balatkar',
        'bastardo',
        'battona',
        'bitch',
        'bite',
        'bitte',
        'boceta',
        'boiata',
        'boob',
        'boobe',
        'bosta',
        'branlage',
        'branler',
        'branlette',
        'branleur',
        'branleuse',
        'cabrao',
        'cabron',
        'caca',
        'cacca',
        'cacete',
        'cagante',
        'cagar',
        'cagare',
        'cagna',
        'caraculo',
        'caralho',
        'cazzata',
        'cazzimma',
        'cazzo',
        'chatte',
        'chiasse',
        'chiavata',
        'chier',
        'chingadazos',
        'chingaderita',
        'chingar',
        'chingo',
        'chingues',
        'chink',
        'chod',
        'chootia',
        'chootiya',
        'clit',
        'clito',
        'cock',
        'coglione',
        'cona',
        'connard',
        'connasse',
        'conne',
        'couilles',
        'cracker',
        'crap',
        'culattone',
        'culero',
        'culo',
        'cum',
        'cunt',
        'damn',
        'deich',
        'depp',
        'dick',
        'dildo',
        'dyke',
        'encule',
        'enema',
        'enfoire',
        'estupido',
        'etron',
        'fag',
        'fica',
        'ficker',
        'figa',
        'foda',
        'foder',
        'fottere',
        'fottersi',
        'fotze',
        'foutre',
        'frocio',
        'froscio',
        'fuck',
        'gandu',
        'goo',
        'gouine',
        'grognasse',
        'harami',
        'haramzade',
        'hundin',
        'idiot',
        'imbecile',
        'jerk',
        'jizz',
        'kamine',
        'kike',
        'leccaculo',
        'mamahuevo',
        'mamon',
        'masturbate',
        'masturbation',
        'merda',
        'merde',
        'merdoso',
        'mierda',
        'mignotta',
        'minchia',
        'mist',
        'muschi',
        'neger',
        'negre',
        'negro',
        'nerchia',
        'nigger',
        'orgasm',
        'palle',
        'paneleiro',
        'patakha',
        'pecorina',
        'pendejo',
        'penis',
        'pipi',
        'pirla',
        'piscio',
        'pisser',
        'polla',
        'pompino',
        'poop',
        'porca',
        'porn',
        'porra',
        'pouffiasse',
        'prick',
        'pussy',
        'puta',
        'putain',
        'pute',
        'putiza',
        'puttana',
        'queca',
        'randi',
        'rape',
        'recchione',
        'retard',
        'rompiballe',
        'ruffiano',
        'sacanagem',
        'salaud',
        'salope',
        'saugnapf',
        'sbattere',
        'sbattersi',
        'sborra',
        'sborrone',
        'scheise',
        'scheisse',
        'schlampe',
        'schwachsinnig',
        'schwanz',
        'scopare',
        'scopata',
        'sexy',
        'shit',
        'slut',
        'spompinare',
        'stronza',
        'stronzo',
        'stupid',
        'succhiami',
        'sucker',
        'tapette',
        'testicle',
        'tette',
        'topa',
        'tringler',
        'troia',
        'trombare',
        'turd',
        'twat',
        'vaffanculo',
        'vagina',
        'verdammt',
        'verga',
        'wank',
        'wichsen',
        'xana',
        'xochota',
        'zizi',
        'zoccola',
    ];

    /**
     * Maximum allowed minimum length for generated IDs.
     */
    private const int MIN_LENGTH_LIMIT = 255;

    /**
     * Leetspeak character replacements for blocklist regex.
     *
     * Used to catch leet variations of blocked words (e.g., "b1tch" for "bitch").
     */
    private const array LEET = [
        'i' => '[i1]',
        'o' => '[o0]',
        'l' => '[l1]',
    ];

    /**
     * Math implementation for arbitrary-precision arithmetic (GMP or BCMath).
     */
    private readonly MathInterface $math;

    /**
     * Compiled regex pattern for blocklist matching.
     */
    private readonly ?string $blocklistRegex;

    /**
     * Create a new Sqids encoder/decoder instance.
     *
     * Initializes the encoder with custom alphabet, minimum length, and blocklist.
     * The alphabet is shuffled for security and validated for correctness.
     *
     * @param string                    $alphabet  Character set for encoding. Must be at least 3 unique single-byte
     *                                             characters without duplicates. Defaults to alphanumeric characters.
     * @param int                       $minLength Minimum length for generated IDs. IDs shorter than this are padded.
     *                                             Must be between 0 and 255. Defaults to 0 (no minimum).
     * @param array<int|string, string> $blocklist Array of words to filter from generated IDs. IDs matching
     *                                             these patterns are regenerated. Defaults to built-in profanity list.
     *
     * @throws AlphabetContainsDuplicatesException If alphabet contains duplicate characters
     * @throws AlphabetContainsMultibyteException  If alphabet contains multibyte UTF-8 characters
     * @throws AlphabetTooShortException           If alphabet contains fewer than 3 characters
     * @throws MinLengthOutOfRangeException        If minLength is negative or exceeds 255
     * @throws MissingMathExtensionException       If neither GMP nor BCMath extension is available
     */
    public function __construct(
        private string $alphabet = self::DEFAULT_ALPHABET,
        private readonly int $minLength = self::DEFAULT_MIN_LENGTH,
        private readonly array $blocklist = self::DEFAULT_BLOCKLIST,
    ) {
        $this->math = $this->getMathExtension();

        if ($alphabet === '') {
            $alphabet = self::DEFAULT_ALPHABET;
        }

        if (mb_strlen($alphabet, '8bit') !== mb_strlen($alphabet)) {
            throw AlphabetContainsMultibyteException::create();
        }

        if (mb_strlen($alphabet) < 3) {
            throw AlphabetTooShortException::forMinimum(3);
        }

        if (preg_match('/(.).*\1/', $alphabet)) {
            throw AlphabetContainsDuplicatesException::create();
        }

        if ($minLength < 0 || $minLength > self::MIN_LENGTH_LIMIT) {
            throw MinLengthOutOfRangeException::forRange(0, self::MIN_LENGTH_LIMIT);
        }

        $this->blocklistRegex = $this->buildBlocklistRegex();
        $this->alphabet = $this->shuffle($alphabet);
    }

    /**
     * Encode an array of unsigned integers into an ID.
     *
     * Generates a short, unique ID from the provided integers. If the generated
     * ID matches a blocklist entry, the ID is regenerated with a modified offset
     * until a non-blocked ID is produced or the maximum regeneration attempts
     * (alphabet length + 1) is reached.
     *
     * @param array<int> $numbers Non-negative integers to encode (must be 0 <= n <= maxValue())
     *
     * @throws MaxRegenerationAttemptsException If unable to generate non-blocked ID after maximum attempts
     * @throws NumberOutOfRangeException        If any number is negative or exceeds maxValue()
     * @return string                           Generated ID string, or empty string if input is empty
     */
    public function encode(array $numbers): string
    {
        if ($numbers === []) {
            return '';
        }

        foreach ($numbers as $n) {
            if ($n < 0 || $n > $this->maxValue()) {
                throw NumberOutOfRangeException::forMaxValue($this->maxValue());
            }
        }

        return $this->encodeNumbers($numbers);
    }

    /**
     * Decode an ID back into an array of unsigned integers.
     *
     * Reverses the encoding process to recover the original integer values.
     * Returns empty array if the ID is invalid (empty, contains non-alphabet
     * characters, or cannot be decoded).
     *
     * @param string $id The encoded ID string to decode
     *
     * @return array<int> Array of decoded integers (empty array if decoding fails)
     */
    public function decode(string $id): array
    {
        $ret = [];

        if ($id === '') {
            return $ret;
        }

        if (!preg_match('/^['.preg_quote($this->alphabet, '/').']+$/', $id)) {
            return $ret;
        }

        $prefix = $id[0];
        $offset = mb_strpos($this->alphabet, $prefix);

        if ($offset === false) {
            return $ret;
        }

        $alphabet = mb_substr($this->alphabet, $offset).mb_substr($this->alphabet, 0, $offset);
        $alphabet = strrev($alphabet);

        $id = mb_substr($id, 1);

        while ($id !== '') {
            $separator = $alphabet[0];

            $chunks = explode($separator, $id, 2);

            if ($chunks[0] === '') {
                return $ret;
            }

            $ret[] = $this->toNumber($chunks[0], mb_substr($alphabet, 1));

            if (array_key_exists(1, $chunks)) {
                $alphabet = $this->shuffle($alphabet);
            }

            $id = $chunks[1] ?? '';
        }

        return $ret;
    }

    /**
     * Get the maximum integer value that can be encoded.
     *
     * @return int PHP's maximum integer value (platform-dependent)
     */
    private function maxValue(): int
    {
        return PHP_INT_MAX;
    }

    /**
     * Internal encoding function with blocklist regeneration support.
     *
     * Recursively generates IDs, incrementing the offset on each attempt
     * if the generated ID matches a blocklist pattern. This ensures
     * profanity-free IDs while maintaining deterministic output.
     *
     * @param array<int> $numbers   Non-negative integers to encode
     * @param int        $increment Regeneration attempt counter (0-based offset modifier)
     *
     * @throws MaxRegenerationAttemptsException If regeneration attempts exceed alphabet length
     * @return string                           The generated ID
     */
    private function encodeNumbers(array $numbers, int $increment = 0): string
    {
        if ($increment > mb_strlen($this->alphabet)) {
            throw MaxRegenerationAttemptsException::create();
        }

        $offset = count($numbers);

        foreach ($numbers as $i => $v) {
            $offset += ord($this->alphabet[$v % mb_strlen($this->alphabet)]) + $i;
        }

        $offset %= mb_strlen($this->alphabet);
        $offset = ($offset + $increment) % mb_strlen($this->alphabet);

        $alphabet = mb_substr($this->alphabet, $offset).mb_substr($this->alphabet, 0, $offset);
        $prefix = $alphabet[0];
        $alphabet = strrev($alphabet);
        $id = $prefix;

        for ($i = 0; $i !== count($numbers); ++$i) {
            $num = $numbers[$i];

            $id .= $this->toId($num, mb_substr($alphabet, 1));

            if ($i >= count($numbers) - 1) {
                continue;
            }

            $id .= $alphabet[0];
            $alphabet = $this->shuffle($alphabet);
        }

        if ($this->minLength > mb_strlen($id)) {
            $id .= $alphabet[0];

            while (mb_strlen($id) < $this->minLength) {
                $alphabet = $this->shuffle($alphabet);
                $id .= mb_substr($alphabet, 0, min($this->minLength - mb_strlen($id), mb_strlen($this->alphabet)));
            }
        }

        if ($this->isBlockedId($id)) {
            return $this->encodeNumbers($numbers, $increment + 1);
        }

        return $id;
    }

    /**
     * Shuffle the alphabet using a consistent deterministic algorithm.
     *
     * Implements a bidirectional shuffle that produces the same result
     * for the same input alphabet, ensuring encoding consistency.
     *
     * @param string $alphabet The alphabet to shuffle
     *
     * @return string The shuffled alphabet
     */
    private function shuffle(string $alphabet): string
    {
        for ($i = 0, $j = mb_strlen($alphabet) - 1; $j > 0; $i++, $j--) {
            $r = ($i * $j + ord($alphabet[$i]) + ord($alphabet[$j])) % mb_strlen($alphabet);
            [$alphabet[$i], $alphabet[$r]] = [$alphabet[$r], $alphabet[$i]];
        }

        return $alphabet;
    }

    /**
     * Convert an integer to ID string using base conversion.
     *
     * Encodes the number to a custom base defined by the alphabet length.
     *
     * @param int    $num      The integer to encode
     * @param string $alphabet The alphabet for base conversion
     *
     * @return string The encoded ID segment
     */
    private function toId(int $num, string $alphabet): string
    {
        $id = '';

        do {
            $id = $alphabet[$this->math->intval($this->math->mod($num, mb_strlen($alphabet)))].$id;
            $num = $this->math->divide($num, mb_strlen($alphabet));
        } while ($this->math->greaterThan($num, 0));

        return $id;
    }

    /**
     * Convert ID string segment back to integer using base conversion.
     *
     * Reverses the toId() operation by converting from custom alphabet base.
     *
     * @param string $id       The ID segment to decode
     * @param string $alphabet The alphabet used for encoding
     *
     * @return int The decoded integer
     */
    private function toNumber(string $id, string $alphabet): int
    {
        $number = 0;

        for ($i = 0; $i < mb_strlen($id); ++$i) {
            $pos = mb_strpos($alphabet, $id[$i]);

            if ($pos === false) {
                continue;
            }

            $number = $this->math->add(
                $this->math->multiply($number, mb_strlen($alphabet)),
                $pos,
            );
        }

        return $this->math->intval($number);
    }

    /**
     * Check if an ID matches any blocklist pattern.
     *
     * @param string $id The ID to check
     *
     * @return bool True if the ID matches a blocked pattern
     */
    private function isBlockedId(string $id): bool
    {
        return $this->blocklistRegex !== null && preg_match($this->blocklistRegex, $id);
    }

    /**
     * Get the best available arbitrary-precision math extension.
     *
     * Prefers GMP over BCMath for better performance.
     *
     * @throws MissingMathExtensionException If neither GMP nor BCMath is available
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

        throw MissingMathExtensionException::forComponent('Sqids');
    }

    /**
     * Build a compiled regex pattern from the blocklist.
     *
     * Creates an optimized regex that matches blocklist words with different
     * strategies based on word length and character patterns:
     * - Short words (<=3 chars): exact match only
     * - Words with digits or leetspeak: match at beginning/end
     * - Other words: match anywhere in ID
     *
     * @return null|string Compiled regex pattern, or null if blocklist is empty
     */
    private function buildBlocklistRegex(): ?string
    {
        $wordsMatchingExactly = [];
        $wordsMatchingBeginningOrEnd = [];
        $wordMatchingAnywhere = [];

        foreach ($this->blocklist as $word) {
            $word = (string) $word;

            if (mb_strlen($word) <= 3) {
                $wordsMatchingExactly[] = preg_quote($word, '/');
            } else {
                $word = preg_quote($word, '/');
                $leet = strtr($word, self::LEET);

                if (!preg_match('/\d/', $word)) {
                    $wordMatchingAnywhere[] = $word;
                } elseif ($leet === $word) {
                    $wordsMatchingBeginningOrEnd[] = $word;
                }

                if ($leet !== $word) {
                    $wordsMatchingBeginningOrEnd[] = $leet;
                }
            }
        }

        $regexParts = [];

        if ($wordsMatchingExactly !== []) {
            $regexParts[] = '^('.implode('|', $wordsMatchingExactly).')$';
        }

        if ($wordsMatchingBeginningOrEnd !== []) {
            $regexParts[] = '^('.implode('|', $wordsMatchingBeginningOrEnd).')';
            $regexParts[] = '('.implode('|', $wordsMatchingBeginningOrEnd).')$';
        }

        if ($wordMatchingAnywhere !== []) {
            $regexParts[] = '('.implode('|', $wordMatchingAnywhere).')';
        }

        if ($regexParts !== []) {
            return '/('.implode('|', $regexParts).')/i';
        }

        return null;
    }
}
