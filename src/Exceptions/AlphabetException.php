<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Exceptions;

use InvalidArgumentException;

/**
 * Base exception for all alphabet configuration errors.
 *
 * This abstract exception serves as the parent class for all alphabet-related
 * validation failures in the Mint package. Alphabets define the character sets
 * used for encoding identifiers, and they must meet specific requirements such
 * as minimum length, uniqueness of characters, and valid character types.
 *
 * Common alphabet validation scenarios include:
 * - Alphabets that are too short for the required encoding scheme
 * - Alphabets containing duplicate characters
 * - Alphabets containing invalid characters (spaces, multibyte characters)
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class AlphabetException extends InvalidArgumentException implements MintException
{
    // Abstract base class - concrete implementations provide specific factory methods
}
