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
 * Exception thrown when a TypeID value has an invalid format.
 *
 * TypeID is a type-safe, K-sortable unique identifier format that combines
 * a type prefix with a unique identifier. This exception is raised when
 * validation fails for TypeID parsing or format verification operations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidTypeIdFormatException extends InvalidIdentifierException
{
    /**
     * Creates an exception for an invalid TypeID format value.
     *
     * @param  string $value The invalid TypeID value that failed format validation
     * @return self   A new exception instance with a descriptive error message
     */
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid TypeID format: "%s"', $value));
    }
}
