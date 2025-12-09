<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Conductors;

use Cline\Mint\Enums\IdentifierType;
use Cline\Mint\Generators\KsuidGenerator;
use Cline\Mint\MintManager;
use Cline\Mint\Support\Identifiers\Ksuid;

/**
 * Fluent conductor for KSUID generation and parsing.
 *
 * KSUIDs are K-Sortable Unique Identifiers - 160-bit identifiers that
 * are sortable by creation time with 128 bits of random payload.
 *
 * ```php
 * $ksuid = Mint::ksuid()->generate();
 * $parsed = Mint::ksuid()->parse($string);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class KsuidConductor
{
    /**
     * Create a new KSUID conductor instance.
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
     * Generate a new KSUID.
     *
     * Creates a K-Sortable Unique Identifier with 160 bits of entropy. Combines
     * a 32-bit timestamp (seconds since epoch) with 128 bits of random data,
     * providing both time-based sorting and collision resistance. Encoded as
     * a 27-character base62 string.
     *
     * @return Ksuid New KSUID identifier object
     */
    public function generate(): Ksuid
    {
        /** @var KsuidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Ksuid);

        return $generator->generate();
    }

    /**
     * Parse a KSUID string.
     *
     * Converts a KSUID string representation into a KSUID object for inspection
     * and manipulation. Allows extraction of the embedded timestamp and access
     * to the payload components.
     *
     * @param  string $value KSUID string to parse (27 base62 characters)
     * @return Ksuid  Parsed KSUID identifier object
     */
    public function parse(string $value): Ksuid
    {
        /** @var KsuidGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::Ksuid);

        return $generator->parse($value);
    }

    /**
     * Check if a string is a valid KSUID.
     *
     * Validates whether a given string conforms to the KSUID format specification.
     * Checks for correct length and valid base62 encoding.
     *
     * @param  string $value String to validate
     * @return bool   True if the string is a valid KSUID format, false otherwise
     */
    public function isValid(string $value): bool
    {
        return $this->manager->getGenerator(IdentifierType::Ksuid)->isValid($value);
    }
}
