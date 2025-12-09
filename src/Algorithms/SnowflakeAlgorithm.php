<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Algorithms;

use Cline\Mint\Contracts\AlgorithmInterface;
use Cline\Mint\Exceptions\ClockMovedBackwardsException;
use Cline\Mint\Exceptions\InvalidNodeIdException;
use Cline\Mint\Exceptions\InvalidSnowflakeFormatException;
use Cline\Mint\Support\Identifiers\Snowflake;
use Override;

use const PHP_INT_MAX;

use function microtime;
use function pack;
use function preg_match;

/**
 * Snowflake algorithm implementation (Twitter-style).
 *
 * Generates 64-bit identifiers following Twitter's Snowflake specification.
 * Designed for distributed systems requiring roughly time-ordered IDs with high
 * throughput and no central coordination. Each node can generate up to 4096 IDs
 * per millisecond.
 *
 * Structure (64 bits):
 * - 1 bit: Unused (always 0 for positive numbers)
 * - 41 bits: Timestamp (milliseconds since custom epoch, supports ~69 years)
 * - 10 bits: Node/machine ID (0-1023, supports 1024 unique nodes)
 * - 12 bits: Sequence number (0-4095, incrementing counter per millisecond)
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://blog.twitter.com/engineering/en_us/a/2010/announcing-snowflake
 */
final class SnowflakeAlgorithm implements AlgorithmInterface
{
    private const int NODE_ID_BITS = 10;

    private const int SEQUENCE_BITS = 12;

    /**
     * Maximum node ID value (1023).
     */
    private const int MAX_NODE_ID = (1 << self::NODE_ID_BITS) - 1;

    /**
     * Maximum sequence value (4095).
     */
    private const int MAX_SEQUENCE = (1 << self::SEQUENCE_BITS) - 1;

    /**
     * Bit shift for node ID component (12 bits).
     */
    private const int NODE_ID_SHIFT = self::SEQUENCE_BITS;

    /**
     * Bit shift for timestamp component (22 bits).
     */
    private const int TIMESTAMP_SHIFT = self::NODE_ID_BITS + self::SEQUENCE_BITS;

    /**
     * Last timestamp used for ID generation.
     */
    private int $lastTimestamp = -1;

    /**
     * Current sequence number within the current millisecond.
     */
    private int $sequence = 0;

    /**
     * Create a new Snowflake algorithm instance.
     *
     * @param int $nodeId Unique identifier for this node/machine (0-1023)
     * @param int $epoch  Custom epoch timestamp in milliseconds
     *
     * @throws InvalidNodeIdException When node ID is outside valid range (0-1023)
     */
    public function __construct(
        private readonly int $nodeId = 0,
        private readonly int $epoch = Snowflake::DEFAULT_EPOCH,
    ) {
        if ($nodeId < 0 || $nodeId > self::MAX_NODE_ID) {
            throw InvalidNodeIdException::outOfRange($nodeId, self::MAX_NODE_ID);
        }
    }

    /**
     * Generate raw Snowflake ID data.
     *
     * @throws ClockMovedBackwardsException        When system clock moves backwards
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function generate(): array
    {
        $timestamp = $this->currentTimestamp();

        if ($timestamp < $this->lastTimestamp) {
            throw ClockMovedBackwardsException::forTimestamps($this->lastTimestamp, $timestamp);
        }

        if ($timestamp === $this->lastTimestamp) {
            $this->sequence = ($this->sequence + 1) & self::MAX_SEQUENCE;

            if ($this->sequence === 0) {
                $timestamp = $this->waitNextMillis($this->lastTimestamp);
            }
        } else {
            $this->sequence = 0;
        }

        $this->lastTimestamp = $timestamp;

        $id = (($timestamp - $this->epoch) << self::TIMESTAMP_SHIFT)
            | ($this->nodeId << self::NODE_ID_SHIFT)
            | $this->sequence;

        return [
            'value' => (string) $id,
            'bytes' => pack('J', $id),
        ];
    }

    /**
     * Parse a Snowflake ID string into raw data.
     *
     * @param string $value The numeric Snowflake ID string
     *
     * @throws InvalidSnowflakeFormatException     When the value is not valid
     * @return array{value: string, bytes: string}
     */
    #[Override()]
    public function parse(string $value): array
    {
        if (!$this->isValid($value)) {
            throw InvalidSnowflakeFormatException::forValue($value);
        }

        $id = (int) $value;

        return [
            'value' => $value,
            'bytes' => pack('J', $id),
        ];
    }

    /**
     * Check if a string is a valid Snowflake ID.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string is a valid Snowflake ID format
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        if (preg_match('/^\d+$/', $value) !== 1) {
            return false;
        }

        $id = (int) $value;

        return $id >= 0 && $id <= PHP_INT_MAX;
    }

    /**
     * Get the configured epoch.
     */
    public function getEpoch(): int
    {
        return $this->epoch;
    }

    /**
     * Get the configured node ID.
     */
    public function getNodeId(): int
    {
        return $this->nodeId;
    }

    /**
     * Get current timestamp in milliseconds.
     */
    private function currentTimestamp(): int
    {
        return (int) (microtime(true) * 1_000);
    }

    /**
     * Wait for the next millisecond.
     */
    private function waitNextMillis(int $lastTimestamp): int
    {
        $timestamp = $this->currentTimestamp();

        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->currentTimestamp();
        }

        return $timestamp;
    }
}
