<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Exceptions;

/**
 * Exception thrown when an alphabet contains multibyte characters.
 *
 * Alphabets used for encoding identifiers must consist only of single-byte ASCII
 * characters to ensure consistent byte-level processing, predictable string lengths,
 * and cross-platform compatibility. Multibyte characters (e.g., emoji, accented letters,
 * Unicode symbols) would cause encoding/decoding inconsistencies due to variable byte
 * lengths and potential charset mismatches.
 *
 * This restriction ensures that:
 * - Character indexing operates predictably at the byte level
 * - Encoded identifiers have consistent length calculations
 * - Cross-language and cross-platform compatibility is maintained
 * - Performance remains optimal without multibyte string handling overhead
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class AlphabetContainsMultibyteException extends AlphabetException
{
    /**
     * Create a new exception for alphabets containing multibyte characters.
     *
     * Factory method for consistent exception instantiation with a standardized
     * error message. Thrown during alphabet validation when multibyte character
     * detection identifies non-ASCII characters in the provided alphabet string.
     *
     * @return self Exception instance with descriptive message indicating the
     *              single-byte character requirement violation. Message clarifies
     *              that only ASCII characters are permitted in alphabet definitions.
     */
    public static function create(): self
    {
        return new self('Alphabet cannot contain multibyte characters');
    }
}
