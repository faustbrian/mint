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
