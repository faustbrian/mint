<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Contracts;

/**
 * Interface for identifier algorithm implementations.
 *
 * Defines the contract for pure algorithm implementations that handle the
 * mathematical and logical operations for generating unique identifiers.
 * Algorithms are responsible for the core computation logic, separated from
 * the generator orchestration layer.
 *
 * Implementations should be:
 * - Pure computational units (input → output)
 * - Free of identifier object creation
 * - Independently testable
 * - Reusable across different contexts
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface AlgorithmInterface
{
    /**
     * Generate raw identifier data.
     *
     * Produces the core identifier value using the algorithm's specific logic.
     * Returns raw data (string value and binary bytes) without wrapping in
     * identifier objects.
     *
     * @return array{value: string, bytes: string} The generated identifier data:
     *                                             - value: String representation
     *                                             - bytes: Binary representation
     */
    public function generate(): array;

    /**
     * Parse a string into raw identifier data.
     *
     * Validates and converts a string representation into the algorithm's
     * internal format without creating identifier objects.
     *
     * @param string $value The string representation to parse
     *
     * @return array{value: string, bytes: string} The parsed identifier data
     */
    public function parse(string $value): array;

    /**
     * Check if a string is valid for this algorithm.
     *
     * Performs format validation without full parsing overhead.
     *
     * @param string $value The string to validate
     *
     * @return bool True if the string conforms to valid format
     */
    public function isValid(string $value): bool;
}
