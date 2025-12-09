<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Exceptions;

/**
 * Exception thrown when the Snowflake sequence overflows.
 *
 * This exception occurs when the Snowflake generator exhausts its sequence
 * counter within a single millisecond. Snowflake uses a 12-bit sequence
 * counter allowing 4,096 IDs per millisecond per worker. When this limit
 * is exceeded, the generator cannot produce additional unique IDs until
 * the next millisecond boundary.
 *
 * This typically indicates extremely high-frequency ID generation that may
 * require horizontal scaling across multiple worker instances or switching
 * to a different ID generation strategy.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class SequenceOverflowException extends GeneratorException
{
    /**
     * Create an exception for sequence counter overflow.
     *
     * @return self The exception instance indicating sequence exhaustion
     */
    public static function tooManyIds(): self
    {
        return new self('Sequence overflow: too many IDs generated in the same millisecond');
    }
}
