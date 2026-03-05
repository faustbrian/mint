<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Exceptions;

use Facade\IgnitionContracts\BaseSolution;
use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;
use RuntimeException;

/**
 * Base exception for all identifier generation runtime errors.
 *
 * This abstract exception serves as the parent class for all runtime failures
 * that occur during the identifier generation process. Unlike configuration
 * errors (which extend InvalidArgumentException), these errors represent
 * environmental or state-based failures that prevent ID generation.
 *
 * Common generation failure scenarios include:
 * - Clock regression in timestamp-based generators (Snowflake, ULID)
 * - Insufficient entropy from the random number generator
 * - Sequence overflow in high-throughput scenarios
 * - Invalid node IDs in distributed generation contexts
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class GeneratorException extends RuntimeException implements MintException, ProvidesSolution
{
    // Abstract base class - concrete implementations provide specific factory methods

    public function getSolution(): Solution
    {
        /** @var BaseSolution $solution */
        $solution = BaseSolution::create('Review package usage and configuration.');

        return $solution
            ->setSolutionDescription('Exception: '.$this->getMessage())
            ->setDocumentationLinks([
                'Package documentation' => 'https://github.com/cline/mint',
            ]);
    }
}
