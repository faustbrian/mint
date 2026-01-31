<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Generators;

use Cline\Mint\Algorithms\SnowflakeAlgorithm;
use Cline\Mint\Contracts\GeneratorInterface;
use Cline\Mint\Support\Identifiers\Snowflake;
use Override;

/**
 * Snowflake ID generator (Twitter-style).
 *
 * Orchestrates Snowflake ID generation by combining the SnowflakeAlgorithm
 * with identifier object creation. Provides a clean interface for generating
 * and parsing Snowflake IDs in distributed systems.
 *
 * ```php
 * $generator = new SnowflakeGenerator(nodeId: 1);
 * $snowflake = $generator->generate();
 * echo $snowflake->toString(); // e.g., "1234567890123456789"
 * ```
 *
 * @api
 * @author Brian Faust <brian@cline.sh>
 * @see https://blog.twitter.com/engineering/en_us/a/2010/announcing-snowflake
 * @psalm-immutable
 */
final readonly class SnowflakeGenerator implements GeneratorInterface
{
    private SnowflakeAlgorithm $algorithm;

    /**
     * Create a new Snowflake generator.
     *
     * @param int $nodeId Unique identifier for this node/machine (0-1023)
     * @param int $epoch  Custom epoch timestamp in milliseconds
     */
    public function __construct(
        int $nodeId = 0,
        int $epoch = Snowflake::DEFAULT_EPOCH,
    ) {
        $this->algorithm = new SnowflakeAlgorithm($nodeId, $epoch);
    }

    /**
     * Generate a new Snowflake ID.
     */
    #[Override()]
    public function generate(): Snowflake
    {
        $data = $this->algorithm->generate();

        return new Snowflake($data['value'], $data['bytes'], $this->algorithm->getEpoch());
    }

    /**
     * Parse a Snowflake ID string.
     */
    #[Override()]
    public function parse(string $value): Snowflake
    {
        $data = $this->algorithm->parse($value);

        return new Snowflake($data['value'], $data['bytes'], $this->algorithm->getEpoch());
    }

    /**
     * Check if a string is a valid Snowflake ID.
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
        return 'snowflake';
    }

    /**
     * Get the current node ID.
     */
    public function getNodeId(): int
    {
        return $this->algorithm->getNodeId();
    }

    /**
     * Get the epoch.
     */
    public function getEpoch(): int
    {
        return $this->algorithm->getEpoch();
    }
}
