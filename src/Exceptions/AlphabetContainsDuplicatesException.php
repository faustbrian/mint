<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Exceptions;

/**
 * Exception thrown when an alphabet contains duplicate characters.
 *
 * Alphabets used for encoding identifiers (Sqids, Hashids, NanoID, etc.) must contain
 * only unique characters to ensure bijective encoding where each character position
 * maps to a distinct value. Duplicate characters would create ambiguous encodings
 * where multiple character sequences could represent the same numeric value.
 *
 * This validation prevents encoding/decoding errors and ensures consistent identifier
 * generation across different systems using the same alphabet configuration.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class AlphabetContainsDuplicatesException extends AlphabetException
{
    /**
     * Create a new exception for alphabets containing duplicate characters.
     *
     * Factory method for consistent exception instantiation with a standardized
     * error message. Thrown during alphabet validation when character uniqueness
     * checks detect repeated characters in the provided alphabet string.
     *
     * @return self Exception instance with descriptive message indicating the
     *              alphabet uniqueness requirement violation. Message is suitable
     *              for debugging and end-user error reporting.
     */
    public static function create(): self
    {
        return new self('Alphabet must contain unique characters');
    }
}
