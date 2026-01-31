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
