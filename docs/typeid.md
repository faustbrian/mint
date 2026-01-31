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
