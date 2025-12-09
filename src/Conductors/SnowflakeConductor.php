<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Conductors;

use Cline\Mint\Enums\IdentifierType;
use Cline\Mint\Generators\SnowflakeGenerator;
use Cline\Mint\MintManager;
use Cline\Mint\Support\Identifiers\Snowflake;

/**
 * Fluent conductor for Snowflake ID generation and parsing.
 *
 * Snowflake IDs are 64-bit, time-ordered identifiers originally designed
 * by Twitter. They consist of timestamp, node ID, and sequence number.
 *
 * ```php
 * $snowflake = Mint::snowflake()->nodeId(1)->generate();
 * $snowflake = Mint::snowflake()->nodeId(1)->epoch(1609459200000)->generate();
 * $parsed = Mint::snowflake()->parse($string);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class SnowflakeConductor
{
    /**
     * Create a new Snowflake ID conductor instance.
     *
     * @param MintManager $manager Central manager instance that coordinates identifier
     *                             generation across the Mint library. Handles generator
     *                             instantiation, configuration management, and provides
     *                             access to the underlying generator implementations.
     * @param int         $nodeId  Node or machine identifier (0-1023) that uniquely identifies
     *                             the generator instance in a distributed system. Different nodes
     *                             must use different IDs to ensure globally unique identifiers.
     *                             Uses 10 bits of the Snowflake ID space. Default is 0 for
     *                             single-node deployments.
     * @param null|int    $epoch   Custom epoch timestamp in milliseconds. Defines the zero point
     *                             for the timestamp component of generated IDs. When null, uses
     *                             the Twitter Snowflake epoch (November 4, 2010). Setting a more
     *                             recent epoch extends the usable lifetime of the ID space by
     *                             reducing wasted timestamp range.
     */
    public function __construct(
        private MintManager $manager,
        private int $nodeId = 0,
        private ?int $epoch = null,
    ) {}

    /**
     * Set the node/machine ID (0-1023).
     *
     * Returns a new conductor instance with the specified node identifier.
     * Essential for distributed ID generation to prevent collisions between
     * different nodes. Each node in a cluster must have a unique ID.
     *
     * @param  int  $nodeId Node identifier between 0 and 1023
     * @return self New conductor instance with updated node ID
     */
    public function nodeId(int $nodeId): self
    {
        return new self($this->manager, $nodeId, $this->epoch);
    }

    /**
     * Set a custom epoch timestamp in milliseconds.
     *
     * Returns a new conductor instance with the specified epoch. Custom epochs
     * allow maximizing the usable lifetime of Snowflake IDs by shifting the
     * timestamp zero point closer to the current time. Each millisecond further
     * from the epoch extends the usable range.
     *
     * @param  int  $epoch Epoch timestamp in milliseconds since Unix epoch
     * @return self New conductor instance with updated epoch
     */
    public function epoch(int $epoch): self
    {
        return new self($this->manager, $this->nodeId, $epoch);
    }

    /**
     * Generate a new Snowflake ID.
     *
     * Creates a 64-bit time-ordered identifier consisting of a 41-bit timestamp,
     * 10-bit node ID, and 12-bit sequence number. Provides chronological ordering,
     * distributed generation without coordination, and up to 4096 IDs per
     * millisecond per node. Represented as a numeric string.
     *
     * @return Snowflake New Snowflake ID identifier object
     */
    public function generate(): Snowflake
    {
        $config = ['node_id' => $this->nodeId];

        if ($this->epoch !== null) {
            $config['epoch'] = $this->epoch;
        }

        /** @var SnowflakeGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Snowflake, $config);

        return $generator->generate();
    }

    /**
     * Parse a Snowflake ID string.
     *
     * Converts a Snowflake ID string representation into a Snowflake object
     * for inspection. Allows extraction of embedded timestamp, node ID, and
     * sequence number components for analysis and debugging.
     *
     * @param  string    $value Snowflake ID string to parse (numeric string)
     * @return Snowflake Parsed Snowflake ID identifier object
     */
    public function parse(string $value): Snowflake
    {
        /** @var SnowflakeGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Snowflake);

        return $generator->parse($value);
    }

    /**
     * Check if a string is a valid Snowflake ID.
     *
     * Validates whether a given string conforms to the Snowflake ID format.
     * Checks that the string represents a valid 64-bit unsigned integer within
     * the acceptable range for Snowflake IDs.
     *
     * @param  string $value String to validate
     * @return bool   True if the string is a valid Snowflake ID format, false otherwise
     */
    public function isValid(string $value): bool
    {
        return $this->manager->getGenerator(IdentifierType::Snowflake)->isValid($value);
    }
}
