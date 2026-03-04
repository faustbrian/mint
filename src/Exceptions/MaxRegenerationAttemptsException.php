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
 * Exception thrown when maximum ID regeneration attempts have been reached.
 *
 * This exception occurs when the generator exhausts all retry attempts
 * while trying to generate a unique identifier, typically due to collision
 * detection or validation constraints. This indicates a systemic issue
 * that requires investigation (e.g., insufficient entropy, overly restrictive
 * validation rules, or extremely high collision rates).
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MaxRegenerationAttemptsException extends InvalidArgumentException implements MintException
{
    /**
     * Create an exception for reaching maximum regeneration attempts.
     *
     * @return self The exception instance indicating retry exhaustion
     */
    public static function create(): self
    {
        return new self('Reached max attempts to re-generate the ID');
    }
}
