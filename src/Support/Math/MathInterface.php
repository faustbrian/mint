<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Support\Math;

use GMP;

/**
 * Interface for arbitrary-precision arithmetic implementations.
 *
 * Defines a common contract for performing mathematical operations on
 * integers that may exceed PHP's native integer limits (PHP_INT_MAX).
 * Implementations use either GMP or BCMath extensions for computation.
 *
 * All operations accept and return values that may be represented as
 * integers, strings, or native extension types (like GMP resources),
 * depending on the implementation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface MathInterface
{
    /**
     * Add two arbitrary-precision integers.
     *
     * @param GMP|int|string $a First operand (supports native int, numeric string, or GMP resource)
     * @param GMP|int|string $b Second operand (supports native int, numeric string, or GMP resource)
     *
     * @return string Sum as a numeric string
     */
    public function add($a, $b);

    /**
     * Multiply two arbitrary-precision integers.
     *
     * @param GMP|int|string $a First operand (multiplicand)
     * @param GMP|int|string $b Second operand (multiplier)
     *
     * @return string Product as a numeric string
     */
    public function multiply($a, $b);

    /**
     * Divide two arbitrary-precision integers.
     *
     * Performs integer division, returning only the quotient without remainder.
     *
     * @param GMP|int|string $a Dividend (numerator)
     * @param GMP|int|string $b Divisor (denominator)
     *
     * @return string Quotient as a numeric string
     */
    public function divide($a, $b);

    /**
     * Calculate modulo of arbitrary-precision integers.
     *
     * Returns the remainder after dividing $n by $d.
     *
     * @param GMP|int|string $n Dividend
     * @param GMP|int|string $d Divisor (modulus)
     *
     * @return string Remainder as a numeric string
     */
    public function mod($n, $d);

    /**
     * Compare two arbitrary-precision integers.
     *
     * Determines if the first value is strictly greater than the second.
     *
     * @param GMP|int|string $a First value to compare
     * @param GMP|int|string $b Second value to compare
     *
     * @return bool True if $a > $b, false otherwise
     */
    public function greaterThan($a, $b);

    /**
     * Convert arbitrary-precision value to PHP native integer.
     *
     * Converts the value to a standard PHP integer. Values exceeding PHP_INT_MAX
     * will overflow and produce incorrect results - use with caution for large values.
     *
     * @param GMP|int|string $a Value to convert
     *
     * @return int Integer representation (may overflow for values > PHP_INT_MAX)
     */
    public function intval($a);

    /**
     * Convert arbitrary-precision value to string representation.
     *
     * Returns the numeric value as a base-10 string, safe for all value sizes.
     *
     * @param GMP|int|string $a Value to convert
     *
     * @return string String representation of the number
     */
    public function strval($a);

    /**
     * Convert PHP integer to implementation-specific arbitrary-precision value.
     *
     * Initializes an arbitrary-precision value from a standard PHP integer or
     * numeric string. The return type varies by implementation (string for BCMath,
     * GMP resource for GMP).
     *
     * @param int|string $a Value to convert
     *
     * @return GMP|int|string Implementation-specific representation
     */
    public function get($a);
}
