<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Conductors;

use Cline\Mint\Enums\IdentifierType;
use Cline\Mint\Generators\ObjectIdGenerator;
use Cline\Mint\MintManager;
use Cline\Mint\Support\Identifiers\ObjectId;

/**
 * Fluent conductor for MongoDB ObjectID generation and parsing.
 *
 * ObjectIDs are 96-bit (12 bytes) identifiers used by MongoDB. They contain
 * a timestamp, machine identifier, process ID, and counter.
 *
 * ```php
 * $objectId = Mint::objectId()->generate();
 * $parsed = Mint::objectId()->parse($string);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class ObjectIdConductor
{
    /**
     * Create a new ObjectID conductor instance.
     *
     * @param MintManager $manager Central manager instance that coordinates identifier
     *                             generation across the Mint library. Handles generator
     *                             instantiation, configuration management, and provides
     *                             access to the underlying generator implementations.
     */
    public function __construct(
        private MintManager $manager,
    ) {}

    /**
     * Generate a new ObjectID.
     *
     * Creates a MongoDB-compatible ObjectID consisting of 96 bits (12 bytes)
     * of data. Encodes a 4-byte timestamp, 5-byte random value, and 3-byte
     * incrementing counter. Provides natural time-based sorting and distributed
     * generation without coordination. Represented as a 24-character hexadecimal
     * string.
     *
     * @return ObjectId New ObjectID identifier object
     */
    public function generate(): ObjectId
    {
        /** @var ObjectIdGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::ObjectId);

        return $generator->generate();
    }

    /**
     * Parse an ObjectID string.
     *
     * Converts an ObjectID string representation into an ObjectID object for
     * inspection and manipulation. Allows extraction of the embedded timestamp
     * and access to individual components (timestamp, random value, counter).
     *
     * @param  string   $value ObjectID string to parse (24 hexadecimal characters)
     * @return ObjectId Parsed ObjectID identifier object
     */
    public function parse(string $value): ObjectId
    {
        /** @var ObjectIdGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::ObjectId);

        return $generator->parse($value);
    }

    /**
     * Check if a string is a valid ObjectID.
     *
     * Validates whether a given string conforms to the ObjectID format
     * specification. Checks for correct length (24 characters) and valid
     * hexadecimal encoding.
     *
     * @param  string $value String to validate
     * @return bool   True if the string is a valid ObjectID format, false otherwise
     */
    public function isValid(string $value): bool
    {
        return $this->manager->getGenerator(IdentifierType::ObjectId)->isValid($value);
    }
}
