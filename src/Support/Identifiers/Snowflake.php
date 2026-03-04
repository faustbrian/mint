<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Support\Identifiers;

use Cline\Mint\Support\AbstractIdentifier;
use Override;

/**
 * Snowflake ID value object for distributed, sortable 64-bit identifiers.
 *
 * A compact 64-bit identifier originally developed by Twitter for distributed
 * systems. Combines timestamp, node identifier, and sequence number in a single
 * integer that fits in a database BIGINT column while maintaining sortability
 * and uniqueness across distributed nodes without coordination.
 *
 * Structure:
 * - 41 bits: timestamp (milliseconds since custom epoch, ~69 years range)
 * - 10 bits: node/machine ID (supports up to 1024 nodes)
 * - 12 bits: sequence number (4096 IDs per millisecond per node)
 *
 * The bit-level encoding allows extracting components via bitwise operations
 * and ensures IDs are both time-sortable and globally unique.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/twitter-archive/snowflake/tree/snowflake-2010
 */
final class Snowflake extends AbstractIdentifier
{
    /**
     * Default Twitter epoch: November 4, 2010 01:42:54.657 UTC.
     *
     * Custom epoch allows the 41-bit timestamp to span approximately 69 years
     * from this starting point, extending the usable range beyond 2079.
     */
    public const int DEFAULT_EPOCH = 1_288_834_974_657;

    /**
     * Number of bits allocated to the node/machine identifier.
     */
    private const int NODE_ID_BITS = 10;

    /**
     * Number of bits allocated to the per-millisecond sequence counter.
     */
    private const int SEQUENCE_BITS = 12;

    /**
     * Create a new Snowflake ID instance.
     *
     * @param string $value Numeric string representation of the 64-bit Snowflake ID,
     *                      typically stored as a string to preserve precision across
     *                      systems with varying integer size limits
     * @param string $bytes Binary representation of the identifier for low-level
     *                      storage operations and byte-level manipulations
     * @param int    $epoch Custom epoch timestamp in milliseconds since Unix epoch.
     *                      Defaults to Twitter's epoch (November 4, 2010), but can be
     *                      customized for different Snowflake implementations to extend
     *                      the usable timestamp range.
     */
    public function __construct(
        string $value,
        string $bytes,
        private readonly int $epoch = self::DEFAULT_EPOCH,
    ) {
        parent::__construct($value, $bytes);
    }

    /**
     * Get the timestamp component in milliseconds since Unix epoch.
     *
     * Extracts the 41-bit timestamp from the upper bits and adds the
     * configured epoch offset to convert to absolute Unix timestamp.
     */
    #[Override()]
    public function getTimestamp(): int
    {
        $id = (int) $this->value;
        $timestampOffset = self::NODE_ID_BITS + self::SEQUENCE_BITS;

        return ($id >> $timestampOffset) + $this->epoch;
    }

    /**
     * Get the node/machine ID component.
     *
     * Extracts the 10-bit node identifier that indicates which machine or
     * datacenter generated this Snowflake. Returns a value between 0-1023.
     */
    public function getNodeId(): int
    {
        $id = (int) $this->value;
        $maxNodeId = (1 << self::NODE_ID_BITS) - 1;

        return ($id >> self::SEQUENCE_BITS) & $maxNodeId;
    }

    /**
     * Get the sequence number component.
     *
     * Extracts the 12-bit sequence counter that increments for each ID
     * generated within the same millisecond on the same node. Returns a
     * value between 0-4095.
     */
    public function getSequence(): int
    {
        $id = (int) $this->value;
        $maxSequence = (1 << self::SEQUENCE_BITS) - 1;

        return $id & $maxSequence;
    }

    /**
     * Get the custom epoch used for this Snowflake.
     *
     * Returns the epoch timestamp in milliseconds that serves as the
     * time-zero reference point for the 41-bit timestamp field.
     */
    public function getEpoch(): int
    {
        return $this->epoch;
    }

    /**
     * Check if this identifier is sortable by creation time.
     *
     * Snowflakes are inherently sortable as the timestamp occupies the
     * most significant bits, ensuring numeric ordering matches chronological
     * order.
     */
    #[Override()]
    public function isSortable(): bool
    {
        return true;
    }

    /**
     * Get the binary representation as bytes.
     */
    #[Override()]
    public function toBytes(): string
    {
        return $this->bytes;
    }
}
