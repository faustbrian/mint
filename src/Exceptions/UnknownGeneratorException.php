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
 * Exception thrown when an unknown generator type is requested.
 *
 * This exception occurs when attempting to instantiate a generator using
 * a type identifier that doesn't match any registered generator. Valid
 * generator types include: uuid, ulid, snowflake, nanoid, cuid2, xid, sqid.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnknownGeneratorException extends GeneratorException
{
    /**
     * Create an exception for an unknown generator type.
     *
     * @param string $type The invalid generator type identifier that was requested
     *
     * @return self The exception instance with the unknown type in the message
     */
    public static function forType(string $type): self
    {
        return new self(sprintf('Unknown generator type: "%s"', $type));
    }
}
