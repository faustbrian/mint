<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Conductors;

use Cline\Mint\Enums\IdentifierType;
use Cline\Mint\Generators\TypeIdGenerator;
use Cline\Mint\MintManager;
use Cline\Mint\Support\Identifiers\TypeId;

/**
 * Fluent conductor for TypeID generation and parsing.
 *
 * TypeIDs are type-safe, K-sortable identifiers that combine a type prefix
 * with a UUIDv7 suffix. The prefix makes IDs self-documenting.
 *
 * ```php
 * $typeId = Mint::typeId()->prefix('user')->generate();
 * $typeId = Mint::typeId()->prefix('order')->generate();
 * $parsed = Mint::typeId()->parse($string);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class TypeIdConductor
{
    /**
     * Create a new TypeID conductor instance.
     *
     * @param MintManager $manager Central manager instance that coordinates identifier
     *                             generation across the Mint library. Handles generator
     *                             instantiation, configuration management, and provides
     *                             access to the underlying generator implementations.
     * @param string      $prefix  Type prefix for generated TypeIDs. Must be 0-63 lowercase
     *                             characters (a-z). The prefix makes IDs self-documenting by
     *                             indicating the entity type (e.g., 'user', 'post', 'order').
     *                             Empty string generates prefix-less TypeIDs. Prefix is separated
     *                             from the UUIDv7 suffix by an underscore in the final ID.
     */
    public function __construct(
        private MintManager $manager,
        private string $prefix = '',
    ) {}

    /**
     * Set the type prefix (0-63 lowercase characters).
     *
     * Returns a new conductor instance with the specified type prefix. The prefix
     * makes TypeIDs self-documenting and type-safe by embedding the entity type
     * directly in the identifier. Must contain only lowercase letters (a-z) and
     * be 63 characters or fewer.
     *
     * @param  string $prefix Type prefix (0-63 lowercase a-z characters)
     * @return self   New conductor instance with updated prefix
     */
    public function prefix(string $prefix): self
    {
        return new self($this->manager, $prefix);
    }

    /**
     * Generate a new TypeID with the configured prefix.
     *
     * Creates a type-safe, K-sortable identifier combining the configured prefix
     * with a UUIDv7 suffix. The UUIDv7 provides time-based sorting and global
     * uniqueness. Format is "prefix_suffix" where suffix is a base32-encoded
     * UUIDv7. Prefix-less TypeIDs (when prefix is empty) contain only the suffix.
     *
     * @return TypeId New TypeID identifier object
     */
    public function generate(): TypeId
    {
        /** @var TypeIdGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::TypeId, [
            'prefix' => $this->prefix,
        ]);

        return $generator->generate();
    }

    /**
     * Parse a TypeID string.
     *
     * Converts a TypeID string representation into a TypeID object for inspection
     * and manipulation. Extracts and validates the prefix and suffix components.
     * Allows access to the embedded UUIDv7 for timestamp extraction and type
     * verification through the prefix.
     *
     * @param  string $value TypeID string to parse (format: "prefix_suffix" or just "suffix")
     * @return TypeId Parsed TypeID identifier object
     */
    public function parse(string $value): TypeId
    {
        /** @var TypeIdGenerator $generator */
        $generator = $this->manager->getGenerator(IdentifierType::TypeId);

        return $generator->parse($value);
    }

    /**
     * Check if a string is a valid TypeID.
     *
     * Validates whether a given string conforms to the TypeID format specification.
     * Checks prefix format (lowercase a-z, 0-63 chars), suffix validity (base32
     * UUIDv7), and overall structure. Does not verify the prefix matches any
     * expected type.
     *
     * @param  string $value String to validate
     * @return bool   True if the string is a valid TypeID format, false otherwise
     */
    public function isValid(string $value): bool
    {
        return $this->manager->getGenerator(IdentifierType::TypeId)->isValid($value);
    }
}
