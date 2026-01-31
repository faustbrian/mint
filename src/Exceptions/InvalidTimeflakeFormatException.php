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
 * Exception thrown when a Timeflake value has an invalid format.
 *
 * Timeflake is a time-ordered unique identifier format that combines timestamp
 * and random components. This exception is raised when validation fails for
 * Timeflake parsing or format verification operations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidTimeflakeFormatException extends InvalidIdentifierException
{
    /**
     * Creates an exception for an invalid Timeflake format value.
     *
     * @param  string $value The invalid Timeflake value that failed format validation
     * @return self   A new exception instance with a descriptive error message
     */
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid Timeflake format: "%s"', $value));
    }
}
