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
 * Exception thrown when a NanoID value has an invalid format.
 *
 * NanoID is a compact, URL-friendly unique identifier generator. This exception
 * is raised when validation fails for NanoID parsing or format verification,
 * such as invalid characters or incorrect length.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidNanoIdFormatException extends InvalidIdentifierException
{
    /**
     * Creates an exception for an invalid NanoID format value.
     *
     * @param  string $value The invalid NanoID value that failed format validation
     * @return self   A new exception instance with a descriptive error message
     */
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid NanoID format: "%s"', $value));
    }
}
