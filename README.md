<p align="center"><a href="https://plank.co"><img src="art/model-cache.png" width="100%"></a></p>

<p align="center">
<a href="https://packagist.org/packages/plank/model-cache"><img src="https://img.shields.io/packagist/php-v/plank/model-cache?color=%23fae370&label=php&logo=php&logoColor=%23fff" alt="PHP Version Support"></a>
<a href="https://laravel.com/docs/11.x/releases#support-policy"><img src="https://img.shields.io/badge/laravel-10.x,%2011.x-%2343d399?color=%23f1ede9&logo=laravel&logoColor=%23ffffff" alt="Laravel Version Support"></a>
<a href="https://github.com/plank/model-cache/actions?query=workflow%3Arun-tests"><img src="https://img.shields.io/github/actions/workflow/status/plank/model-cache/run-tests.yml?branch=main&&color=%23bfc9bd&label=run-tests&logo=github&logoColor=%23fff" alt="GitHub Workflow Status"></a>
<a href="https://codeclimate.com/github/plank/model-cache/test_coverage"><img src="https://img.shields.io/codeclimate/coverage/plank/model-cache?color=%23ff9376&label=test%20coverage&logo=code-climate&logoColor=%23fff" /></a>
<a href="https://codeclimate.com/github/plank/model-cache/maintainability"><img src="https://img.shields.io/codeclimate/maintainability/plank/model-cache?color=%23528cff&label=maintainablility&logo=code-climate&logoColor=%23fff" /></a>
</p>

# Laravel Model Cache

**A Laravel caching package that automatically invalidates when your models change.**

Laravel Model Cache provides an elegant, declarative way to cache expensive operations at the model level with intelligent auto-invalidation. Perfect for read-heavy applications where data doesn't change very frequently but performance is critical.

- **✨ Smart Caching**: Cache any expensive operation with automatic invalidation when models change
- **🏎️ High Performance**: Supports tags for cache invalidation
- **🧠 Intelligent Keys**: Automatic cache key generation from closures and callables
- **🔧 Zero Configuration**: Works out of the box with Laravel's cache system

---

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Core Concepts](#core-concepts)
- [TTL Management](#ttl-management)
- [Cache Invalidation](#cache-invalidation)
- [Performance Considerations](#performance-considerations)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)
- [Security](#security-vulnerabilities)

&nbsp;

## Installation

Install the package via Composer:

```bash
composer require plank/model-cache
```

2. Use the package's install command to complete the installation:

```bash
php artisan model-cache:install
```

&nbsp;

## Quick Start

Make your model cacheable by implementing the `Cachable` contract and using the `IsCachable` trait:

```php
<?php

use Illuminate\Database\Eloquent\Model;
use Plank\ModelCache\Contracts\Cachable;
use Plank\ModelCache\Traits\IsCachable;

class User extends Model implements Cachable
{
    use IsCachable;
    
    // ...
}
```

#### **Model-level cache**

The following example invalidates when:
1. Any `User` model is created, updated, or deleted
2. Any `Post` or `Comment` model is created, updated, or deleted, due to their tags being added.
    - This means that `Post` and `Comment` would need to implement `Flushable` or `Cachable`

```php
$result = User::remember(function () {
    $users = User::with(['posts', 'comments'])
        ->where('active', true)
        ->get();

    return $this->doSomethingExpensive($users);
}, [
    Post::class,
    Comment::class,
]);
```

#### **Instance-level cache**

The following example invalidates when:
1. The specific `User` instance is updated or deleted

```php
protected function getExpensiveAttribute(): mixed
{
    return $this->rememberOnSelf(function () {
        return ExpensiveApi::get(email: $this->email, name: $this->name);
    }, ttl: ExpireAfter::Forever);
}
```

&nbsp;

## Core Concepts

### Model-Level Caching (`remember()`)
Use `remember()` for data that should be invalidated when **any** model of that type changes:

```php
// This cache invalidates when ANY user changes
$data = User::remember(fn() => User::all()->pluck('email'));
```

### Instance-Level Caching (`rememberOnSelf()`)
Use `rememberOnSelf()` for data that should only invalidate when **that specific** model instance changes:

```php
$user = User::find(1);

// This cache only invalidates when THIS user changes
$data = $user->rememberOnSelf(fn() => $user->posts()->count());
```

### Using `Flushable` Tags

A "Flushable tag" is the class string of a `Model` which implements the `Flushable` interface. (`Cachable` extends `Flushable`).

By passing the class string of a Flushable tag to the `remember` and `rememberOnSelf` methods, those cache entries will also be invalidated when any model of that type changes. By passing an instance of a `Flushable` as a tag, the entry will be invalidated only when that specific instance changes.

```php
$user = User::find(1);

// This cache only invalidates when THIS user changes or any Post changes
$data = $user->rememberOnSelf(fn() => $user->posts()->count(), [Post::class]);
```

### Automatic Cache Key Generation
The package intelligently manages cache keys by generating them from the passed callable. Calling the remember methods with the same callable – regardless of where you are calling it from (with the same tags) – will return the same result.

```php

$cached = fn () => 'Expensive text';

User::remember($cached); // Cache miss
User::remember($cached); // Cache hit

class ExpensiveInvokeable
{
    public function __invoke()
    {
        return 'expensive';
    }
}

User::remember(ExpensiveInvokeable::class); // Cache miss
User::remember(ExpensiveInvokeable::class); // Cache hit
```

&nbsp;

### Model-Specific Configuration

Customize caching behavior per model:

```php
class User extends Model implements Cachable
{
    use IsCachable;
    
    // Custom cache prefix for this model
    public static function modelCachePrefix(): string
    {
        return 'user_v2'; // Useful for cache versioning
    }
    
    // Default tags applied to all cache operations
    public static function defaultTags(): array
    {
        return ['users', 'auth'];
    }
    
    // Skip cache invalidation under certain conditions
    public function shouldSkipFlushing(): bool
    {
        // Don't invalidate cache for minor updates
        return $this->wasChanged(['last_seen_at', 'login_count']);
    }
}
```

&nbsp;

### Using ExpireAfter Enum

The package provides a convenient enum for common TTL values:

```php
use Plank\ModelCache\Enums\ExpireAfter;

// Available values
ExpireAfter::Forever;           // null (never expires)
ExpireAfter::OneMinute;         // 60 seconds
ExpireAfter::FiveMinutes;       // 300 seconds
ExpireAfter::TenMinutes;        // 600 seconds
ExpireAfter::FifteenMinutes;    // 900 seconds
ExpireAfter::ThirtyMinutes;     // 1800 seconds
ExpireAfter::FortyFiveMinutes;  // 2700 seconds
ExpireAfter::OneHour;           // 3600 seconds
ExpireAfter::OneDay;            // 86400 seconds
ExpireAfter::OneWeek;           // 604800 seconds
ExpireAfter::OneMonth;          // 2592000 seconds
ExpireAfter::OneYear;           // 31536000 seconds

// Usage
User::remember(fn() => User::all(), ttl: ExpireAfter::OneHour);
User::remember(fn() => User::count(), ttl: ExpireAfter::FiveMinutes);
```

### Custom TTL Values

```php
// Using integer seconds
User::remember(fn() => User::all(), ttl: 3600); // 1 hour

// Using null for forever
User::remember(fn() => User::all(), ttl: null); // Never expires

// Default TTL from config
User::remember(fn() => User::all()); // Uses config('model-cache.ttl')
```

&nbsp;

## Cache Invalidation

### Automatic Invalidation

Cache entries are automatically invalidated when models change:

```php
// Cache some user data
$userData = User::remember(fn() => User::with('posts')->get());

// This will automatically invalidate the above cache
User::create(['name' => 'John', 'email' => 'john@example.com']);

// Next call will regenerate the cache
$freshData = User::remember(fn() => User::with('posts')->get()); // Cache miss, regenerated
```

### Model Events That Trigger Invalidation

- **created**: When a new model is created
- **updated**: When a model is updated  
- **deleted**: When a model is deleted
- **restored**: When a soft-deleted model is restored (if using SoftDeletes)

### Conditional Invalidation

Skip invalidation for specific scenarios:

```php
class User extends Model implements Cachable
{
    use IsCachable;
    
    public function shouldSkipFlushing(): bool
    {
        // Don't invalidate cache for timestamp-only updates
        if ($this->wasChanged(['updated_at']) && count($this->getChanges()) === 1) {
            return true;
        }
        
        // Don't invalidate for tracking fields
        if ($this->wasChanged(['last_seen_at', 'login_count'])) {
            return true;
        }
        
        return false;
    }
}
```

&nbsp;

## Performance Considerations

### Cache Store Compatibility

**Redis (Recommended)**
- Supports cache tags for invalidation
- Best performance for tagged cache operations
- Recommended for production use

**Memcached**
- Supports cache tags
- Good performance
- Alternative to Redis

**File/Database Cache**
- No tag support - uses `Cache::flush()` for invalidation
- **⚠️ Warning**: Invalidation flushes the entire cache
- Not recommended for production use

&nbsp;

## Testing

### Disable Caching in Tests

```php
// In your test setup
config(['model-cache.enabled' => false]);
```

&nbsp;

## Troubleshooting

### Stale data

If your caching the results of a query that depends on or involves other models, be sure to make those models `Cachable` or `Flushable` and tag the entry with those classes/instances.

&nbsp;

## Credits

- [Kurt Friars](https://github.com/kfriars) - Creator and maintainer
- [All Contributors](../../contributors) - Thank you! 

Inspired by the need for intelligent, automatic cache invalidation in Laravel applications.

&nbsp;

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

&nbsp;

## Security Vulnerabilities

If you discover a security vulnerability within the package, please send an e-mail to [security@plank.co](mailto:security@plank.co). All security vulnerabilities will be promptly addressed.

&nbsp;

## Check Us Out!

<a href="https://plank.co/open-source/learn-more-image">
    <img src="https://plank.co/open-source/banner">
</a>

&nbsp;

Plank focuses on impactful solutions that deliver engaging experiences to our clients and their users. We're committed to innovation, inclusivity, and sustainability in the digital space. [Learn more](https://plank.co/open-source/learn-more-link) about our mission to improve the web.