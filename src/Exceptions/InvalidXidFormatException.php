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
 * Exception thrown when an XID value has an invalid format.
 *
 * This exception is raised when attempting to parse or validate a string
 * that does not conform to the XID format specification. XIDs are globally
 * unique, sortable, URL-safe identifiers based on MongoDB's ObjectID.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/rs/xid
 */
final class InvalidXidFormatException extends InvalidIdentifierException
{
    /**
     * Create an exception for an invalid XID value.
     *
     * @param string $value The invalid XID string that failed validation
     *
     * @return self The exception instance with a descriptive error message
     */
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid XID format: "%s"', $value));
    }
}
