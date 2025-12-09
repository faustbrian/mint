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
 * Exception thrown when a number is out of valid range for encoding.
 *
 * This exception occurs when attempting to encode a numeric value that exceeds
 * the maximum supported range for a particular encoding scheme. Each encoder
 * has specific bounds based on its alphabet size and maximum output length.
 * Values must fall within the range [0, maxValue] to be successfully encoded.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class NumberOutOfRangeException extends InvalidArgumentException implements MintException
{
    /**
     * Create an exception for a number exceeding the maximum encodable value.
     *
     * @param int $maxValue The maximum value supported by the encoder
     *
     * @return self The exception instance with the valid range in the message
     */
    public static function forMaxValue(int $maxValue): self
    {
        return new self(sprintf('Encoding supports numbers between 0 and %d', $maxValue));
    }
}
