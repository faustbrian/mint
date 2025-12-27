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
