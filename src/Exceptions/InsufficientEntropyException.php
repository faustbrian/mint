<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Exceptions;

/**
 * Exception thrown when the system cannot generate sufficient random bytes.
 *
 * Cryptographically secure identifier generation requires high-quality random
 * data from the operating system's entropy pool. This exception indicates that
 * the random_bytes() function failed to produce the requested amount of random
 * data, typically due to insufficient entropy in the system's random number
 * generator.
 *
 * This is a rare condition that usually indicates environmental issues such as
 * a depleted entropy pool on systems with limited entropy sources or failures
 * in the underlying CSPRNG (Cryptographically Secure Pseudo-Random Number Generator).
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InsufficientEntropyException extends GeneratorException
{
    /**
     * Create a new exception for random byte generation failures.
     *
     * @return self A new exception instance indicating random byte generation failure
     */
    public static function failedToGenerate(): self
    {
        return new self('Failed to generate sufficient random bytes');
    }
}
