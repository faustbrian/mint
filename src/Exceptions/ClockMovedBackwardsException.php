<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Exceptions;

use function sprintf;

/**
 * Exception thrown when the system clock moves backwards during Snowflake ID generation.
 *
 * Snowflake-style identifiers rely on monotonically increasing timestamps to guarantee
 * uniqueness and sortability. If the system clock moves backwards (due to NTP adjustments,
 * manual time changes, or clock drift), the generator refuses to produce IDs to prevent
 * potential ID collisions or ordering violations.
 *
 * This exception protects against generating duplicate IDs or IDs with incorrect temporal
 * ordering by halting generation when clock regression is detected.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ClockMovedBackwardsException extends GeneratorException
{
    /**
     * Create a new exception for backwards clock movement.
     *
     * @param  int  $lastTimestamp    The most recent timestamp used for ID generation (in milliseconds)
     * @param  int  $currentTimestamp The current system timestamp that is earlier than the last timestamp
     * @return self A new exception instance with detailed timestamp information
     */
    public static function forTimestamps(int $lastTimestamp, int $currentTimestamp): self
    {
        return new self(sprintf(
            'Clock moved backwards. Refusing to generate ID for %d milliseconds (last: %d, current: %d)',
            $lastTimestamp - $currentTimestamp,
            $lastTimestamp,
            $currentTimestamp,
        ));
    }
}
