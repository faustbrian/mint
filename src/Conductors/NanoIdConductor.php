<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Conductors;

use Cline\Mint\Enums\IdentifierType;
use Cline\Mint\Generators\NanoIdGenerator;
use Cline\Mint\MintManager;
use Cline\Mint\Support\Identifiers\NanoId;

/**
 * Fluent conductor for NanoID generation and parsing.
 *
 * NanoIDs are compact, URL-safe, unique identifiers with customizable
 * length and alphabet. Default length is 21 characters.
 *
 * ```php
 * $nanoid = Mint::nanoid()->generate();
 * $nanoid = Mint::nanoid()->length(16)->generate();
 * $nanoid = Mint::nanoid()->alphabet('abc123')->generate();
 * $parsed = Mint::nanoid()->parse($string);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class NanoIdConductor
{
    /**
     * Create a new NanoID conductor instance.
     *
     * @param MintManager $manager  Central manager instance that coordinates identifier
     *                              generation across the Mint library. Handles generator
     *                              instantiation, configuration management, and provides
     *                              access to the underlying generator implementations.
     * @param null|int    $length   Target length for generated NanoID strings. When null,
     *                              uses the default length of 21 characters. Shorter lengths
     *                              produce more compact IDs but reduce collision resistance.
     *                              Common lengths range from 8 to 21 characters.
     * @param null|string $alphabet Custom character set to use for generation. When null,
     *                              uses the default URL-safe alphabet (A-Za-z0-9_-). Custom
     *                              alphabets allow tailoring IDs to specific requirements,
     *                              such as avoiding ambiguous characters or matching brand
     *                              guidelines. Must contain at least 2 unique characters.
     */
    public function __construct(
        private MintManager $manager,
        private ?int $length = null,
        private ?string $alphabet = null,
    ) {}

    /**
     * Set the length of the generated NanoID.
     *
     * Returns a new conductor instance with the specified length configuration.
     * Adjusting length allows balancing between ID compactness and collision
     * probability. The default length of 21 provides ~1 billion years to have
     * a 1% collision probability at 1000 IDs/hour.
     *
     * @param  int  $length Desired length for generated NanoIDs
     * @return self New conductor instance with updated length configuration
     */
    public function length(int $length): self
    {
        return new self($this->manager, $length, $this->alphabet);
    }

    /**
     * Set a custom alphabet for generation.
     *
     * Returns a new conductor instance with the specified alphabet. Custom
     * alphabets enable control over ID appearance and compatibility. Must
     * contain at least 2 unique characters for proper randomness distribution.
     *
     * @param  string $alphabet Custom character set (minimum 2 unique characters)
     * @return self   New conductor instance with updated alphabet
     */
    public function alphabet(string $alphabet): self
    {
        return new self($this->manager, $this->length, $alphabet);
    }

    /**
     * Generate a new NanoID.
     *
     * Creates a compact, URL-safe, unique identifier using cryptographically
     * strong random number generation. NanoIDs are comparable to UUIDs in
     * collision resistance but are shorter and more URL-friendly.
     *
     * @return NanoId New NanoID identifier object
     */
    public function generate(): NanoId
    {
        $config = [];

        if ($this->length !== null) {
            $config['length'] = $this->length;
        }

        if ($this->alphabet !== null) {
            $config['alphabet'] = $this->alphabet;
        }

        /** @var NanoIdGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::NanoId, $config);

        return $generator->generate();
    }

    /**
     * Parse a NanoID string.
     *
     * Converts a NanoID string representation into a NanoID object for
     * inspection and manipulation. Validates the string format during parsing.
     *
     * @param  string $value NanoID string to parse
     * @return NanoId Parsed NanoID identifier object
     */
    public function parse(string $value): NanoId
    {
        /** @var NanoIdGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::NanoId);

        return $generator->parse($value);
    }

    /**
     * Check if a string is a valid NanoID.
     *
     * Validates whether a given string could be a valid NanoID based on
     * character set constraints. Note that validation is alphabet-agnostic
     * when no custom alphabet is configured.
     *
     * @param  string $value String to validate
     * @return bool   True if the string is a valid NanoID format, false otherwise
     */
    public function isValid(string $value): bool
    {
        return $this->manager->getGenerator(IdentifierType::NanoId)->isValid($value);
    }
}
