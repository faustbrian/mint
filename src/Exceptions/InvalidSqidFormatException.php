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
 * Exception thrown when a Sqid value has an invalid format.
 *
 * Sqid is a URL-safe, configurable unique identifier format that can encode
 * and decode integers into short strings. This exception is raised when
 * validation fails for Sqid parsing or format verification operations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidSqidFormatException extends InvalidIdentifierException
{
    /**
     * Creates an exception for an invalid Sqid format value.
     *
     * @param  string $value The invalid Sqid value that failed format validation
     * @return self   A new exception instance with a descriptive error message
     */
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid Sqid format: "%s"', $value));
    }
}
