<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Support\Math;

use function bcadd;
use function bccomp;
use function bcdiv;
use function bcmod;
use function bcmul;

/**
 * BCMath-based arbitrary-precision math implementation.
 *
 * Provides arbitrary-precision arithmetic operations using PHP's BCMath
 * extension. Used as a fallback when GMP is not available. BCMath is
 * generally slower than GMP but more widely available.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://www.php.net/manual/en/book.bc.php
 */
final class BCMath implements MathInterface
{
    /**
     * Add two arbitrary-precision integers.
     *
     * @param int|string $a First operand
     * @param int|string $b Second operand
     *
     * @return string Sum as a numeric string
     */
    public function add($a, $b): string
    {
        return bcadd((string) $a, (string) $b, 0);
    }

    /**
     * Multiply two arbitrary-precision integers.
     *
     * @param int|string $a First operand
     * @param int|string $b Second operand
     *
     * @return string Product as a numeric string
     */
    public function multiply($a, $b): string
    {
        return bcmul((string) $a, (string) $b, 0);
    }

    /**
     * Divide two arbitrary-precision integers.
     *
     * Performs integer division (truncates toward zero).
     *
     * @param int|string $a Dividend
     * @param int|string $b Divisor
     *
     * @return string Quotient as a numeric string
     */
    public function divide($a, $b): string
    {
        return bcdiv((string) $a, (string) $b, 0);
    }

    /**
     * Calculate modulo of arbitrary-precision integers.
     *
     * @param int|string $n Dividend
     * @param int|string $d Divisor
     *
     * @return string Remainder as a numeric string
     */
    public function mod($n, $d): string
    {
        return bcmod((string) $n, (string) $d);
    }

    /**
     * Compare two arbitrary-precision integers.
     *
     * @param int|string $a First value
     * @param int|string $b Second value
     *
     * @return bool True if $a is greater than $b
     */
    public function greaterThan($a, $b): bool
    {
        return bccomp((string) $a, (string) $b, 0) > 0;
    }

    /**
     * Convert arbitrary-precision value to PHP integer.
     *
     * @param int|string $a Value to convert
     *
     * @return int Integer representation (may overflow for large values)
     */
    public function intval($a): int
    {
        return (int) $a;
    }

    /**
     * Convert arbitrary-precision value to string.
     *
     * @param int|string $a Value to convert
     *
     * @return string String representation
     */
    public function strval($a)
    {
        return $a;
    }

    /**
     * Convert PHP integer to arbitrary-precision value.
     *
     * For BCMath, this is a no-op as strings are used directly.
     *
     * @param int|string $a Value to convert
     *
     * @return int|string The value unchanged
     */
    public function get($a)
    {
        return $a;
    }
}
