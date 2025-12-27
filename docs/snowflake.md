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
