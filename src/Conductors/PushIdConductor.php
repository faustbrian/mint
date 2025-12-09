<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Conductors;

use Cline\Mint\Enums\IdentifierType;
use Cline\Mint\Generators\PushIdGenerator;
use Cline\Mint\MintManager;
use Cline\Mint\Support\Identifiers\PushId;

/**
 * Fluent conductor for Firebase Push ID generation and parsing.
 *
 * Push IDs are 120-bit identifiers used by Firebase Realtime Database.
 * They are chronologically sortable and encoded as 20 character strings.
 *
 * ```php
 * $pushId = Mint::pushId()->generate();
 * $parsed = Mint::pushId()->parse($string);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class PushIdConductor
{
    /**
     * Create a new Push ID conductor instance.
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
     * Generate a new Push ID.
     *
     * Creates a Firebase-compatible Push ID consisting of 120 bits of data.
     * Combines a 48-bit timestamp with 72 bits of randomness, providing
     * chronological sorting and high collision resistance. Designed to allow
     * multiple clients to generate IDs offline while maintaining sort order.
     * Encoded as a 20-character string using a custom base64-like alphabet.
     *
     * @return PushId New Push ID identifier object
     */
    public function generate(): PushId
    {
        /** @var PushIdGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::PushId);

        return $generator->generate();
    }

    /**
     * Parse a Push ID string.
     *
     * Converts a Push ID string representation into a Push ID object for
     * inspection and manipulation. Allows extraction of the embedded timestamp
     * for chronological analysis and sorting verification.
     *
     * @param  string $value Push ID string to parse (20 characters)
     * @return PushId Parsed Push ID identifier object
     */
    public function parse(string $value): PushId
    {
        /** @var PushIdGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::PushId);

        return $generator->parse($value);
    }

    /**
     * Check if a string is a valid Push ID.
     *
     * Validates whether a given string conforms to the Push ID format
     * specification. Checks for correct length (20 characters) and valid
     * character encoding using Firebase's custom alphabet.
     *
     * @param  string $value String to validate
     * @return bool   True if the string is a valid Push ID format, false otherwise
     */
    public function isValid(string $value): bool
    {
        return $this->manager->getGenerator(IdentifierType::PushId)->isValid($value);
    }
}
