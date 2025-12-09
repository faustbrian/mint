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
 * Exception thrown when a UUID value has an invalid format.
 *
 * This exception is raised when attempting to parse or validate a string
 * that does not conform to the RFC 4122 UUID format specification.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://datatracker.ietf.org/doc/html/rfc4122
 */
final class InvalidUuidFormatException extends InvalidIdentifierException
{
    /**
     * Create an exception for an invalid UUID value.
     *
     * @param string $value The invalid UUID string that failed validation
     *
     * @return self The exception instance with a descriptive error message
     */
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid UUID format: "%s"', $value));
    }
}
