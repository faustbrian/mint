<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Exceptions;

/**
 * Exception thrown when an alphabet configuration contains space characters.
 *
 * Alphabets used for identifier generation must consist of valid, non-whitespace
 * characters to ensure consistent encoding and decoding behavior. Space characters
 * can cause parsing ambiguities and invalid identifier formats.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class AlphabetContainsSpacesException extends AlphabetException
{
    /**
     * Create a new exception for alphabets containing spaces.
     *
     * @return self A new exception instance with a descriptive error message
     */
    public static function create(): self
    {
        return new self('Alphabet cannot contain spaces');
    }
}
