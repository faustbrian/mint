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
 * Exception thrown when a KSUID value has an invalid format.
 *
 * KSUID (K-Sortable Unique Identifier) is a time-ordered unique identifier
 * format. This exception is raised when validation fails for KSUID parsing
 * or format verification operations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidKsuidFormatException extends InvalidIdentifierException
{
    /**
     * Creates an exception for an invalid KSUID format value.
     *
     * @param  string $value The invalid KSUID value that failed format validation
     * @return self   A new exception instance with a descriptive error message
     */
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid KSUID format: "%s"', $value));
    }
}
