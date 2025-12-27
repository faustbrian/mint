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
