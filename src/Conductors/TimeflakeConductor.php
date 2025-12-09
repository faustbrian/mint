<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Conductors;

use Cline\Mint\Enums\IdentifierType;
use Cline\Mint\Generators\TimeflakeGenerator;
use Cline\Mint\MintManager;
use Cline\Mint\Support\Identifiers\Timeflake;

/**
 * Fluent conductor for Timeflake generation and parsing.
 *
 * Timeflakes are 128-bit, sortable, URL-safe identifiers. They consist of
 * a 48-bit timestamp and 80-bit random payload, encoded as base62.
 *
 * ```php
 * $timeflake = Mint::timeflake()->generate();
 * $parsed = Mint::timeflake()->parse($string);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class TimeflakeConductor
{
    /**
     * Create a new Timeflake conductor instance.
     *
     * @param MintManager $manager Central manager instance that coordinates identifier
     *                             generation across the Mint library. Handles generator
     *                             instantiation, configuration management, and provides
     *                             access to the underlying generator implementations.
     */
    public function __construct(
        private MintManager $manager,
    ) {}

    /**
     * Generate a new Timeflake.
     *
     * Creates a 128-bit sortable, URL-safe identifier consisting of a 48-bit
     * timestamp (milliseconds since epoch) and 80-bit random payload. Provides
     * natural chronological ordering while maintaining high collision resistance.
     * Encoded as a 22-character base62 string for compactness and URL safety.
     *
     * @return Timeflake New Timeflake identifier object
     */
    public function generate(): Timeflake
    {
        /** @var TimeflakeGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Timeflake);

        return $generator->generate();
    }

    /**
     * Parse a Timeflake string.
     *
     * Converts a Timeflake string representation into a Timeflake object for
     * inspection and manipulation. Allows extraction of the embedded timestamp
     * for temporal analysis and the random payload for uniqueness verification.
     *
     * @param  string    $value Timeflake string to parse (22 base62 characters)
     * @return Timeflake Parsed Timeflake identifier object
     */
    public function parse(string $value): Timeflake
    {
        /** @var TimeflakeGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Timeflake);

        return $generator->parse($value);
    }

    /**
     * Check if a string is a valid Timeflake.
     *
     * Validates whether a given string conforms to the Timeflake format
     * specification. Checks for correct length (22 characters) and valid
     * base62 encoding.
     *
     * @param  string $value String to validate
     * @return bool   True if the string is a valid Timeflake format, false otherwise
     */
    public function isValid(string $value): bool
    {
        return $this->manager->getGenerator(IdentifierType::Timeflake)->isValid($value);
    }
}
