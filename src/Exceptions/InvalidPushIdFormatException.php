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
 * Exception thrown when a PushID value has an invalid format.
 *
 * PushID is Firebase's time-ordered unique identifier format designed for
 * distributed systems. This exception is raised when validation fails for
 * PushID parsing or format verification operations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidPushIdFormatException extends InvalidIdentifierException
{
    /**
     * Creates an exception for an invalid PushID format value.
     *
     * @param  string $value The invalid PushID value that failed format validation
     * @return self   A new exception instance with a descriptive error message
     */
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid PushID format: "%s"', $value));
    }
}
