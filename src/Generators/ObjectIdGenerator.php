<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Generators;

use Cline\Mint\Algorithms\ObjectIdAlgorithm;
use Cline\Mint\Contracts\GeneratorInterface;
use Cline\Mint\Exceptions\InvalidObjectIdFormatException;
use Cline\Mint\Support\Identifiers\ObjectId;
use Override;

/**
 * MongoDB ObjectID generator.
 *
 * Generates 96-bit (12 byte) BSON ObjectIDs following the MongoDB specification.
 * ObjectIDs are designed for distributed systems where central coordination is
 * impractical. They provide reasonable global uniqueness without requiring network
 * communication.
 *
 * Structure (12 bytes):
 * - 4 bytes: Unix timestamp in seconds (big-endian)
 * - 5 bytes: Random value (unique per machine/process, cached during lifetime)
 * - 3 bytes: Incrementing counter (big-endian, initialized with random value)
 *
 * The timestamp component makes ObjectIDs roughly sortable by creation time.
 * Encoded as 24 lowercase hexadecimal characters.
 *
 * ```php
 * $generator = new ObjectIdGenerator();
 * $objectId = $generator->generate();
 * echo $objectId->toString(); // e.g., "507f1f77bcf86cd799439011"
 * ```
 *
 * @api
 * @author Brian Faust <brian@cline.sh>
 * @see https://www.mongodb.com/docs/manual/reference/method/ObjectId/
 * @psalm-immutable
 */
final readonly class ObjectIdGenerator implements GeneratorInterface
{
    /**
     * The underlying ObjectID algorithm.
     */
    private ObjectIdAlgorithm $algorithm;

    /**
     * Create a new ObjectID generator instance.
     */
    public function __construct()
    {
        $this->algorithm = new ObjectIdAlgorithm();
    }

    /**
     * Generate a new ObjectID.
     *
     * Combines current timestamp, cached random value, and incrementing counter
     * to produce a globally unique identifier with high probability.
     *
     * @return ObjectId The generated ObjectID instance
     */
    #[Override()]
    public function generate(): ObjectId
    {
        $data = $this->algorithm->generate();

        return new ObjectId($data['value'], $data['bytes']);
    }

    /**
     * Generate an ObjectID from a specific timestamp.
     *
     * Useful for backfilling historical data, testing, or creating ObjectIDs
     * that sort to a specific time range. The random value and counter are
     * still generated normally.
     *
     * @param int $timestamp Unix timestamp in seconds
     *
     * @return ObjectId The generated ObjectID instance with the specified timestamp
     */
    public function fromTimestamp(int $timestamp): ObjectId
    {
        $data = $this->algorithm->fromTimestamp($timestamp);

        return new ObjectId($data['value'], $data['bytes']);
    }

    /**
     * Parse an ObjectID string.
     *
     * Validates and decodes an ObjectID hex string into its component parts,
     * extracting the timestamp, random value, and counter for inspection.
     * Normalizes the input to lowercase.
     *
     * @param string $value The ObjectID hex string to parse (24 characters)
     *
     * @throws InvalidObjectIdFormatException When the value is not a valid ObjectID format
     * @return ObjectId                       The parsed ObjectID instance
     */
    #[Override()]
    public function parse(string $value): ObjectId
    {
        $data = $this->algorithm->parse($value);

        return new ObjectId($data['value'], $data['bytes']);
    }

    /**
     * Check if a string is a valid ObjectID.
     *
     * Validates both the length (must be exactly 24 characters) and that all
     * characters are valid hexadecimal digits. Case-insensitive validation.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid ObjectID format
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        return $this->algorithm->isValid($value);
    }

    /**
     * Get the generator name.
     *
     * @return string The identifier 'objectid'
     */
    #[Override()]
    public function getName(): string
    {
        return 'objectid';
    }
}
