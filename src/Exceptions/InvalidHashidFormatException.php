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
 * Exception thrown when a Hashid identifier value has an invalid format.
 *
 * Hashids are obfuscated, URL-safe identifiers generated from numeric values using
 * a custom alphabet and salt. This exception indicates that a provided string does
 * not conform to the expected Hashid structure or cannot be decoded using the
 * configured alphabet and salt combination.
 *
 * Common causes include tampering with the encoded string, using the wrong alphabet
 * or salt for decoding, or providing a string that was never generated as a Hashid.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidHashidFormatException extends InvalidIdentifierException
{
    /**
     * Create a new exception for invalid Hashid format.
     *
     * @param  string $value The invalid Hashid string that failed validation or decoding
     * @return self   A new exception instance with the invalid value in the error message
     */
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid Hashid format: "%s"', $value));
    }
}
