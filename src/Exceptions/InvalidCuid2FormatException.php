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
 * Exception thrown when a CUID2 identifier value has an invalid format.
 *
 * CUID2 (Collision-resistant Unique Identifier v2) values must conform to a specific
 * format: a lowercase alphanumeric string with configurable length (default 24 characters).
 * This exception indicates that a provided string does not match the expected CUID2
 * structure and cannot be parsed or validated as a valid CUID2 identifier.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidCuid2FormatException extends InvalidIdentifierException
{
    /**
     * Create a new exception for invalid CUID2 format.
     *
     * @param  string $value The invalid CUID2 string that failed validation
     * @return self   A new exception instance with the invalid value in the error message
     */
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid CUID2 format: "%s"', $value));
    }
}
