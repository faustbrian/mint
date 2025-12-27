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
