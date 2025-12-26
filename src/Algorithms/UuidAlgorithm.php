<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms;

use Cline\Mint\Contracts\AlgorithmInterface;
use Cline\Mint\Enums\UuidVersion;
use Cline\Mint\Exceptions\InvalidUuidFormatException;
use Override;
use RuntimeException;

use const STR_PAD_LEFT;

use function bin2hex;
use function chr;
use function dechex;
use function hex2bin;
use function hexdec;
use function mb_str_pad;
use function mb_strtolower;
use function mb_substr;
use function md5;
use function microtime;
use function ord;
use function preg_match;
use function random_bytes;
use function random_int;
use function sha1;
use function sprintf;
use function str_replace;

/**
 * UUID algorithm implementation.
 *
 * Generates UUIDs (Universally Unique Identifiers) following RFC 4122 specification
 * with support for versions 1, 3, 4, 5, 6, 7, and 8. Each version uses different
 * generation strategies optimized for specific use cases.
 *
 * Structure (128 bits):
 * - 32 bits: time_low or random data
 * - 16 bits: time_mid or random data
 * - 16 bits: time_hi_and_version (includes 4-bit version)
 * - 16 bits: clock_seq_hi_and_reserved (includes 2-bit variant) + clock_seq_low
 * - 48 bits: node or random data
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class UuidAlgorithm implements AlgorithmInterface
{
    /**
     * DNS namespace UUID.
     */
    public const string NAMESPACE_DNS = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    /**
     * URL namespace UUID.
     */
    public const string NAMESPACE_URL = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';

    /**
     * OID namespace UUID.
     */
    public const string NAMESPACE_OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';

    /**
     * X.500 DN namespace UUID.
     */
    public const string NAMESPACE_X500 = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';

    /**
     * UUID string pattern for validation.
     */
    private const string PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    /**
     * Create a new UUID algorithm instance.
     *
     * @param UuidVersion $version   The UUID version to generate. Defaults to v7 (recommended
     *                               for modern applications with millisecond-precision timestamps
     *                               and lexicographic sorting capabilities). Version 4 provides
     *                               maximum randomness, while versions 3 and 5 generate
     *                               deterministic name-based UUIDs.
     * @param null|string $namespace Namespace UUID for v3/v5 generation. Must be a valid UUID
     *                               string (predefined constants available: NAMESPACE_DNS,
     *                               NAMESPACE_URL, NAMESPACE_OID, NAMESPACE_X500). Only used
     *                               when generating v3 or v5 UUIDs. Ignored for other versions.
     * @param null|string $name      Name value to hash with namespace for v3/v5 generation.
     *                               Combined with namespace UUID to produce deterministic output.
     *                               Only used when generating v3 or v5 UUIDs. Ignored for other
     *                               versions.
     */
    public function __construct(
        private UuidVersion $version = UuidVersion::V7,
        private ?string $namespace = null,
        private ?string $name = null,
    ) {}

    /**
     * Generate raw UUID data.
     *
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function generate(): array
    {
        return match ($this->version) {
            UuidVersion::V1 => $this->generateV1(),
            UuidVersion::V3 => $this->generateV3(),
            UuidVersion::V4 => $this->generateV4(),
            UuidVersion::V5 => $this->generateV5(),
            UuidVersion::V6 => $this->generateV6(),
            UuidVersion::V7 => $this->generateV7(),
            UuidVersion::V8 => $this->generateV8(),
        };
    }

    /**
     * Parse a UUID string into raw data.
     *
     * @param string $value The UUID string to parse
     *
     * @throws InvalidUuidFormatException          When the value is not valid
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function parse(string $value): array
    {
        if (!$this->isValid($value)) {
            throw InvalidUuidFormatException::forValue($value);
        }

        $hex = str_replace('-', '', $value);
        $bytes = hex2bin($hex) ?: throw new RuntimeException('Invalid hex string');

        return [
            'value' => mb_strtolower($value),
            'bytes' => $bytes,
        ];
    }

    /**
     * Check if a string is a valid UUID.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid UUID format
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        return preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * Get the configured UUID version.
     */
    public function getVersion(): UuidVersion
    {
        return $this->version;
    }

    /**
     * Detect the UUID version from a string.
     *
     * @param string $value The UUID string to analyze
     *
     * @return UuidVersion The detected UUID version
     */
    public function detectVersion(string $value): UuidVersion
    {
        $hex = str_replace('-', '', $value);
        $versionNibble = (int) hexdec($hex[12]);

        return match ($versionNibble) {
            1 => UuidVersion::V1,
            3 => UuidVersion::V3,
            4 => UuidVersion::V4,
            5 => UuidVersion::V5,
            6 => UuidVersion::V6,
            7 => UuidVersion::V7,
            8 => UuidVersion::V8,
            default => UuidVersion::V4,
        };
    }

    /**
     * Generate UUID version 1 (time-based with MAC address simulation).
     *
     * @return array{value: string, bytes: string}
     */
    private function generateV1(): array
    {
        // Get current timestamp in 100-nanosecond intervals since Oct 15, 1582
        $time = (int) (microtime(true) * 10_000_000) + 0x01_B2_1D_D2_13_81_40_00;

        $timeLow = $time & 0xFF_FF_FF_FF;
        $timeMid = ($time >> 32) & 0xFF_FF;
        $timeHi = ($time >> 48) & 0x0F_FF;
        $timeHi |= 0x10_00; // Version 1

        $clockSeq = random_int(0, 0x3F_FF);
        $clockSeq |= 0x80_00; // Variant

        // Random node (MAC substitute)
        $node = random_bytes(6);
        $node[0] = chr(ord($node[0]) | 0x01); // Set multicast bit

        $hex = sprintf(
            '%08x-%04x-%04x-%04x-%s',
            $timeLow,
            $timeMid,
            $timeHi,
            $clockSeq,
            bin2hex($node),
        );

        $bytes = hex2bin(str_replace('-', '', $hex)) ?: throw new RuntimeException('Invalid hex string');

        return [
            'value' => $hex,
            'bytes' => $bytes,
        ];
    }

    /**
     * Generate UUID version 3 (name-based with MD5 hashing).
     *
     * @return array{value: string, bytes: string}
     */
    private function generateV3(): array
    {
        $namespaceBytes = hex2bin(str_replace('-', '', $this->namespace ?? self::NAMESPACE_DNS)) ?: throw new RuntimeException('Invalid namespace hex string');
        $hash = md5($namespaceBytes.($this->name ?? ''));

        // Set version (3) and variant
        $hash = mb_substr($hash, 0, 12).'3'.mb_substr($hash, 13);
        $hash = mb_substr($hash, 0, 16).dechex((hexdec(mb_substr($hash, 16, 1)) & 0x3) | 0x8).mb_substr($hash, 17);

        $hex = sprintf(
            '%s-%s-%s-%s-%s',
            mb_substr($hash, 0, 8),
            mb_substr($hash, 8, 4),
            mb_substr($hash, 12, 4),
            mb_substr($hash, 16, 4),
            mb_substr($hash, 20, 12),
        );

        return [
            'value' => $hex,
            'bytes' => hex2bin($hash) ?: throw new RuntimeException('Invalid hash hex string'),
        ];
    }

    /**
     * Generate UUID version 4 (randomly generated).
     *
     * @return array{value: string, bytes: string}
     */
    private function generateV4(): array
    {
        $bytes = random_bytes(16);

        // Set version (4)
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        // Set variant (10xx)
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        $hex = bin2hex($bytes);
        $formatted = sprintf(
            '%s-%s-%s-%s-%s',
            mb_substr($hex, 0, 8),
            mb_substr($hex, 8, 4),
            mb_substr($hex, 12, 4),
            mb_substr($hex, 16, 4),
            mb_substr($hex, 20, 12),
        );

        return [
            'value' => $formatted,
            'bytes' => $bytes,
        ];
    }

    /**
     * Generate UUID version 5 (name-based with SHA-1 hashing).
     *
     * @return array{value: string, bytes: string}
     */
    private function generateV5(): array
    {
        $namespaceBytes = hex2bin(str_replace('-', '', $this->namespace ?? self::NAMESPACE_DNS)) ?: throw new RuntimeException('Invalid namespace hex string');
        $hash = sha1($namespaceBytes.($this->name ?? ''));

        // Truncate to 32 chars and set version (5) and variant
        $hash = mb_substr($hash, 0, 32);
        $hash = mb_substr($hash, 0, 12).'5'.mb_substr($hash, 13);
        $hash = mb_substr($hash, 0, 16).dechex((hexdec(mb_substr($hash, 16, 1)) & 0x3) | 0x8).mb_substr($hash, 17);

        $hex = sprintf(
            '%s-%s-%s-%s-%s',
            mb_substr($hash, 0, 8),
            mb_substr($hash, 8, 4),
            mb_substr($hash, 12, 4),
            mb_substr($hash, 16, 4),
            mb_substr($hash, 20, 12),
        );

        return [
            'value' => $hex,
            'bytes' => hex2bin($hash) ?: throw new RuntimeException('Invalid hash hex string'),
        ];
    }

    /**
     * Generate UUID version 6 (reordered time-based for sorting).
     *
     * @return array{value: string, bytes: string}
     */
    private function generateV6(): array
    {
        // Get current timestamp in 100-nanosecond intervals since Oct 15, 1582
        $time = (int) (microtime(true) * 10_000_000) + 0x01_B2_1D_D2_13_81_40_00;

        // Reorder for lexicographic sorting
        $timeHigh = ($time >> 28) & 0xFF_FF_FF_FF;
        $timeMid = ($time >> 12) & 0xFF_FF;
        $timeLow = $time & 0x0F_FF;
        $timeLow |= 0x60_00; // Version 6

        $clockSeq = random_int(0, 0x3F_FF);
        $clockSeq |= 0x80_00; // Variant

        $node = random_bytes(6);

        $hex = sprintf(
            '%08x-%04x-%04x-%04x-%s',
            $timeHigh,
            $timeMid,
            $timeLow,
            $clockSeq,
            bin2hex($node),
        );

        $bytes = hex2bin(str_replace('-', '', $hex)) ?: throw new RuntimeException('Invalid hex string');

        return [
            'value' => $hex,
            'bytes' => $bytes,
        ];
    }

    /**
     * Generate UUID version 7 (Unix Epoch time-based, recommended).
     *
     * @return array{value: string, bytes: string}
     */
    private function generateV7(): array
    {
        // 48-bit Unix timestamp in milliseconds
        $timestamp = (int) (microtime(true) * 1_000);
        $timestampHex = mb_str_pad(dechex($timestamp), 12, '0', STR_PAD_LEFT);

        // Random bits
        $random = random_bytes(10);

        // Set version (7) in bits 48-51
        $random[0] = chr((ord($random[0]) & 0x0F) | 0x70);
        // Set variant (10xx) in bits 64-65
        $random[2] = chr((ord($random[2]) & 0x3F) | 0x80);

        $hex = $timestampHex.bin2hex($random);
        $formatted = sprintf(
            '%s-%s-%s-%s-%s',
            mb_substr($hex, 0, 8),
            mb_substr($hex, 8, 4),
            mb_substr($hex, 12, 4),
            mb_substr($hex, 16, 4),
            mb_substr($hex, 20, 12),
        );

        return [
            'value' => $formatted,
            'bytes' => hex2bin($hex) ?: throw new RuntimeException('Invalid hex string'),
        ];
    }

    /**
     * Generate UUID version 8 (custom/experimental format).
     *
     * @return array{value: string, bytes: string}
     */
    private function generateV8(): array
    {
        // Custom implementation: timestamp + random
        $bytes = random_bytes(16);

        // Set version (8)
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x80);
        // Set variant (10xx)
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        $hex = bin2hex($bytes);
        $formatted = sprintf(
            '%s-%s-%s-%s-%s',
            mb_substr($hex, 0, 8),
            mb_substr($hex, 8, 4),
            mb_substr($hex, 12, 4),
            mb_substr($hex, 16, 4),
            mb_substr($hex, 20, 12),
        );

        return [
            'value' => $formatted,
            'bytes' => $bytes,
        ];
    }
}
