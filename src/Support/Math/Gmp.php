<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Support\Math;

use function gmp_add;
use function gmp_cmp;
use function gmp_div_q;
use function gmp_init;
use function gmp_intval;
use function gmp_mod;
use function gmp_mul;
use function gmp_strval;

/**
 * GMP-based arbitrary-precision math implementation.
 *
 * Provides arbitrary-precision arithmetic operations using PHP's GMP
 * (GNU Multiple Precision) extension. GMP offers superior performance
 * compared to BCMath and is the preferred implementation when available.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://www.php.net/manual/en/book.gmp.php
 */
final class Gmp implements MathInterface
{
    /**
     * Add two arbitrary-precision integers.
     *
     * @param \GMP|int|string $a First operand
     * @param \GMP|int|string $b Second operand
     *
     * @return string Sum as a numeric string
     */
    public function add($a, $b): string
    {
        return gmp_strval(gmp_add($a, $b));
    }

    /**
     * Multiply two arbitrary-precision integers.
     *
     * @param \GMP|int|string $a First operand
     * @param \GMP|int|string $b Second operand
     *
     * @return string Product as a numeric string
     */
    public function multiply($a, $b): string
    {
        return gmp_strval(gmp_mul($a, $b));
    }

    /**
     * Divide two arbitrary-precision integers.
     *
     * Performs integer division with quotient only (no remainder).
     *
     * @param \GMP|int|string $a Dividend
     * @param \GMP|int|string $b Divisor
     *
     * @return string Quotient as a numeric string
     */
    public function divide($a, $b): string
    {
        return gmp_strval(gmp_div_q($a, $b));
    }

    /**
     * Calculate modulo of arbitrary-precision integers.
     *
     * @param \GMP|int|string $n Dividend
     * @param \GMP|int|string $d Divisor
     *
     * @return string Remainder as a numeric string
     */
    public function mod($n, $d): string
    {
        return gmp_strval(gmp_mod($n, $d));
    }

    /**
     * Compare two arbitrary-precision integers.
     *
     * @param \GMP|int|string $a First value
     * @param \GMP|int|string $b Second value
     *
     * @return bool True if $a is greater than $b
     */
    public function greaterThan($a, $b): bool
    {
        return gmp_cmp($a, $b) > 0;
    }

    /**
     * Convert arbitrary-precision value to PHP integer.
     *
     * @param \GMP|int|string $a Value to convert
     *
     * @return int Integer representation (may overflow for large values)
     */
    public function intval($a): int
    {
        return gmp_intval($a);
    }

    /**
     * Convert arbitrary-precision value to string.
     *
     * @param \GMP|int|string $a Value to convert
     *
     * @return string String representation in base 10
     */
    public function strval($a): string
    {
        return gmp_strval($a);
    }

    /**
     * Convert PHP integer to GMP number resource.
     *
     * Creates a GMP number that can be used in subsequent GMP operations.
     *
     * @param int|string $a Value to convert
     *
     * @return \GMP GMP number resource
     */
    public function get($a): \GMP
    {
        return gmp_init($a);
    }
}
