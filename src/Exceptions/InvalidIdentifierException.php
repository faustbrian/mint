<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Exceptions;

use InvalidArgumentException;

/**
 * Base exception for all invalid identifier format validation errors.
 *
 * This abstract exception serves as the parent class for all identifier format
 * validation failures in the Mint package. Each identifier type (UUID, ULID,
 * Hashid, etc.) has specific format requirements, and this exception hierarchy
 * provides type-specific error handling for parsing and validation failures.
 *
 * Common validation failure scenarios include:
 * - Invalid character sequences that don't match the identifier's alphabet
 * - Incorrect length or structure for the identifier type
 * - Checksum or validation failures for self-validating identifier formats
 * - Encoding/decoding failures due to corrupted or tampered identifier strings
 *
 * These are configuration/input errors (InvalidArgumentException) rather than
 * runtime generation failures (GeneratorException).
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class InvalidIdentifierException extends InvalidArgumentException implements MintException
{
    // Abstract base class - concrete implementations provide specific factory methods
}
