<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint\Generators;

use Cline\Mint\Algorithms\UlidAlgorithm;
use Cline\Mint\Contracts\GeneratorInterface;
use Cline\Mint\Support\Identifiers\Ulid;
use Override;

/**
 * ULID (Universally Unique Lexicographically Sortable Identifier) generator.
 *
 * Orchestrates ULID generation by delegating to UlidAlgorithm and wrapping
 * the results in Ulid identifier objects. Provides a high-level API for
 * generating, parsing, and validating ULIDs.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class UlidGenerator implements GeneratorInterface
{
    /**
     * The ULID algorithm instance.
     */
    private UlidAlgorithm $algorithm;

    /**
     * Create a new ULID generator.
     *
     * @param bool $monotonic Whether to generate monotonically increasing ULIDs
     */
    public function __construct(
        private bool $monotonic = true,
    ) {
        $this->algorithm = new UlidAlgorithm($this->monotonic);
    }

    /**
     * Generate a new ULID.
     */
    #[Override()]
    public function generate(): Ulid
    {
        $data = $this->algorithm->generate();

        return new Ulid($data['value'], $data['bytes']);
    }

    /**
     * Generate a ULID from a specific timestamp.
     *
     * @param int $timestamp Unix timestamp in milliseconds
     */
    public function fromTimestamp(int $timestamp): Ulid
    {
        $data = $this->algorithm->fromTimestamp($timestamp);

        return new Ulid($data['value'], $data['bytes']);
    }

    /**
     * Parse a ULID string.
     */
    #[Override()]
    public function parse(string $value): Ulid
    {
        $data = $this->algorithm->parse($value);

        return new Ulid($data['value'], $data['bytes']);
    }

    /**
     * Check if a string is a valid ULID.
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
        return 'ulid';
    }
}
