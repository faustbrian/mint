## Table of Contents

1. [Overview](#doc-docs-readme) (`docs/README.md`)
2. [Hashid](#doc-docs-hashid) (`docs/hashid.md`)
3. [Ksuid](#doc-docs-ksuid) (`docs/ksuid.md`)
4. [Nanoid](#doc-docs-nanoid) (`docs/nanoid.md`)
5. [Other Identifiers](#doc-docs-other-identifiers) (`docs/other-identifiers.md`)
6. [Snowflake](#doc-docs-snowflake) (`docs/snowflake.md`)
7. [Sqid](#doc-docs-sqid) (`docs/sqid.md`)
8. [Typeid](#doc-docs-typeid) (`docs/typeid.md`)
9. [Ulid](#doc-docs-ulid) (`docs/ulid.md`)
10. [Uuid](#doc-docs-uuid) (`docs/uuid.md`)
<a id="doc-docs-readme"></a>

## Installation

Install via Composer:

```bash
composer require cline/mint
```

## What is Mint?

Mint is a unified identifier generation library for Laravel that provides a fluent API for generating, parsing, and validating various types of unique identifiers. Whether you need time-ordered UUIDs for database performance, compact NanoIDs for URLs, or type-prefixed TypeIDs for self-documenting APIs, Mint has you covered.

### Supported Identifier Types

| Type | Length | Sortable | Description |
|------|--------|----------|-------------|
| **UUID** | 36 chars | v1, v6, v7 | Universally Unique Identifier (RFC 4122/9562) |
| **ULID** | 26 chars | Yes | Universally Unique Lexicographically Sortable Identifier |
| **Snowflake** | 19 digits | Yes | Twitter-style 64-bit time-ordered identifiers |
| **NanoID** | 21 chars* | No | Compact, URL-safe random identifiers |
| **Sqid** | Variable | No | Encode/decode integers to short strings |
| **Hashid** | Variable | No | Encode/decode integers with salt |
| **TypeID** | Variable | Yes | Type-prefixed UUIDv7 (e.g., `user_01h455vb4pex5vsknk084sn02q`) |
| **KSUID** | 27 chars | Yes | K-Sortable Unique Identifier |
| **CUID2** | 24 chars* | No | Collision-resistant unique identifier |
| **XID** | 20 chars | Yes | Globally unique, sortable identifier |
| **ObjectID** | 24 chars | Yes | MongoDB-style 96-bit identifier |
| **PushID** | 20 chars | Yes | Firebase-style chronologically sortable identifier |
| **Timeflake** | 26 chars | Yes | 128-bit time-sortable identifier |

*Default length, configurable

## Quick Start

### Using the Facade

```php
use Cline\Mint\Mint;

// Generate a time-ordered UUID v7 (recommended for databases)
$uuid = Mint::uuid()->v7()->generate();
echo $uuid->toString(); // "018c5d6e-5f89-7a9b-9c1d-2e3f4a5b6c7d"

// Generate a ULID
$ulid = Mint::ulid()->generate();
echo $ulid->toString(); // "01ARZ3NDEKTSV4RRFFQ69G5FAV"

// Generate a Snowflake ID with custom node
$snowflake = Mint::snowflake()->nodeId(1)->generate();
echo $snowflake->toString(); // "1234567890123456789"

// Generate a compact NanoID
$nanoid = Mint::nanoid()->generate();
echo $nanoid->toString(); // "V1StGXR8_Z5jdHi6B-myT"

// Generate a type-prefixed TypeID
$typeId = Mint::typeId()->prefix('user')->generate();
echo $typeId->toString(); // "user_01h455vb4pex5vsknk084sn02q"
```

### Parsing Existing Identifiers

```php
// Parse any identifier string
$uuid = Mint::uuid()->parse('550e8400-e29b-41d4-a716-446655440000');
$ulid = Mint::ulid()->parse('01ARZ3NDEKTSV4RRFFQ69G5FAV');
$snowflake = Mint::snowflake()->parse('1234567890123456789');

// Extract timestamps from time-based identifiers
$timestamp = $uuid->getTimestamp();    // Unix milliseconds (v1, v6, v7)
$timestamp = $ulid->getTimestamp();    // Unix milliseconds
$timestamp = $snowflake->getTimestamp(); // Unix milliseconds
```

### Validation

```php
// Validate identifier format
if (Mint::uuid()->isValid($input)) {
    $uuid = Mint::uuid()->parse($input);
}

if (Mint::ulid()->isValid($input)) {
    $ulid = Mint::ulid()->parse($input);
}
```

### Encoding/Decoding Numbers

```php
// Sqids - encode integers to short, URL-safe strings
$sqid = Mint::sqid()->encode([1, 2, 3]);
echo $sqid->toString(); // "86Rf07"
$numbers = Mint::sqid()->decode('86Rf07'); // [1, 2, 3]

// Hashids - encode with salt for app-specific output
$hashid = Mint::hashid()->salt('my-secret')->encode([42]);
echo $hashid->toString(); // "Y8r7W"
$numbers = Mint::hashid()->salt('my-secret')->decode('Y8r7W'); // [42]
```

## Fluent Configuration

Each identifier type supports fluent configuration for customization:

```php
// UUID with version selection
Mint::uuid()->v4()->generate();  // Random UUID
Mint::uuid()->v7()->generate();  // Time-ordered UUID (default)

// Snowflake with node ID and custom epoch
Mint::snowflake()
    ->nodeId(1)
    ->epoch(1609459200000) // Custom epoch (Jan 1, 2021)
    ->generate();

// NanoID with custom length and alphabet
Mint::nanoid()
    ->length(10)
    ->alphabet('0123456789abcdef')
    ->generate();

// Sqid with minimum length and blocklist
Mint::sqid()
    ->minLength(8)
    ->blocklist(['profanity'])
    ->encode([42]);
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=mint-config
```

Configure defaults in `config/mint.php`:

```php
return [
    'snowflake' => [
        'epoch' => 1288834974657, // Twitter epoch (default)
    ],
    'nanoid' => [
        'alphabet' => '_-0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
    ],
    'sqid' => [
        'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
        'min_length' => 0,
        'blocklist' => [],
    ],
    'hashid' => [
        'salt' => env('HASHIDS_SALT', ''),
        'min_length' => 0,
        'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
    ],
];
```

## Choosing the Right Identifier

### For Database Primary Keys

**UUID v7** or **ULID** - Both are time-ordered for optimal B-tree index performance:

```php
// Best for PostgreSQL, MySQL, etc.
$uuid = Mint::uuid()->v7()->generate();
$ulid = Mint::ulid()->generate();
```

### For Distributed Systems

**Snowflake** - Node-aware to prevent collisions without coordination:

```php
// Each node gets unique IDs without central coordination
$snowflake = Mint::snowflake()->nodeId(getNodeId())->generate();
```

### For URL Slugs

**NanoID** or **Sqid** - Compact and URL-safe:

```php
// Short, random ID for URLs
$nanoid = Mint::nanoid()->length(10)->generate(); // "V1StGXR8_Z"

// Encode database ID for public URLs
$sqid = Mint::sqid()->encodeNumber($post->id); // "86Rf07"
```

### For Type-Safe APIs

**TypeID** - Self-documenting with type prefixes:

```php
// IDs that indicate their entity type
$userId = Mint::typeId()->prefix('user')->generate();   // user_01h455vb...
$orderId = Mint::typeId()->prefix('order')->generate(); // order_01h455vc...
```

### For Obfuscating IDs

**Hashid** - Reversible encoding with salt:

```php
// Prevent enumeration while keeping DB integers
$public = Mint::hashid()->salt(config('app.key'))->encodeNumber($user->id);
$internal = Mint::hashid()->salt(config('app.key'))->decode($public)[0];
```

## Next Steps

- **[UUID](uuid)** - Time-based and random UUIDs with version selection
- **[ULID](ulid)** - Lexicographically sortable identifiers
- **[Snowflake](snowflake)** - Twitter-style distributed IDs
- **[NanoID](nanoid)** - Compact random identifiers
- **[Sqid](sqid)** - Integer encoding with Sqids
- **[Hashid](hashid)** - Integer encoding with Hashids
- **[TypeID](typeid)** - Type-prefixed sortable identifiers
- **[KSUID](ksuid)** - K-Sortable unique identifiers
- **[Other Identifiers](other-identifiers)** - CUID2, XID, ObjectID, PushID, Timeflake

<a id="doc-docs-hashid"></a>

## Overview

Hashids is the original library for encoding integers into short, unique strings. Unlike Sqids (its successor), Hashids uses a salt to make outputs unique to your application - the same number with different salts produces different outputs.

## Hashids vs Sqids

| Feature | Hashid | Sqid |
|---------|--------|------|
| Salt support | Yes | No |
| Blocklist | No | Yes |
| Hex encoding | Yes | No |
| Status | Stable | Active development |

**Choose Hashids when:**
- You need salt-based uniqueness per application
- You need hex string encoding
- Working with existing Hashids implementations

**Choose Sqids when:**
- Starting new projects (preferred successor)
- Need built-in blocklist support

## Encoding Numbers

### Basic Encoding

```php
use Cline\Mint\Mint;

// Encode a single number
$hashid = Mint::hashid()->encodeNumber(42);
echo $hashid->toString(); // "Y8r7W"

// Encode multiple numbers
$hashid = Mint::hashid()->encode([1, 2, 3]);
echo $hashid->toString(); // "laHquq"
```

### With Salt

Salt makes output unique to your application:

```php
// Your application's salt
$hashid = Mint::hashid()->salt('my-secret-salt')->encodeNumber(42);
echo $hashid->toString(); // "X9dWp"

// Different salt = different output
$hashid = Mint::hashid()->salt('different-salt')->encodeNumber(42);
echo $hashid->toString(); // "Kj3Rm"
```

**Important:** Use the same salt for encoding and decoding.

### Generate Unique Hashid

```php
// Generate based on timestamp + counter
$hashid = Mint::hashid()->generate();
echo $hashid->toString();
```

## Decoding

```php
// Decode back to numbers
$numbers = Mint::hashid()->salt('my-secret-salt')->decode('X9dWp');
// [42]

$numbers = Mint::hashid()->salt('my-secret-salt')->decode('laHquq');
// [1, 2, 3]

// Wrong salt returns empty array
$numbers = Mint::hashid()->salt('wrong-salt')->decode('X9dWp');
// []
```

## Hex Encoding

Encode hexadecimal strings (useful for UUIDs):

```php
// Encode hex string
$hashid = Mint::hashid()
    ->salt('my-salt')
    ->encodeHex('deadbeef');
echo $hashid->toString(); // "kRNrpKlJ"

// Decode back to hex
$hex = Mint::hashid()
    ->salt('my-salt')
    ->decodeHex('kRNrpKlJ');
// "deadbeef"

// Encode a UUID (without hyphens)
$uuid = '550e8400e29b41d4a716446655440000';
$hashid = Mint::hashid()->salt('my-salt')->encodeHex($uuid);
```

## Configuration Options

### Salt

Make outputs unique to your application:

```php
$hashid = Mint::hashid()
    ->salt(config('app.key'))
    ->encodeNumber(42);
```

### Minimum Length

Pad short Hashids:

```php
// Without minimum length
$hashid = Mint::hashid()->encodeNumber(1);
echo $hashid->toString(); // "jR"

// With minimum length
$hashid = Mint::hashid()->minLength(10)->encodeNumber(1);
echo $hashid->toString(); // "VolejRejNm"
```

### Custom Alphabet

At least 16 unique characters required:

```php
// Uppercase only
$hashid = Mint::hashid()
    ->alphabet('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890')
    ->encode([42]);

// No vowels (avoid accidental words)
$hashid = Mint::hashid()
    ->alphabet('bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ1234567890')
    ->encode([42]);
```

### Combined Configuration

```php
$hashid = Mint::hashid()
    ->salt('my-secret')
    ->minLength(10)
    ->alphabet('ABCDEFGHJKLMNPQRSTUVWXYZ23456789')
    ->encodeNumber(42);
```

## Configuration File

Configure defaults in `config/mint.php`:

```php
return [
    'hashid' => [
        'salt' => env('HASHIDS_SALT', ''),
        'min_length' => 0,
        'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
    ],
];
```

Set `HASHIDS_SALT` in your `.env`:

```env
HASHIDS_SALT="your-unique-application-salt"
```

## Validation & Parsing

```php
// Validate format
if (Mint::hashid()->isValid($input)) {
    $numbers = Mint::hashid()->salt('my-salt')->decode($input);
}

// Parse into object
$hashid = Mint::hashid()->parse('X9dWp');
echo $hashid->toString(); // "X9dWp"
```

## Use Cases

### Hide Database IDs

```php
class User extends Model
{
    private static function getHasher()
    {
        return Mint::hashid()->salt(config('app.key'));
    }

    public function getPublicIdAttribute(): string
    {
        return self::getHasher()->encodeNumber($this->id)->toString();
    }

    public static function findByPublicId(string $publicId): ?self
    {
        $decoded = self::getHasher()->decode($publicId);
        return $decoded ? self::find($decoded[0]) : null;
    }
}
```

### API Resources

```php
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => Mint::hashid()
                ->salt(config('hashids.users'))
                ->encodeNumber($this->id)
                ->toString(),
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
```

### Encode UUIDs

```php
class Document extends Model
{
    // Store UUID, expose short ID
    public function getShortIdAttribute(): string
    {
        $hex = str_replace('-', '', $this->uuid);
        return Mint::hashid()
            ->salt(config('app.key'))
            ->minLength(12)
            ->encodeHex($hex)
            ->toString();
    }

    public static function findByShortId(string $shortId): ?self
    {
        $hex = Mint::hashid()
            ->salt(config('app.key'))
            ->minLength(12)
            ->decodeHex($shortId);

        if (!$hex) {
            return null;
        }

        $uuid = sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20)
        );

        return self::where('uuid', $uuid)->first();
    }
}
```

### Multi-Tenant IDs

```php
// Different salt per tenant
class TenantHashids
{
    public function encode(Tenant $tenant, int $id): string
    {
        return Mint::hashid()
            ->salt($tenant->hashid_salt)
            ->encodeNumber($id)
            ->toString();
    }

    public function decode(Tenant $tenant, string $hashid): ?int
    {
        $decoded = Mint::hashid()
            ->salt($tenant->hashid_salt)
            ->decode($hashid);

        return $decoded[0] ?? null;
    }
}
```

## Important Notes

### Not Encryption

Hashids are **obfuscation**, not encryption:

```php
// With known salt, anyone can decode
$numbers = Mint::hashid()->salt('my-salt')->decode('X9dWp');
```

For sensitive data, use proper encryption.

### Configuration Must Match

Encoding and decoding require identical configuration:

```php
// Encode
$hashid = Mint::hashid()
    ->salt('secret')
    ->minLength(10)
    ->encodeNumber(42)
    ->toString();

// Decode - must use same config
$numbers = Mint::hashid()
    ->salt('secret')
    ->minLength(10)
    ->decode($hashid);
// [42]

// Different config fails
$numbers = Mint::hashid()
    ->salt('different')
    ->decode($hashid);
// []
```

### Non-Negative Integers Only

```php
Mint::hashid()->encode([0, 1, 2]);   // Works
Mint::hashid()->encode([-1]);        // Error
```

## API Reference

### HashidConductor Methods

| Method | Description |
|--------|-------------|
| `salt(string $salt)` | Set salt for unique output |
| `minLength(int $length)` | Set minimum output length |
| `alphabet(string $alphabet)` | Set custom charset (min 16 chars) |
| `generate()` | Generate unique Hashid |
| `encode(array $numbers)` | Encode array of integers |
| `encodeNumber(int $number)` | Encode single integer |
| `encodeHex(string $hex)` | Encode hexadecimal string |
| `decode(string $value)` | Decode to array of integers |
| `decodeHex(string $value)` | Decode to hexadecimal string |
| `parse(string $value)` | Parse into Hashid object |
| `isValid(string $value)` | Validate format |

### Hashid Object Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `toString()` | `string` | String representation |
| `getTimestamp()` | `null` | Always null |
| `isSortable()` | `bool` | Always `false` |

<a id="doc-docs-ksuid"></a>

## Overview

KSUIDs (K-Sortable Unique Identifiers) are 160-bit identifiers designed by Segment. They combine a 32-bit timestamp with 128 bits of random data, providing both sortability and high collision resistance.

## Structure

| Component | Bits | Description |
|-----------|------|-------------|
| Timestamp | 32 | Seconds since KSUID epoch |
| Payload | 128 | Cryptographically random data |

**Format:** 27-character Base62 string

```
0ujtsYcgvSTl8PAuAdqWYSMnLOv
└─────────────────────────────┘
     27 Base62 characters
```

## Why KSUID?

| Feature | KSUID | UUID v7 | ULID |
|---------|-------|---------|------|
| Size | 160 bits | 128 bits | 128 bits |
| Length | 27 chars | 36 chars | 26 chars |
| Timestamp precision | Seconds | Milliseconds | Milliseconds |
| Random bits | 128 | ~62 | 80 |
| Sortable | Yes | Yes | Yes |

**Choose KSUID when:**
- Maximum collision resistance is needed
- Second-level timestamp precision is sufficient
- You prefer Base62 encoding (alphanumeric only)

## Generating KSUIDs

```php
use Cline\Mint\Mint;

$ksuid = Mint::ksuid()->generate();
echo $ksuid->toString(); // "0ujtsYcgvSTl8PAuAdqWYSMnLOv"
```

Each KSUID contains:
- Current Unix timestamp (seconds)
- 128 bits of cryptographically secure random data

## Parsing KSUIDs

```php
$ksuid = Mint::ksuid()->parse('0ujtsYcgvSTl8PAuAdqWYSMnLOv');

// Get timestamp (Unix seconds)
$timestamp = $ksuid->getTimestamp();
$created = Carbon::createFromTimestamp($timestamp / 1000);

// Access string representation
echo $ksuid->toString();

// Access binary representation
$bytes = $ksuid->getBytes();

// Check sortability
$ksuid->isSortable(); // true
```

## Validation

```php
if (Mint::ksuid()->isValid($input)) {
    $ksuid = Mint::ksuid()->parse($input);
}

// Validates:
// - 27 character length
// - Valid Base62 characters (0-9, A-Z, a-z)
```

## Sorting

KSUIDs sort lexicographically by timestamp:

```php
$ids = [
    '0ujtsYcgvSTl8PAuAdqWYSMnLOv',
    '0ujsswThIGTUYm2K8FjOOfXtY1K',
    '0ujzPyRiIAffKhBux4PvQdDqMHY',
];

sort($ids);
// Sorted by creation time (second precision)
```

## KSUID Epoch

KSUIDs use a custom epoch: **May 13, 2014 (1400000000)**

This provides:
- ~136 years of timestamp space from the epoch
- IDs starting with lower characters (more aesthetically pleasing)

## Database Usage

### As Primary Key

```php
// Migration
Schema::create('events', function (Blueprint $table) {
    $table->string('id', 27)->primary();
    $table->string('type');
    $table->json('data');
    $table->timestamps();
});

// Model
class Event extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = Mint::ksuid()->generate()->toString();
        });
    }
}
```

### Binary Storage (20 bytes)

```php
// Migration
Schema::create('events', function (Blueprint $table) {
    $table->binary('id', 20)->primary();
    // ...
});

// Store binary
$ksuid = Mint::ksuid()->generate();
$event->id = $ksuid->getBytes();
```

## Use Cases

### Event Sourcing

```php
class DomainEvent extends Model
{
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            // KSUID provides natural time ordering
            $model->id = Mint::ksuid()->generate()->toString();
        });
    }
}

// Events are automatically sorted by time
$events = DomainEvent::orderBy('id')->get();
```

### Distributed Logging

```php
class LogEntry
{
    public function __construct(
        public string $id,
        public string $message,
        public array $context,
    ) {}

    public static function create(string $message, array $context = []): self
    {
        return new self(
            id: Mint::ksuid()->generate()->toString(),
            message: $message,
            context: $context,
        );
    }
}

// Log entries from multiple services sort correctly
```

### Message Queues

```php
class QueueMessage
{
    public string $id;
    public string $payload;

    public function __construct(string $payload)
    {
        $this->id = Mint::ksuid()->generate()->toString();
        $this->payload = $payload;
    }
}

// Messages can be ordered by KSUID
```

## Comparison with Similar IDs

### KSUID vs ULID

| Aspect | KSUID | ULID |
|--------|-------|------|
| Timestamp precision | Seconds | Milliseconds |
| Random bits | 128 | 80 |
| Length | 27 chars | 26 chars |
| Collision resistance | Higher | Good |

### KSUID vs UUID v7

| Aspect | KSUID | UUID v7 |
|--------|-------|---------|
| Standard | Segment | RFC 9562 |
| Size | 160 bits | 128 bits |
| Timestamp | Seconds | Milliseconds |
| Random bits | 128 | ~62 |

## Best Practices

### When to Use KSUID

```php
// Good for: High-write distributed systems
$ksuid = Mint::ksuid()->generate();

// Good for: Event IDs (second precision is fine)
$eventId = Mint::ksuid()->generate();

// Consider alternatives for: Sub-second ordering requirements
// (Use ULID or UUID v7 instead)
```

### Type Safety

```php
use Cline\Mint\Support\Identifiers\Ksuid;

class EventStore
{
    public function append(Ksuid $eventId, array $data): void
    {
        Event::create([
            'id' => $eventId->toString(),
            'data' => $data,
        ]);
    }

    public function since(Ksuid $afterId): Collection
    {
        return Event::where('id', '>', $afterId->toString())
            ->orderBy('id')
            ->get();
    }
}
```

### Timestamp Extraction

```php
$ksuid = Mint::ksuid()->generate();

// Get creation time
$timestamp = $ksuid->getTimestamp();
$created = Carbon::createFromTimestampMs($timestamp);

// Note: Second precision only
echo $created->format('Y-m-d H:i:s');
```

## API Reference

### KsuidConductor Methods

| Method | Description |
|--------|-------------|
| `generate()` | Generate a new KSUID |
| `parse(string $value)` | Parse a KSUID string |
| `isValid(string $value)` | Validate KSUID format |

### Ksuid Object Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `toString()` | `string` | 27-character Base62 string |
| `getBytes()` | `string` | 20-byte binary representation |
| `getTimestamp()` | `int` | Unix timestamp in milliseconds |
| `isSortable()` | `bool` | Always `true` |

<a id="doc-docs-nanoid"></a>

## Overview

NanoID is a tiny, secure, URL-friendly unique string ID generator. It uses cryptographically strong random values and is comparable to UUID in terms of collision probability but produces shorter, more URL-friendly strings.

## Why NanoID?

| Feature | NanoID | UUID v4 |
|---------|--------|---------|
| Default length | 21 chars | 36 chars |
| Alphabet | URL-safe | Hex + hyphens |
| Entropy | ~126 bits | 122 bits |
| Collision at 1000 IDs/hour | ~1 billion years | Similar |
| URL-safe | Yes | Requires encoding |

## Generating NanoIDs

### Default Generation

```php
use Cline\Mint\Mint;

// Generate with default settings (21 chars, URL-safe alphabet)
$nanoid = Mint::nanoid()->generate();
echo $nanoid->toString(); // "V1StGXR8_Z5jdHi6B-myT"
```

### Custom Length

```php
// Shorter IDs (less collision resistance)
$nanoid = Mint::nanoid()->length(10)->generate();
echo $nanoid->toString(); // "IRFa-VaY2b"

// Longer IDs (more collision resistance)
$nanoid = Mint::nanoid()->length(32)->generate();
echo $nanoid->toString(); // "V1StGXR8_Z5jdHi6B-myTV1StGXR8_Z5"
```

### Custom Alphabet

```php
// Numeric only
$nanoid = Mint::nanoid()
    ->alphabet('0123456789')
    ->generate();
echo $nanoid->toString(); // "583491728304756192038"

// Lowercase alphanumeric
$nanoid = Mint::nanoid()
    ->alphabet('0123456789abcdefghijklmnopqrstuvwxyz')
    ->generate();
echo $nanoid->toString(); // "a3b5c7d9e1f2g4h6i8j0k"

// Hexadecimal
$nanoid = Mint::nanoid()
    ->alphabet('0123456789abcdef')
    ->generate();
echo $nanoid->toString(); // "a3b5c7d9e1f2g4h6i8j0a"

// No ambiguous characters (0O, 1lI)
$nanoid = Mint::nanoid()
    ->alphabet('23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz')
    ->generate();
```

### Combined Customization

```php
$nanoid = Mint::nanoid()
    ->length(12)
    ->alphabet('ABCDEFGHIJKLMNOPQRSTUVWXYZ')
    ->generate();
echo $nanoid->toString(); // "XKCDMPQWERTY"
```

## Parsing NanoIDs

```php
$nanoid = Mint::nanoid()->parse('V1StGXR8_Z5jdHi6B-myT');

// Access string representation
echo $nanoid->toString();

// Check if sortable
$nanoid->isSortable(); // false - NanoIDs are random

// Get timestamp
$nanoid->getTimestamp(); // null - NanoIDs don't contain timestamps
```

## Validation

```php
if (Mint::nanoid()->isValid($input)) {
    $nanoid = Mint::nanoid()->parse($input);
}
```

Note: Validation checks for valid characters from the default alphabet. Custom alphabet validation may require additional checks.

## Configuration

Configure defaults in `config/mint.php`:

```php
return [
    'nanoid' => [
        'alphabet' => '_-0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        // Default length is 21 (hardcoded in generator)
    ],
];
```

## Collision Probability

The collision probability depends on:
- **Alphabet size**: More characters = more entropy per character
- **ID length**: Longer = exponentially more combinations

| Length | Alphabet Size | Total Combinations | Collision Probability |
|--------|---------------|-------------------|----------------------|
| 21 | 64 (default) | 2^126 | ~1B years at 1000/hour |
| 10 | 64 | 2^60 | ~17 years at 1000/hour |
| 21 | 16 (hex) | 2^84 | ~4M years at 1000/hour |
| 10 | 10 (numeric) | 10^10 | ~1 day at 1000/hour |

## Use Cases

### URL Slugs

```php
// Short, URL-safe identifiers
$slug = Mint::nanoid()->length(10)->generate();
$url = "https://example.com/posts/{$slug}";
// https://example.com/posts/V1StGXR8_Z
```

### Session Tokens

```php
// Longer for security-sensitive contexts
$token = Mint::nanoid()->length(32)->generate();
```

### File Names

```php
$filename = Mint::nanoid()->length(16)->generate();
$path = "uploads/{$filename}.jpg";
```

### Reference Codes

```php
// Human-readable, unambiguous
$code = Mint::nanoid()
    ->length(8)
    ->alphabet('ABCDEFGHJKLMNPQRSTUVWXYZ23456789')
    ->generate();
echo "Your code: {$code}"; // "K7M2N9P4"
```

## Database Usage

### As Primary Key

```php
// Migration
Schema::create('short_urls', function (Blueprint $table) {
    $table->string('id', 21)->primary();
    $table->string('target_url');
    $table->timestamps();
});

// Model
class ShortUrl extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = Mint::nanoid()->generate()->toString();
        });
    }
}
```

### As Slug Field

```php
// Migration
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('slug', 10)->unique();
    $table->string('title');
    $table->timestamps();
});

// Model
class Post extends Model
{
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->slug = Mint::nanoid()->length(10)->generate()->toString();
        });
    }
}
```

## Best Practices

### Choose Appropriate Length

```php
// Too short - collision risk
$nanoid = Mint::nanoid()->length(5)->generate(); // Bad for production

// Good balance for URLs
$nanoid = Mint::nanoid()->length(10)->generate(); // 60 bits

// Good for primary keys
$nanoid = Mint::nanoid()->length(21)->generate(); // 126 bits (default)

// Extra security (tokens, secrets)
$nanoid = Mint::nanoid()->length(32)->generate(); // 192 bits
```

### User-Facing IDs

```php
// Remove ambiguous characters
$alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
$nanoid = Mint::nanoid()->alphabet($alphabet)->generate();

// Uppercase only for verbal communication
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$nanoid = Mint::nanoid()->alphabet($alphabet)->length(8)->generate();
```

### Type Safety

```php
use Cline\Mint\Support\Identifiers\NanoId;

class UrlShortener
{
    public function shorten(string $url): NanoId
    {
        $id = Mint::nanoid()->length(10)->generate();
        ShortUrl::create(['id' => $id->toString(), 'url' => $url]);
        return $id;
    }

    public function resolve(NanoId $id): string
    {
        return ShortUrl::findOrFail($id->toString())->url;
    }
}
```

## API Reference

### NanoIdConductor Methods

| Method | Description |
|--------|-------------|
| `length(int $length)` | Set ID length (default: 21) |
| `alphabet(string $alphabet)` | Set custom character set |
| `generate()` | Generate a new NanoID |
| `parse(string $value)` | Parse a NanoID string |
| `isValid(string $value)` | Validate format |

### NanoId Object Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `toString()` | `string` | String representation |
| `getTimestamp()` | `null` | Always null (random ID) |
| `isSortable()` | `bool` | Always `false` |

<a id="doc-docs-other-identifiers"></a>

## Overview

Mint supports several additional identifier types for specialized use cases. Each provides unique characteristics suited to different scenarios.

## CUID2

**Collision-Resistant Unique Identifier** - The successor to CUID, designed for security and horizontal scalability.

### Features

| Property | Value |
|----------|-------|
| Length | 24 characters (default) |
| Sortable | No (secure randomness) |
| Characters | Lowercase alphanumeric |
| Collision resistant | Yes |

### Usage

```php
use Cline\Mint\Mint;

// Generate with default length (24)
$cuid2 = Mint::cuid2()->generate();
echo $cuid2->toString(); // "ckn4l3dw10000vl1d8b0n8b1c"

// Custom length (10-32)
$cuid2 = Mint::cuid2()->length(32)->generate();
```

### When to Use

- Security-sensitive identifiers
- Systems requiring unpredictable IDs
- Horizontal scaling scenarios

---

## XID

**Globally Unique Sortable ID** - A 96-bit identifier inspired by MongoDB ObjectID but with a simpler structure.

### Features

| Property | Value |
|----------|-------|
| Length | 20 characters |
| Size | 96 bits (12 bytes) |
| Sortable | Yes |
| Encoding | Base32 (Crockford) |

### Structure

| Component | Bits | Description |
|-----------|------|-------------|
| Timestamp | 32 | Unix seconds |
| Machine ID | 24 | Machine identifier |
| Process ID | 16 | Process identifier |
| Counter | 24 | Incrementing counter |

### Usage

```php
// Generate
$xid = Mint::xid()->generate();
echo $xid->toString(); // "9m4e2mr0ui3e8a215n4g"

// Parse
$xid = Mint::xid()->parse('9m4e2mr0ui3e8a215n4g');
$timestamp = $xid->getTimestamp();

// Validate
Mint::xid()->isValid($input);
```

### When to Use

- Cross-platform distributed systems
- When you need smaller IDs than UUID (12 vs 16 bytes)
- Go-compatible systems (XID is popular in Go)

---

## ObjectID

**MongoDB-Style Identifier** - 96-bit identifiers used natively by MongoDB.

### Features

| Property | Value |
|----------|-------|
| Length | 24 characters (hex) |
| Size | 96 bits (12 bytes) |
| Sortable | Yes |
| Encoding | Hexadecimal |

### Structure

| Component | Bytes | Description |
|-----------|-------|-------------|
| Timestamp | 4 | Unix seconds |
| Machine | 3 | Machine identifier |
| Process | 2 | Process ID |
| Counter | 3 | Incrementing counter |

### Usage

```php
// Generate
$objectId = Mint::objectId()->generate();
echo $objectId->toString(); // "507f1f77bcf86cd799439011"

// Parse
$objectId = Mint::objectId()->parse('507f1f77bcf86cd799439011');
$timestamp = $objectId->getTimestamp();

// Validate
Mint::objectId()->isValid($input);
```

### When to Use

- MongoDB integrations
- Systems that need MongoDB-compatible IDs
- Migrating from/to MongoDB

---

## PushID

**Firebase-Style Identifier** - Chronologically sortable identifiers designed for Firebase Realtime Database.

### Features

| Property | Value |
|----------|-------|
| Length | 20 characters |
| Sortable | Yes (chronological) |
| Characters | URL-safe Base64 |

### Structure

| Component | Characters | Description |
|-----------|------------|-------------|
| Timestamp | 8 | Milliseconds |
| Randomness | 12 | Random entropy |

### Usage

```php
// Generate
$pushId = Mint::pushId()->generate();
echo $pushId->toString(); // "-L7v3WZPJz_H5QfA5jDE"

// Parse
$pushId = Mint::pushId()->parse('-L7v3WZPJz_H5QfA5jDE');
$timestamp = $pushId->getTimestamp();

// Validate
Mint::pushId()->isValid($input);
```

### When to Use

- Firebase Realtime Database integration
- Real-time applications needing chronological ordering
- Systems requiring millisecond-precision timestamps

---

## Timeflake

**128-bit Time-Sortable Identifier** - Combines Unix timestamp with random data for sortable, collision-resistant IDs.

### Features

| Property | Value |
|----------|-------|
| Length | 26 characters |
| Size | 128 bits |
| Sortable | Yes |
| Encoding | Base62 |

### Structure

| Component | Bits | Description |
|-----------|------|-------------|
| Timestamp | 48 | Milliseconds since epoch |
| Random | 80 | Cryptographic randomness |

### Usage

```php
// Generate
$timeflake = Mint::timeflake()->generate();
echo $timeflake->toString(); // "01FH8W5A1Z2Y3X4W5V6U7T8S9R"

// Parse
$timeflake = Mint::timeflake()->parse('01FH8W5A1Z2Y3X4W5V6U7T8S9R');
$timestamp = $timeflake->getTimestamp();

// Validate
Mint::timeflake()->isValid($input);
```

### When to Use

- Database primary keys requiring chronological sorting
- Alternative to ULID with Base62 encoding
- Systems needing timestamp + randomness

---

## Comparison Table

| Type | Length | Sortable | Timestamp | Use Case |
|------|--------|----------|-----------|----------|
| CUID2 | 24 | No | No | Security-focused random IDs |
| XID | 20 | Yes | Seconds | Go-compatible, compact |
| ObjectID | 24 | Yes | Seconds | MongoDB compatibility |
| PushID | 20 | Yes | Milliseconds | Firebase, real-time apps |
| Timeflake | 26 | Yes | Milliseconds | General purpose, Base62 |

## Quick Reference

```php
use Cline\Mint\Mint;

// CUID2 - Secure random
$cuid2 = Mint::cuid2()->generate();                    // "ckn4l3dw10000..."
$cuid2 = Mint::cuid2()->length(32)->generate();        // Longer variant

// XID - Compact sortable
$xid = Mint::xid()->generate();                        // "9m4e2mr0ui3e8a215n4g"
$xid = Mint::xid()->parse($string);

// ObjectID - MongoDB style
$objectId = Mint::objectId()->generate();              // "507f1f77bcf86cd799439011"
$objectId = Mint::objectId()->parse($string);

// PushID - Firebase style
$pushId = Mint::pushId()->generate();                  // "-L7v3WZPJz_H5QfA5jDE"
$pushId = Mint::pushId()->parse($string);

// Timeflake - Time-sortable
$timeflake = Mint::timeflake()->generate();            // "01FH8W5A1Z2Y3X4W5V6U7T8S9R"
$timeflake = Mint::timeflake()->parse($string);
```

## API Reference

### Common Methods

All conductors support:

| Method | Description |
|--------|-------------|
| `generate()` | Generate a new identifier |
| `parse(string $value)` | Parse an identifier string |
| `isValid(string $value)` | Validate format |

### Common Object Methods

All identifier objects support:

| Method | Returns | Description |
|--------|---------|-------------|
| `toString()` | `string` | String representation |
| `getBytes()` | `string` | Binary representation |
| `getTimestamp()` | `?int` | Unix milliseconds (if sortable) |
| `isSortable()` | `bool` | Whether time-ordered |

### CUID2-Specific

| Method | Description |
|--------|-------------|
| `length(int $length)` | Set output length (10-32) |

<a id="doc-docs-snowflake"></a>

## Overview

Snowflake IDs are 64-bit, time-ordered identifiers originally designed by Twitter for distributed systems. They consist of a timestamp, node ID, and sequence number, allowing multiple machines to generate unique IDs without coordination.

## Structure

A Snowflake ID is composed of:

| Component | Bits | Description |
|-----------|------|-------------|
| Sign bit | 1 | Always 0 (positive number) |
| Timestamp | 41 | Milliseconds since epoch |
| Node ID | 10 | Machine/process identifier (0-1023) |
| Sequence | 12 | Per-millisecond counter (0-4095) |

**Capacity:**
- ~69 years of timestamps from epoch
- 1024 unique nodes
- 4096 IDs per millisecond per node
- **4.1 million IDs per second** per node

## Generating Snowflakes

### Basic Generation

```php
use Cline\Mint\Mint;

// Generate with default node ID (0)
$snowflake = Mint::snowflake()->generate();
echo $snowflake->toString(); // "1234567890123456789"
```

### With Node ID

In distributed systems, each node must have a unique ID:

```php
// Node 1
$snowflake = Mint::snowflake()->nodeId(1)->generate();

// Node 2
$snowflake = Mint::snowflake()->nodeId(2)->generate();
```

Node IDs range from 0 to 1023 (10 bits).

### With Custom Epoch

Set a custom epoch to maximize the usable timestamp range:

```php
// Custom epoch: January 1, 2024
$epoch = strtotime('2024-01-01 00:00:00') * 1000;

$snowflake = Mint::snowflake()
    ->nodeId(1)
    ->epoch($epoch)
    ->generate();
```

**Why custom epochs?**
- Default Twitter epoch: November 4, 2010
- Custom epoch closer to now = longer usable lifetime
- ~69 years from your chosen epoch

## Parsing Snowflakes

Extract components from existing Snowflake IDs:

```php
$snowflake = Mint::snowflake()->parse('1234567890123456789');

// Get timestamp (Unix milliseconds)
$timestamp = $snowflake->getTimestamp();
$created = Carbon::createFromTimestampMs($timestamp);

// Get node ID
$nodeId = $snowflake->getNodeId();

// Get sequence number
$sequence = $snowflake->getSequence();

// String representation
echo $snowflake->toString();
```

## Validation

```php
if (Mint::snowflake()->isValid($input)) {
    $snowflake = Mint::snowflake()->parse($input);
}

// Validates:
// - Numeric string
// - 64-bit unsigned integer range
```

## Configuration

Configure defaults in `config/mint.php`:

```php
return [
    'snowflake' => [
        'epoch' => 1288834974657, // Twitter epoch (default)
        // Or use a custom epoch:
        // 'epoch' => strtotime('2024-01-01 00:00:00') * 1000,
    ],
];
```

## Distributed Systems

### Node ID Management

In multi-server deployments, ensure unique node IDs:

```php
// Option 1: Environment variable
$nodeId = (int) env('SNOWFLAKE_NODE_ID', 0);
$snowflake = Mint::snowflake()->nodeId($nodeId)->generate();

// Option 2: Derive from server configuration
$nodeId = crc32(gethostname()) % 1024;
$snowflake = Mint::snowflake()->nodeId($nodeId)->generate();

// Option 3: Use container/pod ID
$nodeId = (int) env('POD_ID', 0) % 1024;
$snowflake = Mint::snowflake()->nodeId($nodeId)->generate();
```

### High Throughput

Snowflakes support 4096 IDs per millisecond per node:

```php
// Generate multiple IDs rapidly
$ids = [];
for ($i = 0; $i < 1000; $i++) {
    $ids[] = Mint::snowflake()->nodeId(1)->generate();
}

// All unique, all in order
```

If you exceed 4096/ms, the generator will wait until the next millisecond.

## Database Usage

### As Primary Key

```php
// Migration
Schema::create('events', function (Blueprint $table) {
    $table->unsignedBigInteger('id')->primary();
    $table->string('type');
    $table->json('payload');
    $table->timestamps();
});

// Model
class Event extends Model
{
    public $incrementing = false;
    protected $keyType = 'int';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = (int) Mint::snowflake()
                ->nodeId(config('app.node_id'))
                ->generate()
                ->toString();
        });
    }
}
```

### Indexing Benefits

Snowflakes are time-ordered, providing:
- Efficient range queries by time
- Reduced index fragmentation
- Natural chronological ordering

```php
// Get events from the last hour (approximately)
$oneHourAgo = now()->subHour();
$minId = /* calculate snowflake for timestamp */;

Event::where('id', '>=', $minId)->get();
```

## Comparison with Other IDs

| Feature | Snowflake | UUID v7 | ULID |
|---------|-----------|---------|------|
| Size | 64 bits | 128 bits | 128 bits |
| String length | 19 chars | 36 chars | 26 chars |
| Sortable | Yes | Yes | Yes |
| Distributed | Native | Yes | Yes |
| Node-aware | Yes | No | No |

**Choose Snowflake when:**
- 64-bit integer storage is preferred
- You need node-aware distributed generation
- High throughput is required (>1000/second)
- Database uses BIGINT efficiently

## Best Practices

### Consistent Node IDs

```php
// Service provider
class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Ensure consistent node ID across the application
        config(['mint.snowflake.node_id' => $this->resolveNodeId()]);
    }

    private function resolveNodeId(): int
    {
        return (int) env('SNOWFLAKE_NODE_ID', 0);
    }
}
```

### Handle Clock Drift

Snowflakes depend on monotonic time. The generator handles backward clock movement by waiting or throwing exceptions:

```php
try {
    $snowflake = Mint::snowflake()->generate();
} catch (ClockMovedBackwardsException $e) {
    // Clock moved backwards, handle appropriately
    Log::warning('Clock drift detected', ['error' => $e->getMessage()]);
}
```

### Type Safety

```php
use Cline\Mint\Support\Identifiers\Snowflake;

class EventService
{
    public function find(Snowflake $id): ?Event
    {
        return Event::find((int) $id->toString());
    }

    public function getNode(Snowflake $id): int
    {
        return $id->getNodeId();
    }
}
```

## API Reference

### SnowflakeConductor Methods

| Method | Description |
|--------|-------------|
| `nodeId(int $nodeId)` | Set node ID (0-1023) |
| `epoch(int $epoch)` | Set custom epoch (milliseconds) |
| `generate()` | Generate a new Snowflake ID |
| `parse(string $value)` | Parse a Snowflake string |
| `isValid(string $value)` | Validate format |

### Snowflake Object Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `toString()` | `string` | Numeric string representation |
| `getTimestamp()` | `int` | Unix timestamp in milliseconds |
| `getNodeId()` | `int` | Node/machine identifier |
| `getSequence()` | `int` | Sequence number within millisecond |
| `isSortable()` | `bool` | Always returns `true` |

<a id="doc-docs-sqid"></a>

## Overview

Sqids (pronounced "squids") is the successor to Hashids, providing a way to encode integers into short, URL-safe strings. Unlike random IDs, Sqids are deterministic and reversible - the same numbers always produce the same output, and you can decode them back to the original values.

## Why Sqids?

| Feature | Sqid | UUID | Auto-increment |
|---------|------|------|----------------|
| Reversible | Yes | No | N/A |
| Deterministic | Yes | No | N/A |
| Short | ~6 chars | 36 chars | Varies |
| Hides DB IDs | Yes | Yes | No |
| Sequential guessing | Protected | Protected | Vulnerable |

**Use cases:**
- Public-facing IDs that hide database auto-increment values
- URL shortening
- Share/invite codes
- Order/confirmation numbers

## Encoding Numbers

### Single Number

```php
use Cline\Mint\Mint;

// Encode a single number
$sqid = Mint::sqid()->encodeNumber(42);
echo $sqid->toString(); // "8QRLaD"

// Or use encode with array
$sqid = Mint::sqid()->encode([42]);
echo $sqid->toString(); // "8QRLaD"
```

### Multiple Numbers

```php
// Encode multiple numbers into one string
$sqid = Mint::sqid()->encode([1, 2, 3]);
echo $sqid->toString(); // "86Rf07"

// Useful for composite keys
$sqid = Mint::sqid()->encode([$userId, $postId]);
echo $sqid->toString(); // "xkJ7y2"
```

### Generate Unique Sqid

```php
// Generate based on timestamp + counter (for unique IDs)
$sqid = Mint::sqid()->generate();
echo $sqid->toString();
```

## Decoding

```php
// Decode back to numbers
$numbers = Mint::sqid()->decode('86Rf07');
// [1, 2, 3]

$numbers = Mint::sqid()->decode('8QRLaD');
// [42]

// Invalid Sqids return empty array
$numbers = Mint::sqid()->decode('invalid');
// []
```

## Configuration Options

### Minimum Length

Pad short Sqids to a minimum length:

```php
// Without minimum length
$sqid = Mint::sqid()->encodeNumber(1);
echo $sqid->toString(); // "Uk"

// With minimum length
$sqid = Mint::sqid()->minLength(10)->encodeNumber(1);
echo $sqid->toString(); // "kRbLa23Uk9"
```

### Custom Alphabet

Customize the characters used:

```php
// Lowercase only
$sqid = Mint::sqid()
    ->alphabet('abcdefghijklmnopqrstuvwxyz')
    ->encode([42]);
echo $sqid->toString(); // "xqyvzp"

// Numeric only
$sqid = Mint::sqid()
    ->alphabet('0123456789')
    ->encode([42]);
echo $sqid->toString(); // "924610"
```

### Blocklist

Prevent generation of specific words:

```php
$sqid = Mint::sqid()
    ->blocklist(['bad', 'word'])
    ->encode([123]);
// If output would contain "bad" or "word",
// alphabet is shuffled to produce different output
```

### Combined Configuration

```php
$sqid = Mint::sqid()
    ->alphabet('abcdefghijklmnopqrstuvwxyz0123456789')
    ->minLength(8)
    ->blocklist(['spam', 'test'])
    ->encode([42]);
```

## Configuration File

Configure defaults in `config/mint.php`:

```php
return [
    'sqid' => [
        'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
        'min_length' => 0,
        'blocklist' => [],
    ],
];
```

## Validation

```php
if (Mint::sqid()->isValid($input)) {
    $numbers = Mint::sqid()->decode($input);
}

// Validates characters are in the alphabet
```

## Parsing

```php
// Parse into Sqid object (without decoding)
$sqid = Mint::sqid()->parse('86Rf07');
echo $sqid->toString(); // "86Rf07"
```

## Use Cases

### Hide Database IDs

```php
// In Model
class Post extends Model
{
    public function getPublicIdAttribute(): string
    {
        return Mint::sqid()->encodeNumber($this->id)->toString();
    }

    public static function findByPublicId(string $publicId): ?self
    {
        $decoded = Mint::sqid()->decode($publicId);
        return $decoded ? self::find($decoded[0]) : null;
    }
}

// In Controller
Route::get('/posts/{publicId}', function (string $publicId) {
    $post = Post::findByPublicId($publicId);
    if (!$post) {
        abort(404);
    }
    return $post;
});
```

### URL Shortening

```php
class UrlShortener
{
    public function shorten(string $url): string
    {
        $link = ShortLink::create(['url' => $url]);
        return Mint::sqid()->minLength(6)->encodeNumber($link->id)->toString();
    }

    public function resolve(string $code): ?string
    {
        $decoded = Mint::sqid()->minLength(6)->decode($code);
        if (!$decoded) {
            return null;
        }
        return ShortLink::find($decoded[0])?->url;
    }
}
```

### Confirmation Numbers

```php
class Order extends Model
{
    public function getConfirmationNumber(): string
    {
        // Encode multiple values for composite uniqueness
        return Mint::sqid()
            ->minLength(10)
            ->alphabet('ABCDEFGHJKLMNPQRSTUVWXYZ23456789')
            ->encode([$this->id, $this->user_id])
            ->toString();
    }
}
```

### Share Codes

```php
class Document extends Model
{
    public function generateShareCode(): string
    {
        // Include document ID and expiry timestamp
        $expiryTimestamp = now()->addDays(7)->timestamp;

        return Mint::sqid()
            ->encode([$this->id, $expiryTimestamp])
            ->toString();
    }

    public static function findByShareCode(string $code): ?self
    {
        $decoded = Mint::sqid()->decode($code);
        if (count($decoded) !== 2) {
            return null;
        }

        [$id, $expiry] = $decoded;

        if ($expiry < now()->timestamp) {
            return null; // Expired
        }

        return self::find($id);
    }
}
```

## Important Notes

### Not Encryption

Sqids are **not encrypted** - they are obfuscated. Do not use them for security-sensitive data. Anyone with the same configuration can decode them.

```php
// These are equivalent - no security
Mint::sqid()->decode('86Rf07'); // Anyone can do this
```

### Configuration Must Match

Encoding and decoding must use the same configuration:

```php
// Encode with config A
$encoded = Mint::sqid()->minLength(10)->encode([42])->toString();

// Decode with config A - works
$numbers = Mint::sqid()->minLength(10)->decode($encoded); // [42]

// Decode with config B - fails
$numbers = Mint::sqid()->minLength(5)->decode($encoded); // []
```

### Non-Negative Integers Only

Sqids only work with non-negative integers:

```php
Mint::sqid()->encode([0, 1, 2]);   // Works
Mint::sqid()->encode([-1]);        // Error
Mint::sqid()->encode([1.5]);       // Error
```

## API Reference

### SqidConductor Methods

| Method | Description |
|--------|-------------|
| `alphabet(string $alphabet)` | Set custom character set (min 3 chars) |
| `minLength(int $length)` | Set minimum output length |
| `blocklist(array $words)` | Set words to avoid in output |
| `generate()` | Generate unique Sqid (timestamp-based) |
| `encode(array $numbers)` | Encode array of integers |
| `encodeNumber(int $number)` | Encode single integer |
| `decode(string $value)` | Decode to array of integers |
| `parse(string $value)` | Parse into Sqid object |
| `isValid(string $value)` | Validate format |

### Sqid Object Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `toString()` | `string` | String representation |
| `getTimestamp()` | `null` | Always null |
| `isSortable()` | `bool` | Always `false` |

<a id="doc-docs-typeid"></a>

## Overview

TypeIDs are type-safe, K-sortable identifiers that combine a type prefix with a UUIDv7 suffix. The prefix makes IDs self-documenting - you can tell what kind of entity an ID refers to just by looking at it.

## Format

```
user_01h455vb4pex5vsknk084sn02q
└──┘ └──────────────────────────┘
prefix      UUIDv7 (base32)
```

| Component | Description |
|-----------|-------------|
| **Prefix** | 0-63 lowercase letters (a-z) |
| **Separator** | Underscore `_` |
| **Suffix** | Base32-encoded UUIDv7 (26 chars) |

## Why TypeID?

| Feature | TypeID | UUID | ULID |
|---------|--------|------|------|
| Self-documenting | Yes | No | No |
| Sortable | Yes | v7 only | Yes |
| Type-safe | Yes | No | No |
| Format | `user_01h455...` | `550e8400-...` | `01ARZ3N...` |

**Benefits:**
- Instantly identify entity type from the ID
- Prevent accidental ID mixups (user vs order)
- K-sortable (chronological ordering)
- Based on battle-tested UUIDv7

## Generating TypeIDs

### With Prefix

```php
use Cline\Mint\Mint;

// Generate with type prefix
$userId = Mint::typeId()->prefix('user')->generate();
echo $userId->toString(); // "user_01h455vb4pex5vsknk084sn02q"

$orderId = Mint::typeId()->prefix('order')->generate();
echo $orderId->toString(); // "order_01h455vb4pex5vsknk084sn02q"

$postId = Mint::typeId()->prefix('post')->generate();
echo $postId->toString(); // "post_01h455vb4pex5vsknk084sn02q"
```

### Without Prefix

TypeIDs can be generated without a prefix (suffix only):

```php
$typeId = Mint::typeId()->generate();
echo $typeId->toString(); // "01h455vb4pex5vsknk084sn02q"
```

## Parsing TypeIDs

```php
$typeId = Mint::typeId()->parse('user_01h455vb4pex5vsknk084sn02q');

// Get the prefix (entity type)
$prefix = $typeId->getPrefix(); // "user"

// Get the suffix (UUIDv7)
$suffix = $typeId->getSuffix(); // "01h455vb4pex5vsknk084sn02q"

// Get as UUID string
$uuid = $typeId->getUuid(); // "018c5d6e-5f89-7a9b-9c1d-2e3f4a5b6c7d"

// Get timestamp (inherited from UUIDv7)
$timestamp = $typeId->getTimestamp(); // Unix milliseconds

// String representation
echo $typeId->toString();
```

## Validation

```php
if (Mint::typeId()->isValid($input)) {
    $typeId = Mint::typeId()->parse($input);
}

// Validates:
// - Prefix: 0-63 lowercase letters (a-z)
// - Separator: underscore (if prefix present)
// - Suffix: valid base32 UUIDv7
```

## Type Safety

TypeIDs enable compile-time and runtime type checking:

```php
use Cline\Mint\Support\Identifiers\TypeId;

class UserService
{
    public function find(TypeId $id): ?User
    {
        // Runtime validation
        if ($id->getPrefix() !== 'user') {
            throw new InvalidArgumentException(
                "Expected user TypeID, got {$id->getPrefix()}"
            );
        }

        return User::where('id', $id->toString())->first();
    }
}

class OrderService
{
    public function find(TypeId $id): ?Order
    {
        if ($id->getPrefix() !== 'order') {
            throw new InvalidArgumentException(
                "Expected order TypeID, got {$id->getPrefix()}"
            );
        }

        return Order::where('id', $id->toString())->first();
    }
}

// Usage prevents mixups
$userId = Mint::typeId()->prefix('user')->generate();
$orderId = Mint::typeId()->prefix('order')->generate();

$userService->find($userId);  // Works
$userService->find($orderId); // Throws exception!
```

## Database Usage

### As Primary Key

```php
// Migration
Schema::create('users', function (Blueprint $table) {
    $table->string('id', 36)->primary(); // user_ + 26 chars
    $table->string('name');
    $table->timestamps();
});

// Model
class User extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = Mint::typeId()->prefix('user')->generate()->toString();
        });
    }
}
```

### Prefix Constants

Define prefixes as constants for consistency:

```php
class TypeIdPrefixes
{
    public const USER = 'user';
    public const ORDER = 'order';
    public const POST = 'post';
    public const COMMENT = 'comment';
    public const INVOICE = 'invoice';
}

// Usage
$userId = Mint::typeId()->prefix(TypeIdPrefixes::USER)->generate();
```

### Trait for Models

```php
trait HasTypeId
{
    public static function bootHasTypeId(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Mint::typeId()
                    ->prefix($model->getTypeIdPrefix())
                    ->generate()
                    ->toString();
            }
        });
    }

    abstract public function getTypeIdPrefix(): string;

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}

// Models
class User extends Model
{
    use HasTypeId;

    public function getTypeIdPrefix(): string
    {
        return 'user';
    }
}

class Order extends Model
{
    use HasTypeId;

    public function getTypeIdPrefix(): string
    {
        return 'order';
    }
}
```

## Sorting

TypeIDs are K-sortable - they sort chronologically:

```php
$ids = [
    'user_01h455vb4pex5vsknk084sn02q',
    'user_01h455vb4pex5vsknk084sn02r',
    'user_01h455vb4pex5vsknk084sn02s',
];

sort($ids); // Already in chronological order

// Database ordering
$users = User::orderBy('id')->get(); // Chronological
```

## Use Cases

### API Responses

```php
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id, // "user_01h455vb..."
            'name' => $this->name,
            'created_at' => $this->created_at,
        ];
    }
}

// Response clearly shows entity type
{
    "id": "user_01h455vb4pex5vsknk084sn02q",
    "name": "Alice"
}
```

### Request Validation

```php
class UpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'regex:/^user_[a-z0-9]{26}$/'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
```

### Polymorphic Relations

```php
class Comment extends Model
{
    use HasTypeId;

    public function getTypeIdPrefix(): string
    {
        return 'comment';
    }

    // Commentable can be identified by prefix
    public function commentable()
    {
        return $this->morphTo();
    }
}

// The commentable_id clearly shows entity type
// comment.commentable_id = "post_01h455..." -> It's a Post
// comment.commentable_id = "video_01h455..." -> It's a Video
```

## Prefix Guidelines

| Entity | Prefix | Example |
|--------|--------|---------|
| User | `user` | `user_01h455...` |
| Order | `order` | `order_01h455...` |
| Product | `product` | `product_01h455...` |
| Invoice | `invoice` | `invoice_01h455...` |
| Session | `session` | `session_01h455...` |
| API Key | `apikey` | `apikey_01h455...` |

**Best practices:**
- Use singular nouns (`user` not `users`)
- Keep prefixes short but descriptive
- Use lowercase only (a-z)
- Be consistent across your application

## API Reference

### TypeIdConductor Methods

| Method | Description |
|--------|-------------|
| `prefix(string $prefix)` | Set type prefix (0-63 lowercase chars) |
| `generate()` | Generate a new TypeID |
| `parse(string $value)` | Parse a TypeID string |
| `isValid(string $value)` | Validate TypeID format |

### TypeId Object Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `toString()` | `string` | Full TypeID string |
| `getPrefix()` | `string` | Type prefix (e.g., "user") |
| `getSuffix()` | `string` | Base32 UUIDv7 suffix |
| `getUuid()` | `string` | Standard UUID format |
| `getTimestamp()` | `int` | Unix timestamp in milliseconds |
| `isSortable()` | `bool` | Always `true` |

<a id="doc-docs-ulid"></a>

## Overview

ULIDs (Universally Unique Lexicographically Sortable Identifiers) are 128-bit identifiers that combine the uniqueness of UUIDs with lexicographic sortability. They consist of a 48-bit timestamp and 80 bits of randomness, encoded as a 26-character case-insensitive string using Crockford's Base32 alphabet.

## Why ULID?

| Feature | ULID | UUID v4 |
|---------|------|---------|
| Length | 26 chars | 36 chars |
| Sortable | Yes | No |
| Case-sensitive | No | Yes |
| Timestamp | Extractable | No |
| Format | `01ARZ3NDEKTSV4RRFFQ69G5FAV` | `550e8400-e29b-41d4-a716-446655440000` |

**Benefits:**
- Lexicographically sortable (chronological ordering)
- Shorter than UUIDs (26 vs 36 characters)
- Case-insensitive (Crockford's Base32)
- No special characters (URL-safe)
- Millisecond precision timestamps

## Generating ULIDs

```php
use Cline\Mint\Mint;

// Generate a new ULID
$ulid = Mint::ulid()->generate();

echo $ulid->toString(); // "01ARZ3NDEKTSV4RRFFQ69G5FAV"
```

Each ULID contains:
- **Timestamp** (10 characters): Milliseconds since Unix epoch
- **Randomness** (16 characters): Cryptographically secure random data

## Parsing ULIDs

```php
$ulid = Mint::ulid()->parse('01ARZ3NDEKTSV4RRFFQ69G5FAV');

// Extract timestamp
$timestamp = $ulid->getTimestamp(); // Unix milliseconds
$created = Carbon::createFromTimestampMs($timestamp);

// Access string representation
echo $ulid->toString(); // "01ARZ3NDEKTSV4RRFFQ69G5FAV"

// Access binary representation
$bytes = $ulid->getBytes(); // 16 bytes

// Check sortability
$ulid->isSortable(); // true
```

## Validation

```php
// Check format validity
if (Mint::ulid()->isValid($input)) {
    $ulid = Mint::ulid()->parse($input);
}

// Validates:
// - 26 character length
// - Valid Crockford Base32 characters
// - Valid timestamp range
```

## Timestamp Extraction

ULIDs encode creation time with millisecond precision:

```php
$ulid = Mint::ulid()->generate();
$timestamp = $ulid->getTimestamp();

// Convert to Carbon
$created = Carbon::createFromTimestampMs($timestamp);
echo $created->toDateTimeString(); // "2024-01-15 10:30:00"

// Compare creation times
$older = Mint::ulid()->parse('01ARZ3NDEKTSV4RRFFQ69G5FAV');
$newer = Mint::ulid()->generate();

if ($newer->getTimestamp() > $older->getTimestamp()) {
    // newer was created after older
}
```

## Sorting

ULIDs sort lexicographically in chronological order:

```php
$ids = [
    '01HQ4X5JNAV8WBQX3YPCJ9V7DG',
    '01HQ4X5JNAV8WBQX3YPCJ9V7DF',
    '01HQ4X5JNAV8WBQX3YPCJ9V7DH',
];

sort($ids);
// Results in chronological order

// In database queries
$users = User::orderBy('id')->get(); // Chronological order if using ULID
```

## Database Usage

### As Primary Key

```php
// Migration
Schema::create('posts', function (Blueprint $table) {
    $table->string('id', 26)->primary();
    $table->string('title');
    $table->text('content');
    $table->timestamps();
});

// Model
class Post extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = Mint::ulid()->generate()->toString();
        });
    }
}
```

### Binary Storage (16 bytes)

For maximum space efficiency:

```php
// Migration
Schema::create('posts', function (Blueprint $table) {
    $table->binary('id', 16)->primary();
    // ...
});

// Store as binary
$ulid = Mint::ulid()->generate();
$post->id = $ulid->getBytes();

// Retrieve and parse
$ulid = Mint::ulid()->parse(
    strtoupper(bin2hex($post->id))
);
```

## Comparison with UUID v7

Both ULID and UUID v7 provide time-ordered uniqueness. Choose based on:

| Consideration | ULID | UUID v7 |
|---------------|------|---------|
| Length | 26 chars | 36 chars |
| Standard | De facto | RFC 9562 |
| Ecosystem | Growing | Universal |
| Case sensitivity | No | Yes |
| Hyphenated | No | Yes |

```php
// ULID - Compact, URL-friendly
$ulid = Mint::ulid()->generate();
// "01ARZ3NDEKTSV4RRFFQ69G5FAV"

// UUID v7 - RFC standard
$uuid = Mint::uuid()->v7()->generate();
// "018c5d6e-5f89-7a9b-9c1d-2e3f4a5b6c7d"
```

## Best Practices

### Validate Input

```php
public function show(string $id): Response
{
    if (!Mint::ulid()->isValid($id)) {
        abort(400, 'Invalid ULID format');
    }

    $ulid = Mint::ulid()->parse($id);
    $post = Post::findOrFail($ulid->toString());

    return response()->json($post);
}
```

### Use Type Hints

```php
use Cline\Mint\Support\Identifiers\Ulid;

class PostService
{
    public function find(Ulid $id): ?Post
    {
        return Post::find($id->toString());
    }

    public function getCreatedAt(Ulid $id): Carbon
    {
        return Carbon::createFromTimestampMs($id->getTimestamp());
    }
}
```

### Leverage Sortability

```php
// Range queries by time
$startOfDay = '01HQ4X0000000000000000000';
$endOfDay = '01HQ4XZZZZZZZZZZZZZZZZZZZZ';

$todaysPosts = Post::whereBetween('id', [$startOfDay, $endOfDay])->get();

// Or use timestamp extraction
$posts = Post::all()->sortBy(function ($post) {
    return Mint::ulid()->parse($post->id)->getTimestamp();
});
```

## API Reference

### UlidConductor Methods

| Method | Description |
|--------|-------------|
| `generate()` | Generate a new ULID |
| `parse(string $value)` | Parse a ULID string |
| `isValid(string $value)` | Validate ULID format |

### Ulid Object Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `toString()` | `string` | 26-character Crockford Base32 string |
| `getBytes()` | `string` | 16-byte binary representation |
| `getTimestamp()` | `int` | Unix timestamp in milliseconds |
| `isSortable()` | `bool` | Always returns `true` |

<a id="doc-docs-uuid"></a>

## Overview

UUIDs (Universally Unique Identifiers) are 128-bit identifiers standardized in RFC 4122 and RFC 9562. Mint supports versions 1, 4, and 7, with version 7 recommended for most use cases due to its time-ordered sortability and database performance benefits.

## UUID Versions

| Version | Description | Sortable | Use Case |
|---------|-------------|----------|----------|
| **v1** | Timestamp + MAC address | Yes | Legacy systems, hardware traceability |
| **v4** | Purely random | No | Maximum entropy, privacy-focused |
| **v7** | Unix timestamp + random | Yes | **Recommended** - database performance |

## Generating UUIDs

### UUID v7 (Recommended)

UUID v7 uses Unix timestamps with random entropy, providing chronological sortability ideal for database primary keys:

```php
use Cline\Mint\Mint;

// Default is v7
$uuid = Mint::uuid()->generate();

// Or explicitly
$uuid = Mint::uuid()->v7()->generate();

echo $uuid->toString(); // "018c5d6e-5f89-7a9b-9c1d-2e3f4a5b6c7d"
```

**Benefits of v7:**
- Natural time-based ordering reduces B-tree index fragmentation
- Improved write performance compared to random UUIDs
- Timestamp extraction for audit trails
- RFC 9562 compliant

### UUID v4 (Random)

UUID v4 uses purely random data, offering maximum entropy and no information leakage:

```php
$uuid = Mint::uuid()->v4()->generate();
echo $uuid->toString(); // "550e8400-e29b-41d4-a716-446655440000"
```

**Use when:**
- Timestamp ordering is not needed
- Maximum entropy is required
- Privacy is paramount (no time correlation)

### UUID v1 (Time + MAC)

UUID v1 combines timestamp with MAC address, providing temporal ordering but exposing hardware information:

```php
$uuid = Mint::uuid()->v1()->generate();
```

**Use when:**
- Hardware traceability is needed
- Working with legacy systems requiring v1

## Parsing UUIDs

Parse existing UUID strings to extract version and timestamp information:

```php
$uuid = Mint::uuid()->parse('018c5d6e-5f89-7a9b-9c1d-2e3f4a5b6c7d');

// Get the version
$version = $uuid->getVersion(); // UuidVersion::V7

// Get timestamp (v1, v6, v7 only)
$timestamp = $uuid->getTimestamp(); // Unix milliseconds (int) or null

// Check sortability
if ($uuid->isSortable()) {
    // v1, v6, or v7
}

// Access string representation
echo $uuid->toString();

// Access binary representation
$bytes = $uuid->getBytes();
```

## Validation

Check if a string is a valid UUID format:

```php
if (Mint::uuid()->isValid($input)) {
    $uuid = Mint::uuid()->parse($input);
}

// Validates:
// - 36 character length
// - Hyphen positions (8-4-4-4-12 format)
// - Hexadecimal characters only
```

## Special UUIDs

### Nil UUID

The nil UUID (all zeros) represents absence of a value:

```php
$nil = Mint::uuid()->nil();
echo $nil->toString(); // "00000000-0000-0000-0000-000000000000"
```

Use for:
- Placeholder values in databases
- Explicit "no UUID" representation

### Max UUID

The max UUID (all ones) represents the maximum value:

```php
$max = Mint::uuid()->max();
echo $max->toString(); // "ffffffff-ffff-ffff-ffff-ffffffffffff"
```

Use for:
- Sentinel values
- Range query boundaries

## Timestamp Extraction

Time-based UUIDs (v1, v6, v7) contain embedded timestamps:

```php
// UUID v7 - Unix timestamp in milliseconds
$uuid = Mint::uuid()->v7()->generate();
$timestamp = $uuid->getTimestamp();
$created = Carbon::createFromTimestampMs($timestamp);

// UUID v1 - Gregorian timestamp (converted to Unix ms)
$uuid = Mint::uuid()->v1()->generate();
$timestamp = $uuid->getTimestamp();

// UUID v4 - No timestamp
$uuid = Mint::uuid()->v4()->generate();
$timestamp = $uuid->getTimestamp(); // null
```

## Database Usage

### As Primary Key

```php
// Migration
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->timestamps();
});

// Model
class User extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = Mint::uuid()->v7()->generate()->toString();
        });
    }
}
```

### Binary Storage (16 bytes)

For space efficiency, store UUIDs in binary format:

```php
// Migration
Schema::create('users', function (Blueprint $table) {
    $table->binary('id', 16)->primary();
    // ...
});

// Store binary
$uuid = Mint::uuid()->v7()->generate();
$user->id = $uuid->getBytes();

// Parse from binary
$uuid = Mint::uuid()->parse(bin2hex($user->id));
```

## Best Practices

### Use v7 for New Projects

```php
// Good - v7 provides optimal database performance
$uuid = Mint::uuid()->v7()->generate();

// Avoid - v4 causes index fragmentation
$uuid = Mint::uuid()->v4()->generate();
```

### Validate Before Parsing

```php
// Always validate untrusted input
if (!Mint::uuid()->isValid($request->input('uuid'))) {
    throw new InvalidArgumentException('Invalid UUID format');
}

$uuid = Mint::uuid()->parse($request->input('uuid'));
```

### Use Type Hints

```php
use Cline\Mint\Support\Identifiers\Uuid;

class UserService
{
    public function find(Uuid $id): ?User
    {
        return User::find($id->toString());
    }
}

// Usage
$uuid = Mint::uuid()->parse($request->id);
$user = $userService->find($uuid);
```

## API Reference

### UuidConductor Methods

| Method | Description |
|--------|-------------|
| `v1()` | Configure for UUID version 1 |
| `v4()` | Configure for UUID version 4 |
| `v7()` | Configure for UUID version 7 (default) |
| `generate()` | Generate a new UUID |
| `parse(string $value)` | Parse a UUID string |
| `isValid(string $value)` | Validate UUID format |
| `nil()` | Get the nil UUID |
| `max()` | Get the max UUID |

### Uuid Object Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `toString()` | `string` | Standard hyphenated format |
| `getBytes()` | `string` | 16-byte binary representation |
| `getVersion()` | `UuidVersion` | UUID version enum |
| `getTimestamp()` | `?int` | Unix milliseconds (v1/v6/v7) or null |
| `isSortable()` | `bool` | True for time-ordered versions |
