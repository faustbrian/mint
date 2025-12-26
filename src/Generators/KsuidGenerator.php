<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Generators;

use Cline\Mint\Algorithms\KsuidAlgorithm;
use Cline\Mint\Contracts\GeneratorInterface;
use Cline\Mint\Exceptions\InvalidKsuidFormatException;
use Cline\Mint\Support\Identifiers\Ksuid;
use Override;

/**
 * KSUID (K-Sortable Unique IDentifier) generator.
 *
 * Generates 160-bit identifiers with 32-bit timestamp and 128-bit random payload.
 * KSUIDs are lexicographically sortable by generation time, making them ideal for
 * distributed systems where chronological ordering is important. The timestamp
 * component uses a custom epoch (2014-05-13T16:53:20Z) to maximize the usable
 * date range.
 *
 * Encoded as 27 base62 characters (0-9, A-Z, a-z), providing excellent density
 * while remaining URL-safe and case-sensitive.
 *
 * ```php
 * $generator = new KsuidGenerator();
 * $ksuid = $generator->generate();
 * echo $ksuid->toString(); // e.g., "0ujsszwN8NRY24YaXiTIE2VWDTS"
 * ```
 *
 * @api
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/segmentio/ksuid
 * @psalm-immutable
 */
final readonly class KsuidGenerator implements GeneratorInterface
{
    /**
     * The underlying KSUID algorithm implementation.
     */
    private KsuidAlgorithm $algorithm;

    /**
     * Create a new KSUID generator instance.
     *
     * @param int $epoch Custom epoch timestamp in seconds (default: KSUID epoch 1400000000)
     */
    public function __construct(int $epoch = Ksuid::EPOCH)
    {
        $this->algorithm = new KsuidAlgorithm($epoch);
    }

    /**
     * Generate a new KSUID.
     *
     * Creates a KSUID using the current timestamp and cryptographically secure
     * random bytes for the payload. The timestamp is adjusted relative to the
     * KSUID epoch to maximize the usable date range.
     *
     * @return Ksuid The generated KSUID instance
     */
    #[Override()]
    public function generate(): Ksuid
    {
        $result = $this->algorithm->generate();

        return new Ksuid($result['value'], $result['bytes']);
    }

    /**
     * Generate a KSUID from a specific timestamp.
     *
     * Useful for backfilling historical data or testing with deterministic timestamps
     * while maintaining randomness in the payload portion.
     *
     * @param int $timestamp Unix timestamp in seconds (standard Unix epoch, not KSUID epoch)
     *
     * @return Ksuid The generated KSUID instance with the specified timestamp
     */
    public function fromTimestamp(int $timestamp): Ksuid
    {
        $result = $this->algorithm->fromTimestamp($timestamp);

        return new Ksuid($result['value'], $result['bytes']);
    }

    /**
     * Parse a KSUID string.
     *
     * Validates and decodes a KSUID string back into its component parts,
     * extracting the timestamp and random payload for inspection.
     *
     * @param string $value The KSUID string to parse (must be exactly 27 base62 characters)
     *
     * @throws InvalidKsuidFormatException When the value is not a valid KSUID format
     * @return Ksuid                       The parsed KSUID instance
     */
    #[Override()]
    public function parse(string $value): Ksuid
    {
        $result = $this->algorithm->parse($value);

        return new Ksuid($result['value'], $result['bytes']);
    }

    /**
     * Check if a string is a valid KSUID.
     *
     * Validates both the length and character set. A valid KSUID must be exactly
     * 27 characters long and contain only base62 characters (0-9, A-Z, a-z).
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid KSUID format
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        return $this->algorithm->isValid($value);
    }

    /**
     * Get the generator name.
     *
     * @return string The identifier 'ksuid'
     */
    #[Override()]
    public function getName(): string
    {
        return 'ksuid';
    }

    /**
     * Get the minimum KSUID (all zeros in payload).
     *
     * Returns the smallest possible KSUID value, useful for range queries or
     * establishing boundaries in distributed systems.
     *
     * @return Ksuid The minimum KSUID (000000000000000000000000000)
     */
    public function min(): Ksuid
    {
        $result = $this->algorithm->min();

        return new Ksuid($result['value'], $result['bytes']);
    }

    /**
     * Get the maximum KSUID (all ones in payload).
     *
     * Returns the largest possible KSUID value, useful for range queries or
     * establishing upper boundaries in distributed systems.
     *
     * @return Ksuid The maximum KSUID (aWgEPTl1tmebfsQzFP4bxwgy80V)
     */
    public function max(): Ksuid
    {
        $result = $this->algorithm->max();

        return new Ksuid($result['value'], $result['bytes']);
    }
}
