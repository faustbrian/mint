<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Generators;

use Cline\Mint\Algorithms\TimeflakeAlgorithm;
use Cline\Mint\Contracts\GeneratorInterface;
use Cline\Mint\Support\Identifiers\Timeflake;
use Override;

/**
 * Timeflake generator.
 *
 * Generates 128-bit identifiers similar to ULID and UUIDv7. Timeflakes combine
 * a 48-bit timestamp with 80 bits of randomness, providing both chronological
 * sorting and collision resistance. Unlike UUIDs, Timeflakes support multiple
 * encoding formats for flexibility.
 *
 * Structure (16 bytes):
 * - 6 bytes: Timestamp (milliseconds since Unix epoch, big-endian)
 * - 10 bytes: Cryptographically secure random data
 *
 * Encoding formats:
 * - Base62: Compact URL-safe representation (~22 characters)
 * - Hexadecimal: UUID-compatible format (32 characters)
 * - Raw bytes: 16-byte binary format
 *
 * ```php
 * $generator = new TimeflakeGenerator();
 * $timeflake = $generator->generate(); // Base62 encoded
 * echo $timeflake->toString(); // e.g., "3jKl9j3KLM2kf9j3KL"
 *
 * $hexTimeflake = $generator->generateHex(); // Hex encoded
 * echo $hexTimeflake->toString(); // e.g., "0185e5f3c...4d2"
 * ```
 *
 * @api
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class TimeflakeGenerator implements GeneratorInterface
{
    /**
     * The Timeflake algorithm instance.
     */
    private TimeflakeAlgorithm $algorithm;

    /**
     * Create a new Timeflake generator instance.
     */
    public function __construct()
    {
        $this->algorithm = new TimeflakeAlgorithm();
    }

    /**
     * Generate a new Timeflake.
     */
    #[Override()]
    public function generate(): Timeflake
    {
        $data = $this->algorithm->generate();

        return new Timeflake($data['value'], $data['bytes']);
    }

    /**
     * Generate a Timeflake from a specific timestamp.
     *
     * @param int $timestamp Unix timestamp in milliseconds
     */
    public function fromTimestamp(int $timestamp): Timeflake
    {
        $data = $this->algorithm->generateFromTimestamp($timestamp);

        return new Timeflake($data['value'], $data['bytes']);
    }

    /**
     * Generate a Timeflake and return as hex string.
     */
    public function generateHex(): Timeflake
    {
        $data = $this->algorithm->generateHex();

        return new Timeflake($data['value'], $data['bytes']);
    }

    /**
     * Parse a Timeflake string (base62 or hex).
     */
    #[Override()]
    public function parse(string $value): Timeflake
    {
        $data = $this->algorithm->parse($value);

        return new Timeflake($data['value'], $data['bytes']);
    }

    /**
     * Check if a string is a valid Timeflake.
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        return $this->algorithm->isValid($value);
    }

    /**
     * Get the generator name.
     */
    #[Override()]
    public function getName(): string
    {
        return 'timeflake';
    }
}
