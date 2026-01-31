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
 * Exception thrown when a generic identifier value has an invalid format.
 *
 * This exception provides a generic validation failure mechanism for identifier
 * types that don't have their own specific exception classes. It allows dynamic
 * error reporting where the identifier type name is included in the exception
 * message, making it useful for custom or plugin-based identifier generators.
 *
 * Use this exception when validating identifiers for which there is no dedicated
 * format exception class, or when building generic validation logic that works
 * across multiple identifier types.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidGenericIdentifierFormatException extends InvalidIdentifierException
{
    /**
     * Create a new exception for invalid identifier format with type information.
     *
     * @param  string $type  The identifier type name (e.g., "UUID", "ULID", "Custom") for the error message
     * @param  string $value The invalid identifier string that failed validation
     * @return self   A new exception instance with both type and value in the error message
     */
    public static function forTypeAndValue(string $type, string $value): self
    {
        return new self(sprintf('Invalid %s format: "%s"', $type, $value));
    }
}
