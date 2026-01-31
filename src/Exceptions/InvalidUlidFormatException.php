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
 * Exception thrown when a ULID value has an invalid format.
 *
 * ULID (Universally Unique Lexicographically Sortable Identifier) is a
 * time-ordered unique identifier format designed as a more sortable
 * alternative to UUID. This exception is raised when validation fails for
 * ULID parsing or format verification operations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidUlidFormatException extends InvalidIdentifierException
{
    /**
     * Creates an exception for an invalid ULID format value.
     *
     * @param  string $value The invalid ULID value that failed format validation
     * @return self   A new exception instance with a descriptive error message
     */
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid ULID format: "%s"', $value));
    }
}
