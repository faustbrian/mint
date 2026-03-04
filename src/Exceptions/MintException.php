<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Exceptions;

use Throwable;

/**
 * Marker interface for all Mint package exceptions.
 *
 * This interface allows consumers to catch any exception thrown by the Mint
 * package using a single catch block. All Mint-specific exceptions implement
 * this interface, enabling clean separation between Mint errors and other
 * application exceptions.
 *
 * ```php
 * try {
 *     $id = Mint::generate('uuid');
 * } catch (MintException $e) {
 *     // Handle any Mint-related error
 *     Log::error('ID generation failed', ['error' => $e->getMessage()]);
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface MintException extends Throwable
{
    // Marker interface - no methods required
}
