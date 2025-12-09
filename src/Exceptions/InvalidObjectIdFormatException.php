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
 * Exception thrown when an ObjectID value has an invalid format.
 *
 * ObjectID is MongoDB's 12-byte unique identifier format. This exception
 * is raised when validation fails for ObjectID parsing or format verification,
 * typically due to incorrect length or invalid hexadecimal characters.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidObjectIdFormatException extends InvalidIdentifierException
{
    /**
     * Creates an exception for an invalid ObjectID format value.
     *
     * @param  string $value The invalid ObjectID value that failed format validation
     * @return self   A new exception instance with a descriptive error message
     */
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid ObjectID format: "%s"', $value));
    }
}
