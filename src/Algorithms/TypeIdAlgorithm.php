<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms;

use Cline\Mint\Contracts\AlgorithmInterface;
use Cline\Mint\Exceptions\InvalidTypeIdFormatException;
use Override;

use function bindec;
use function chr;
use function mb_strlen;
use function mb_strrpos;
use function mb_substr;
use function microtime;
use function ord;
use function preg_match;
use function random_bytes;
use function sprintf;

/**
 * TypeID algorithm implementation.
 *
 * Generates type-safe, K-sortable identifiers with a prefix.
 * Based on UUIDv7 with a type prefix for better developer experience.
 * Format: prefix_base32suffix (e.g., user_01h455vb4pex5vsknk084sn02q)
 *
 * Compliant with the official TypeID specification.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/jetify-com/typeid/tree/main/spec
 * @psalm-immutable
 */
final readonly class TypeIdAlgorithm implements AlgorithmInterface
{
    /**
     * Base32 alphabet for TypeID (lowercase Crockford without i, l, o, u).
     */
    private const string ALPHABET = '0123456789abcdefghjkmnpqrstvwxyz';

    /**
     * TypeID suffix length (26 characters).
     */
    private const int SUFFIX_LENGTH = 26;

    /**
     * Maximum prefix length.
     */
    private const int MAX_PREFIX_LENGTH = 63;

    /**
     * Pattern for valid prefix (lowercase alpha only, with optional underscores in middle).
     * - Must start with a-z
     * - Can contain a-z and underscore in middle
     * - Must end with a-z (not underscore)
     * - Can also be just a single character a-z
     */
    private const string PREFIX_PATTERN = '/^[a-z]([a-z_]*[a-z])?$/';

    /**
     * Pattern for valid suffix (26 lowercase base32 chars, no i, l, o, u).
     */
    private const string SUFFIX_PATTERN = '/^[0-7][0-9a-hjkmnp-tv-z]{25}$/';

    /**
     * Create a new TypeID algorithm instance.
     *
     * @param string $prefix The type prefix (e.g., 'user', 'order', 'post')
     */
    public function __construct(
        private string $prefix = '',
    ) {}

    /**
     * Generate raw TypeID data.
     *
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function generate(): array
    {
        // Generate UUIDv7 bytes
        $bytes = $this->generateUuidV7Bytes();

        // Encode to TypeID base32
        $suffix = $this->encodeBase32($bytes);

        // Build the full TypeID
        $value = $this->prefix === ''
            ? $suffix
            : $this->prefix.'_'.$suffix;

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Parse a TypeID string into raw data.
     *
     * @param string $value The TypeID string
     *
     * @throws InvalidTypeIdFormatException        When the value is not valid
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function parse(string $value): array
    {
        if (!$this->isValid($value)) {
            throw InvalidTypeIdFormatException::forValue($value);
        }

        ['suffix' => $suffix] = $this->parseTypeIdString($value);
        $bytes = $this->decodeBase32($suffix);

        return [
            'value' => $value,
            'bytes' => $bytes,
        ];
    }

    /**
     * Check if a string is a valid TypeID.
     *
     * Strict validation: does NOT normalize case.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid TypeID format
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        // Empty string is invalid
        if ($value === '') {
            return false;
        }

        // Find the last underscore to split prefix and suffix
        $underscorePos = mb_strrpos($value, '_');

        if ($underscorePos === false) {
            // No prefix, just validate suffix (must be exactly 26 chars)
            return $this->isValidSuffix($value);
        }

        // Underscore at position 0 means empty prefix with separator (invalid)
        if ($underscorePos === 0) {
            return false;
        }

        $prefix = mb_substr($value, 0, $underscorePos);
        $suffix = mb_substr($value, $underscorePos + 1);

        // Empty suffix is invalid
        if ($suffix === '') {
            return false;
        }

        return $this->isValidPrefix($prefix) && $this->isValidSuffix($suffix);
    }

    /**
     * Get the configured prefix.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Validate a prefix according to the spec.
     */
    private function isValidPrefix(string $prefix): bool
    {
        $length = mb_strlen($prefix);

        // Max 63 chars
        if ($length > self::MAX_PREFIX_LENGTH) {
            return false;
        }

        // Must match pattern (lowercase a-z, underscores allowed in middle only)
        return preg_match(self::PREFIX_PATTERN, $prefix) === 1;
    }

    /**
     * Validate a suffix according to the spec.
     */
    private function isValidSuffix(string $suffix): bool
    {
        // Must be exactly 26 characters
        if (mb_strlen($suffix) !== self::SUFFIX_LENGTH) {
            return false;
        }

        // Must match pattern and first char must be 0-7 (to prevent overflow)
        return preg_match(self::SUFFIX_PATTERN, $suffix) === 1;
    }

    /**
     * Parse a TypeID string into prefix and suffix.
     *
     * @return array{prefix: string, suffix: string}
     */
    private function parseTypeIdString(string $value): array
    {
        $underscorePos = mb_strrpos($value, '_');

        if ($underscorePos === false) {
            return ['prefix' => '', 'suffix' => $value];
        }

        return [
            'prefix' => mb_substr($value, 0, $underscorePos),
            'suffix' => mb_substr($value, $underscorePos + 1),
        ];
    }

    /**
     * Generate UUIDv7 bytes.
     */
    private function generateUuidV7Bytes(): string
    {
        // 48-bit Unix timestamp in milliseconds
        $timestamp = (int) (microtime(true) * 1_000);

        // Build timestamp bytes (6 bytes)
        $timestampBytes = '';

        for ($i = 5; $i >= 0; --$i) {
            $timestampBytes .= chr(($timestamp >> ($i * 8)) & 0xFF);
        }

        // Random bytes (10 bytes)
        $random = random_bytes(10);

        // Set version (7) in bits 48-51
        $random[0] = chr((ord($random[0]) & 0x0F) | 0x70);
        // Set variant (10xx) in bits 64-65
        $random[2] = chr((ord($random[2]) & 0x3F) | 0x80);

        return $timestampBytes.$random;
    }

    /**
     * Encode 16 bytes to TypeID base32 (26 characters).
     *
     * Uses the TypeID-specific encoding as per the spec.
     */
    private function encodeBase32(string $bytes): string
    {
        // TypeID uses a specific encoding scheme for 128 bits -> 26 chars
        // Each group of 5 bits maps to one character
        // 128 bits = 26 * 5 - 2 = 128 bits (first char uses only 3 bits)
        $result = '';

        // First character uses top 3 bits of first byte (128 - 125 = 3 bits)
        $result .= self::ALPHABET[(ord($bytes[0]) >> 5) & 0x07];

        // Remaining 25 characters use 5 bits each
        $buffer = ord($bytes[0]) & 0x1F;
        $bitsLeft = 5;

        for ($i = 1; $i < 16; ++$i) {
            $buffer = ($buffer << 8) | ord($bytes[$i]);
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $result .= self::ALPHABET[($buffer >> $bitsLeft) & 0x1F];
            }
        }

        // Handle any remaining bits
        if ($bitsLeft > 0) {
            $result .= self::ALPHABET[($buffer << (5 - $bitsLeft)) & 0x1F];
        }

        return $result;
    }

    /**
     * Decode TypeID base32 to 16 bytes.
     *
     * Uses the TypeID-specific decoding scheme.
     * 26 chars * 5 bits = 130 bits, first 2 bits are padding.
     */
    private function decodeBase32(string $value): string
    {
        // Create reverse lookup
        /** @var null|array<string, int> */
        static $lookup = null;

        if ($lookup === null) {
            $lookup = [];

            for ($i = 0; $i < 32; ++$i) {
                $char = self::ALPHABET[$i];
                $lookup[$char] = $i;
            }
        }

        // Build all 130 bits from 26 chars
        $buffer = '';

        for ($i = 0; $i < 26; ++$i) {
            $char = $value[$i];
            $charValue = $lookup[$char] ?? 0;
            $buffer .= sprintf('%05b', $charValue);
        }

        // Remove first 2 bits (padding) to get 128 bits
        $buffer = mb_substr($buffer, 2);

        // Convert to 16 bytes
        $result = '';

        for ($i = 0; $i < 128; $i += 8) {
            $result .= chr((int) bindec(mb_substr($buffer, $i, 8)));
        }

        return $result;
    }
}
