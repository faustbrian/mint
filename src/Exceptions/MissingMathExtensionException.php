<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Exceptions;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when a required math extension is not installed.
 *
 * Certain ID generators (such as ULID and Snowflake) require arbitrary-precision
 * arithmetic operations that depend on either the bcmath or gmp PHP extension.
 * This exception is thrown when neither extension is available in the current
 * PHP environment.
 *
 * To resolve this error, install one of the required extensions:
 * - bcmath: `apt-get install php-bcmath` or `brew install php --with-bcmath`
 * - gmp: `apt-get install php-gmp` or `brew install php --with-gmp`
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MissingMathExtensionException extends RuntimeException implements MintException
{
    /**
     * Create an exception for a missing math extension.
     *
     * @param string $component The generator component name that requires the extension
     *
     * @return self The exception instance with installation instructions
     */
    public static function forComponent(string $component): self
    {
        return new self(sprintf(
            'Missing math extension for %s, install either bcmath or gmp.',
            $component,
        ));
    }
}
