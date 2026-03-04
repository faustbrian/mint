<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint;

use Cline\Mint\Conductors\Cuid2Conductor;
use Cline\Mint\Conductors\HashidConductor;
use Cline\Mint\Conductors\KsuidConductor;
use Cline\Mint\Conductors\NanoIdConductor;
use Cline\Mint\Conductors\ObjectIdConductor;
use Cline\Mint\Conductors\PushIdConductor;
use Cline\Mint\Conductors\SnowflakeConductor;
use Cline\Mint\Conductors\SqidConductor;
use Cline\Mint\Conductors\TimeflakeConductor;
use Cline\Mint\Conductors\TypeIdConductor;
use Cline\Mint\Conductors\UlidConductor;
use Cline\Mint\Conductors\UuidConductor;
use Cline\Mint\Conductors\XidConductor;
use Cline\Mint\Contracts\GeneratorInterface;
use Cline\Mint\Enums\IdentifierType;
use Cline\Mint\Enums\UuidVersion;
use Cline\Mint\Generators\Cuid2Generator;
use Cline\Mint\Generators\HashidGenerator;
use Cline\Mint\Generators\KsuidGenerator;
use Cline\Mint\Generators\NanoIdGenerator;
use Cline\Mint\Generators\ObjectIdGenerator;
use Cline\Mint\Generators\PushIdGenerator;
use Cline\Mint\Generators\SnowflakeGenerator;
use Cline\Mint\Generators\SqidGenerator;
use Cline\Mint\Generators\TimeflakeGenerator;
use Cline\Mint\Generators\TypeIdGenerator;
use Cline\Mint\Generators\UlidGenerator;
use Cline\Mint\Generators\UuidGenerator;
use Cline\Mint\Generators\XidGenerator;
use Cline\Mint\Support\Identifiers\Snowflake;
use Illuminate\Container\Attributes\Singleton;

use function array_merge;
use function md5;
use function serialize;

/**
 * Main manager class for identifier generation.
 *
 * Provides a fluent conductor API for generating various types of unique identifiers.
 * Each conductor provides type-specific configuration and generation methods.
 *
 * ```php
 * Mint::uuid()->v7()->generate();
 * Mint::snowflake()->nodeId(1)->generate();
 * Mint::sqid()->minLength(10)->encode([1, 2, 3]);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final class MintManager
{
    /**
     * Cached generator instances keyed by type and configuration hash.
     *
     * Generators are expensive to instantiate (especially those with custom
     * alphabets or configuration), so we cache them per unique configuration
     * to avoid repeated object creation within the same request.
     *
     * @var array<string, GeneratorInterface>
     */
    private array $generators = [];

    /**
     * Create a new Mint manager instance.
     *
     * @param array<string, mixed> $config Configuration options from config/mint.php file.
     *                                     Should contain type-specific settings indexed by
     *                                     identifier type (e.g., 'uuid' => ['version' => 'v7']).
     *                                     Merged with runtime configuration when creating generators.
     */
    public function __construct(
        private readonly array $config = [],
    ) {}

    // =========================================================================
    // Conductor Factories
    // =========================================================================

    /**
     * Begin a fluent chain for UUID generation.
     *
     * ```php
     * Mint::uuid()->v7()->generate();
     * Mint::uuid()->v4()->generate();
     * Mint::uuid()->parse($string);
     * ```
     */
    public function uuid(): UuidConductor
    {
        return new UuidConductor($this);
    }

    /**
     * Begin a fluent chain for ULID generation.
     *
     * ```php
     * Mint::ulid()->generate();
     * Mint::ulid()->parse($string);
     * ```
     */
    public function ulid(): UlidConductor
    {
        return new UlidConductor($this);
    }

    /**
     * Begin a fluent chain for Snowflake ID generation.
     *
     * ```php
     * Mint::snowflake()->nodeId(1)->generate();
     * Mint::snowflake()->nodeId(1)->epoch(1609459200000)->generate();
     * Mint::snowflake()->parse($string);
     * ```
     */
    public function snowflake(): SnowflakeConductor
    {
        return new SnowflakeConductor($this);
    }

    /**
     * Begin a fluent chain for NanoID generation.
     *
     * ```php
     * Mint::nanoid()->generate();
     * Mint::nanoid()->length(16)->generate();
     * Mint::nanoid()->alphabet('abc123')->generate();
     * ```
     */
    public function nanoid(): NanoIdConductor
    {
        return new NanoIdConductor($this);
    }

    /**
     * Begin a fluent chain for Sqid encoding.
     *
     * ```php
     * Mint::sqid()->encode([1, 2, 3]);
     * Mint::sqid()->minLength(10)->encode([42]);
     * Mint::sqid()->decode($string);
     * ```
     */
    public function sqid(): SqidConductor
    {
        return new SqidConductor($this);
    }

    /**
     * Begin a fluent chain for Hashid encoding.
     *
     * ```php
     * Mint::hashid()->salt('my-salt')->encode([1, 2, 3]);
     * Mint::hashid()->minLength(10)->encode([42]);
     * Mint::hashid()->decode($string);
     * ```
     */
    public function hashid(): HashidConductor
    {
        return new HashidConductor($this);
    }

    /**
     * Begin a fluent chain for KSUID generation.
     *
     * ```php
     * Mint::ksuid()->generate();
     * Mint::ksuid()->parse($string);
     * ```
     */
    public function ksuid(): KsuidConductor
    {
        return new KsuidConductor($this);
    }

    /**
     * Begin a fluent chain for CUID2 generation.
     *
     * ```php
     * Mint::cuid2()->generate();
     * Mint::cuid2()->length(32)->generate();
     * ```
     */
    public function cuid2(): Cuid2Conductor
    {
        return new Cuid2Conductor($this);
    }

    /**
     * Begin a fluent chain for TypeID generation.
     *
     * ```php
     * Mint::typeId()->prefix('user')->generate();
     * Mint::typeId()->parse($string);
     * ```
     */
    public function typeId(): TypeIdConductor
    {
        return new TypeIdConductor($this);
    }

    /**
     * Begin a fluent chain for XID generation.
     *
     * ```php
     * Mint::xid()->generate();
     * Mint::xid()->parse($string);
     * ```
     */
    public function xid(): XidConductor
    {
        return new XidConductor($this);
    }

    /**
     * Begin a fluent chain for ObjectID generation.
     *
     * ```php
     * Mint::objectId()->generate();
     * Mint::objectId()->parse($string);
     * ```
     */
    public function objectId(): ObjectIdConductor
    {
        return new ObjectIdConductor($this);
    }

    /**
     * Begin a fluent chain for Push ID generation.
     *
     * ```php
     * Mint::pushId()->generate();
     * Mint::pushId()->parse($string);
     * ```
     */
    public function pushId(): PushIdConductor
    {
        return new PushIdConductor($this);
    }

    /**
     * Begin a fluent chain for Timeflake generation.
     *
     * ```php
     * Mint::timeflake()->generate();
     * Mint::timeflake()->parse($string);
     * ```
     */
    public function timeflake(): TimeflakeConductor
    {
        return new TimeflakeConductor($this);
    }

    // =========================================================================
    // Generator Access (Used by Conductors)
    // =========================================================================

    /**
     * Get a generator instance by type with optional configuration.
     *
     * Used internally by conductors to access configured generator instances.
     * Generators are cached by a unique key combining type and configuration
     * to enable instance reuse while supporting dynamic configuration.
     *
     * @internal This method is intended for internal use by conductor classes
     *
     * @param IdentifierType       $type   The identifier type to generate (UUID, ULID, etc.)
     * @param array<string, mixed> $config Runtime configuration overrides that merge with
     *                                     base configuration from constructor. Allows conductors
     *                                     to pass custom settings like alphabet, length, or version.
     *
     * @return GeneratorInterface The generator instance (cached or newly created)
     */
    public function getGenerator(IdentifierType $type, array $config = []): GeneratorInterface
    {
        $key = $this->getCacheKey($type, $config);

        return $this->generators[$key] ??= $this->createGenerator($type, $config);
    }

    // =========================================================================
    // Private Methods
    // =========================================================================

    /**
     * Create a new generator instance with merged configuration.
     *
     * Merges the base configuration (from constructor) with runtime overrides
     * and instantiates the appropriate generator class with type-specific
     * parameters extracted from the merged configuration.
     *
     * @param IdentifierType       $type   The identifier type to create a generator for
     * @param array<string, mixed> $config Runtime configuration overrides from the conductor,
     *                                     merged with base config to produce final settings
     *
     * @return GeneratorInterface A newly instantiated generator configured for the type
     */
    private function createGenerator(IdentifierType $type, array $config): GeneratorInterface
    {
        /** @var array<string, mixed> $typeConfig */
        $typeConfig = $this->config[$type->value] ?? [];
        $merged = array_merge($typeConfig, $config);

        /** @var UuidVersion $uuidVersion */
        $uuidVersion = $merged['version'] ?? UuidVersion::V7;

        /** @var int $snowflakeNodeId */
        $snowflakeNodeId = $merged['node_id'] ?? 0;

        /** @var int $snowflakeEpoch */
        $snowflakeEpoch = $merged['epoch'] ?? Snowflake::DEFAULT_EPOCH;

        /** @var int $nanoidLength */
        $nanoidLength = $merged['length'] ?? NanoIdGenerator::DEFAULT_LENGTH;

        /** @var string $nanoidAlphabet */
        $nanoidAlphabet = $merged['alphabet'] ?? NanoIdGenerator::DEFAULT_ALPHABET;

        /** @var int $cuid2Length */
        $cuid2Length = $merged['length'] ?? 24;

        /** @var string $typeIdPrefix */
        $typeIdPrefix = $merged['prefix'] ?? '';

        /** @var string $sqidAlphabet */
        $sqidAlphabet = $merged['alphabet'] ?? 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        /** @var int $sqidMinLength */
        $sqidMinLength = $merged['min_length'] ?? 0;

        /** @var array<string> $sqidBlocklist */
        $sqidBlocklist = $merged['blocklist'] ?? [];

        /** @var string $hashidSalt */
        $hashidSalt = $merged['salt'] ?? '';

        /** @var int $hashidMinLength */
        $hashidMinLength = $merged['min_length'] ?? 0;

        /** @var string $hashidAlphabet */
        $hashidAlphabet = $merged['alphabet'] ?? 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

        return match ($type) {
            IdentifierType::Uuid => new UuidGenerator($uuidVersion),
            IdentifierType::Ulid => new UlidGenerator(),
            IdentifierType::Snowflake => new SnowflakeGenerator($snowflakeNodeId, $snowflakeEpoch),
            IdentifierType::NanoId => new NanoIdGenerator($nanoidLength, $nanoidAlphabet),
            IdentifierType::Sqid => new SqidGenerator($sqidAlphabet, $sqidMinLength, $sqidBlocklist),
            IdentifierType::Hashid => new HashidGenerator($hashidSalt, $hashidMinLength, $hashidAlphabet),
            IdentifierType::Ksuid => new KsuidGenerator(),
            IdentifierType::Cuid2 => new Cuid2Generator($cuid2Length),
            IdentifierType::TypeId => new TypeIdGenerator($typeIdPrefix),
            IdentifierType::Xid => new XidGenerator(),
            IdentifierType::ObjectId => new ObjectIdGenerator(),
            IdentifierType::PushId => new PushIdGenerator(),
            IdentifierType::Timeflake => new TimeflakeGenerator(),
        };
    }

    /**
     * Generate a unique cache key for a generator configuration.
     *
     * Creates a string key combining the identifier type and configuration hash.
     * When configuration is empty, returns just the type value for simpler keys.
     * Otherwise, appends an MD5 hash of the serialized configuration.
     *
     * @param IdentifierType       $type   The identifier type (used as base key)
     * @param array<string, mixed> $config The configuration to hash (empty array for defaults)
     *
     * @return string A unique cache key like 'uuid' or 'snowflake_a1b2c3d4...'
     */
    private function getCacheKey(IdentifierType $type, array $config): string
    {
        if ($config === []) {
            return $type->value;
        }

        return $type->value.'_'.md5(serialize($config));
    }
}
