<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Exceptions;

use InvalidArgumentException;

use function sprintf;

/**
 * Exception thrown when a minimum length is out of valid range.
 *
 * This exception is raised when attempting to configure a generator with
 * a minimum length value that falls outside the generator's supported
 * range constraints. Each generator type has specific bounds for identifier
 * length based on its encoding and format requirements.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MinLengthOutOfRangeException extends InvalidArgumentException implements MintException
{
    /**
     * Create an exception for a minimum length outside valid range.
     *
     * @param int $min The minimum allowed length for the generator
     * @param int $max The maximum allowed length for the generator
     *
     * @return self The exception instance with the valid range in the message
     */
    public static function forRange(int $min, int $max): self
    {
        return new self(sprintf('Minimum length has to be between %d and %d', $min, $max));
    }
}
