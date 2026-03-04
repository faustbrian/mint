<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Generators;

use Cline\Mint\Algorithms\TypeIdAlgorithm;
use Cline\Mint\Contracts\GeneratorInterface;
use Cline\Mint\Support\Identifiers\TypeId;
use Override;

use function mb_strrpos;
use function mb_substr;

/**
 * TypeID generator.
 *
 * Generates type-safe, K-sortable identifiers with a prefix.
 * Based on UUIDv7 with a type prefix for better developer experience.
 * Format: prefix_base32suffix (e.g., user_01h455vb4pex5vsknk084sn02q)
 *
 * Compliant with the official TypeID specification.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://github.com/jetify-com/typeid/tree/main/spec
 * @psalm-immutable
 */
final readonly class TypeIdGenerator implements GeneratorInterface
{
    private TypeIdAlgorithm $algorithm;

    /**
     * Create a new TypeID generator.
     *
     * @param string $prefix The type prefix (e.g., 'user', 'order', 'post')
     */
    public function __construct(
        private string $prefix = '',
    ) {
        $this->algorithm = new TypeIdAlgorithm($prefix);
    }

    /**
     * Generate a new TypeID.
     */
    #[Override()]
    public function generate(): TypeId
    {
        ['value' => $value, 'bytes' => $bytes] = $this->algorithm->generate();

        // Extract suffix from the value
        $suffix = $this->extractSuffix($value);

        return new TypeId($value, $bytes, $this->prefix, $suffix);
    }

    /**
     * Generate a TypeID with a specific prefix.
     *
     * @param string $prefix The type prefix
     */
    public function withPrefix(string $prefix): TypeId
    {
        $generator = new self($prefix);

        return $generator->generate();
    }

    /**
     * Parse a TypeID string.
     */
    #[Override()]
    public function parse(string $value): TypeId
    {
        ['value' => $parsedValue, 'bytes' => $bytes] = $this->algorithm->parse($value);

        // Extract prefix and suffix
        ['prefix' => $prefix, 'suffix' => $suffix] = $this->parseTypeIdString($parsedValue);

        return new TypeId($parsedValue, $bytes, $prefix, $suffix);
    }

    /**
     * Check if a string is a valid TypeID.
     *
     * Strict validation: does NOT normalize case.
     */
    #[Override()]
    public function isValid(string $value): bool
    {
        return $this->algorithm->isValid($value);
    }

    /**
     * Get the generator name.
     */
    #[Override()]
    public function getName(): string
    {
        return 'typeid';
    }

    /**
     * Get the configured prefix.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Extract suffix from a TypeID value.
     */
    private function extractSuffix(string $value): string
    {
        $underscorePos = mb_strrpos($value, '_');

        if ($underscorePos === false) {
            return $value;
        }

        return mb_substr($value, $underscorePos + 1);
    }

    /**
     * Parse a TypeID string into prefix and suffix.
     *
     * @return array{prefix: string, suffix: string}
     */
    private function parseTypeIdString(string $value): array
    {
        $underscorePos = mb_strrpos($value, '_');

        if ($underscorePos === false) {
            return ['prefix' => '', 'suffix' => $value];
        }

        return [
            'prefix' => mb_substr($value, 0, $underscorePos),
            'suffix' => mb_substr($value, $underscorePos + 1),
        ];
    }
}
