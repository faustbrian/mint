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
