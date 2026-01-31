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
