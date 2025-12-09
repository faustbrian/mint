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
 * Exception thrown when a Snowflake ID value has an invalid format.
 *
 * Snowflake is Twitter's distributed unique ID generation system that produces
 * 64-bit time-based identifiers. This exception is raised when validation fails
 * for Snowflake ID parsing or format verification operations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidSnowflakeFormatException extends InvalidIdentifierException
{
    /**
     * Creates an exception for an invalid Snowflake ID format value.
     *
     * @param  string $value The invalid Snowflake ID value that failed format validation
     * @return self   A new exception instance with a descriptive error message
     */
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid Snowflake ID format: "%s"', $value));
    }
}
