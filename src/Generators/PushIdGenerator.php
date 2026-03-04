<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Generators;

use Cline\Mint\Algorithms\PushIdAlgorithm;
use Cline\Mint\Contracts\GeneratorInterface;
use Cline\Mint\Support\Identifiers\PushId;
use Override;

/**
 * Firebase Push ID generator.
 *
 * Generates 120-bit identifiers used by Firebase Realtime Database for ordered
 * child nodes. Push IDs are designed to be lexicographically sortable while
 * maintaining high collision resistance in distributed environments.
 *
 * Structure (20 characters):
 * - 8 characters: Timestamp (milliseconds since Unix epoch, base64-encoded)
 * - 12 characters: Random data (72 bits of randomness)
 *
 * Uses a custom 64-character alphabet that sorts correctly in lexicographic
 * order when timestamps are encoded. When multiple IDs are generated within
 * the same millisecond, the random portion is incremented monotonically to
 * ensure proper ordering.
 *
 * ```php
 * $generator = new PushIdGenerator();
 * $pushId = $generator->generate();
 * echo $pushId->toString(); // e.g., "-KpqYvCvIpMW8M-YYZF9"
 * ```
 *
 * @api
 * @author Brian Faust <brian@cline.sh>
 * @see https://firebase.googleblog.com/2015/02/the-2120-ways-to-ensure-unique_68.html
 * @psalm-immutable
 */
final readonly class PushIdGenerator implements GeneratorInterface
{
    /**
     * The Push ID algorithm instance.
     */
    private PushIdAlgorithm $algorithm;

    /**
     * Create a new Push ID generator instance.
     */
    public function __construct()
    {
        $this->algorithm = new PushIdAlgorithm();
    }

    /**
     * Generate a new Push ID.
     *
     * Creates a chronologically sortable ID with the current timestamp encoded
     * in the first 8 characters. If multiple IDs are generated in the same
     * millisecond, the random portion is incremented monotonically rather than
     * regenerated, ensuring proper ordering.
     *
     * @return PushId The generated Push ID instance
     */
    #[Override()]
    public function generate(): PushId
    {
        $data = $this->algorithm->generate();

        return new PushId($data['value'], $data['bytes']);
    }

    /**
     * Generate a Push ID from a specific timestamp.
     *
     * Creates a Push ID with a specific timestamp but random payload. Useful
     * for backfilling data or testing. Does not participate in monotonic
     * increment behavior.
     *
     * @param int $timestamp Unix timestamp in milliseconds
     *
     * @return PushId The generated Push ID instance with the specified timestamp
     */
    public function fromTimestamp(int $timestamp): PushId
    {
        $value = $this->algorithm->generateFromTimestamp($timestamp);

        return new PushId($value, $value);
    }

    /**
     * Parse a Push ID string.
     *
     * Validates and wraps a Push ID string. The timestamp can be extracted
     * from the first 8 characters for chronological analysis.
     *
     * @param string $value The Push ID string to parse (must be exactly 20 characters)
     *
     * @return PushId The parsed Push ID instance
     */
    #[Override()]
    public function parse(string $value): PushId
    {
        $data = $this->algorithm->parse($value);

        return new PushId($data['value'], $data['bytes']);
    }

    /**
     * Check if a string is a valid Push ID.
     *
     * Validates that the string is exactly 20 characters and contains only
     * characters from the Push ID alphabet.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid Push ID format
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        return $this->algorithm->isValid($value);
    }

    /**
     * Get the generator name.
     *
     * @return string The identifier 'pushid'
     */
    #[Override()]
    public function getName(): string
    {
        return 'pushid';
    }
}
