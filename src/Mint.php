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
use Illuminate\Support\Facades\Facade;

/**
 * Mint facade for Laravel applications.
 *
 * Provides static access to the MintManager for generating unique identifiers
 * via fluent conductor chains. Each method returns a conductor that offers
 * type-specific configuration and generation capabilities.
 *
 * ```php
 * // Generate UUIDs with version selection
 * Mint::uuid()->v7()->generate();
 * Mint::uuid()->v4()->generate();
 *
 * // Generate Snowflake IDs with custom node
 * Mint::snowflake()->nodeId(1)->generate();
 *
 * // Encode/decode with Sqids
 * Mint::sqid()->minLength(10)->encode([1, 2, 3]);
 * Mint::sqid()->decode($encoded);
 * ```
 *
 * @method static Cuid2Conductor     cuid2()     Begin a fluent chain for CUID2 generation
 * @method static HashidConductor    hashid()    Begin a fluent chain for Hashid encoding
 * @method static KsuidConductor     ksuid()     Begin a fluent chain for KSUID generation
 * @method static NanoIdConductor    nanoid()    Begin a fluent chain for NanoID generation
 * @method static ObjectIdConductor  objectId()  Begin a fluent chain for ObjectID generation
 * @method static PushIdConductor    pushId()    Begin a fluent chain for Push ID generation
 * @method static SnowflakeConductor snowflake() Begin a fluent chain for Snowflake ID generation
 * @method static SqidConductor      sqid()      Begin a fluent chain for Sqid encoding
 * @method static TimeflakeConductor timeflake() Begin a fluent chain for Timeflake generation
 * @method static TypeIdConductor    typeId()    Begin a fluent chain for TypeID generation
 * @method static UlidConductor      ulid()      Begin a fluent chain for ULID generation
 * @method static UuidConductor      uuid()      Begin a fluent chain for UUID generation
 * @method static XidConductor       xid()       Begin a fluent chain for XID generation
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see MintManager
 */
final class Mint extends Facade
{
    /**
     * Get the registered service container binding name.
     *
     * Returns the class name used to resolve the MintManager instance
     * from Laravel's service container.
     *
     * @return string The fully-qualified class name of MintManager
     */
    protected static function getFacadeAccessor(): string
    {
        return MintManager::class;
    }
}
