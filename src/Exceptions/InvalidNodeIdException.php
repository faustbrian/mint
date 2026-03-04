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
 * Exception thrown when an invalid node ID is provided.
 *
 * Node IDs are used in distributed ID generation systems (like Snowflake)
 * to distinguish between different machines or processes. This exception
 * is raised when a node ID falls outside the valid range for the system.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidNodeIdException extends GeneratorException
{
    /**
     * Creates an exception for a node ID that is outside the valid range.
     *
     * @param  int  $nodeId    The invalid node ID value that was provided
     * @param  int  $maxNodeId The maximum allowed node ID value for the system
     * @return self A new exception instance with detailed range information
     */
    public static function outOfRange(int $nodeId, int $maxNodeId): self
    {
        return new self(sprintf(
            'Node ID must be between 0 and %d, got: %d',
            $maxNodeId,
            $nodeId,
        ));
    }
}
