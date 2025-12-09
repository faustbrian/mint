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
 * Exception thrown when an alphabet does not meet the minimum length requirement.
 *
 * Different identifier encoding schemes require alphabets of specific minimum lengths
 * to provide sufficient entropy and collision resistance. This exception indicates
 * that the provided alphabet contains too few characters for the requested encoding
 * algorithm to function correctly.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class AlphabetTooShortException extends AlphabetException
{
    /**
     * Create a new exception for alphabets that are too short.
     *
     * @param  int  $minimum The minimum required alphabet length for the encoding scheme
     * @return self A new exception instance with the minimum requirement in the error message
     */
    public static function forMinimum(int $minimum): self
    {
        return new self(sprintf('Alphabet length must be at least %d', $minimum));
    }
}
