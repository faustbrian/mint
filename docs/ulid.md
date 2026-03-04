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
