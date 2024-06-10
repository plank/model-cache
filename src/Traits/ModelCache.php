<?php

namespace Plank\ModelCache\Traits;

use Closure;
use Illuminate\Cache\TaggableStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Contracts\ManagesCache;
use Plank\ModelCache\Contracts\Permissable;
use Plank\ModelCache\Observers\ModelCacheObserver;

/**
 * @mixin Model
 * @mixin ManagesCache
 */
trait ModelCache
{
    public static function bootModelCache()
    {
        static::observe(ModelCacheObserver::class);
    }

    public static function modelCacheDisabled(): bool
    {
        return config()->get('model-cache.disabled', false);
    }

    public function shouldSkipFlushing(): bool
    {
        return false;
    }

    /**
     * Flush the model's entire cache
     */
    public function flushModelCache(): void
    {
        if ($this->shouldSkipFlushing()) {
            return;
        }

        if (! static::cacheSupportsTags()) {
            Cache::flush();

            return;
        }

        Cache::tags([static::modelCacheTag(), $this->instanceCacheTag()])->flush();
    }

    /**
     * Compute a set of default tags to be used when caching the model
     *
     * @return array
     */
    public static function defaultTags(): array
    {
        return [];
    }

    public function rememberOnSelfWithPermissions(
        string $key,
        ?Permissable $user,
        Closure $callable,
        array $tags = [],
        ?int $ttl = null,
    ): mixed {
        $permissionsKey = $user instanceof Permissable ? $user->permissionsKey() : 'guest';

        return $this->rememberOnSelf($key.'-'.$permissionsKey, $callable, $tags, $ttl);
    }

    public function rememberOnSelf(
        string $key,
        Closure $callable,
        array $tags = [],
        ?int $ttl = null
    ): mixed {
        $ttl = $ttl ?? config()->get('model-cache.ttl', 3600);

        if (static::modelCacheDisabled()) {
            return $callable();
        }

        if (! static::cacheSupportsTags()) {
            return Cache::remember(static::withCacheKeyPrefix($key), $ttl, $callable);
        }

        $tags = array_merge(
            static::defaultTags(),
            [$this->instanceCacheTag()],
            array_map([static::class, 'handleTag'], $tags),
        );

        return Cache::tags($tags)
            ->remember(static::withCacheKeyPrefix($key), $ttl, $callable);
    }

    public static function rememberWithPermissions(
        string $key,
        ?Permissable $user,
        Closure $callable,
        array $tags = [],
        ?int $ttl = null
    ): mixed {
        $permissionsKey = $user instanceof Permissable ? $user->permissionsKey() : 'guest';

        return static::remember($key.'-'.$permissionsKey, $callable, $tags, $ttl);
    }

    public static function remember(
        string $key,
        Closure $callable,
        array $tags = [],
        ?int $ttl = null
    ): mixed {
        $ttl = $ttl ?? config()->get('model-cache.ttl', 3600);

        if (static::modelCacheDisabled()) {
            return $callable();
        }

        if (! static::cacheSupportsTags()) {
            return Cache::remember(static::withCacheKeyPrefix($key), $ttl, $callable);
        }

        $tags = array_merge(
            static::defaultTags(),
            [static::modelCacheTag()],
            array_map([static::class, 'handleTag'], $tags),
        );

        return Cache::tags($tags)
            ->remember(static::withCacheKeyPrefix($key), $ttl, $callable);
    }

    public function rememberOnSelfWithPermissionsForever(
        string $key,
        ?Permissable $user,
        Closure $callable,
        array $tags = [],
    ): mixed {
        $permissionsKey = $user instanceof Permissable ? $user->permissionsKey() : 'guest';

        return $this->rememberOnSelfForever($key.'-'.$permissionsKey, $callable, $tags);
    }

    public function rememberOnSelfForever(
        string $key,
        Closure $callable,
        array $tags = [],
    ): mixed {
        if (static::modelCacheDisabled()) {
            return $callable();
        }

        if (! static::cacheSupportsTags()) {
            return Cache::rememberForever(static::withCacheKeyPrefix($key), $callable);
        }

        $tags = array_merge(
            static::defaultTags(),
            [$this->instanceCacheTag()],
            array_map([static::class, 'handleTag'], $tags),
        );

        return Cache::tags($tags)
            ->rememberForever(static::withCacheKeyPrefix($key), $callable);
    }

    public static function rememberWithPermissionsForever(
        string $key,
        ?Permissable $user,
        Closure $callable,
        array $tags = [],
    ): mixed {
        $permissionsKey = $user instanceof Permissable ? $user->permissionsKey() : 'guest';

        return static::rememberForever($key.'-'.$permissionsKey, $callable, $tags);
    }

    public static function rememberForever(
        string $key,
        Closure $callable,
        array $tags = [],
    ): mixed {
        if (static::modelCacheDisabled()) {
            return $callable();
        }

        if (! static::cacheSupportsTags()) {
            return Cache::rememberForever(static::withCacheKeyPrefix($key), $callable);
        }

        $tags = array_merge(
            static::defaultTags(),
            [static::modelCacheTag()],
            array_map([static::class, 'handleTag'], $tags),
        );

        return Cache::tags($tags)
            ->rememberForever(static::withCacheKeyPrefix($key), $callable);
    }

    protected static function handleTag(string $tag)
    {
        if (class_exists($tag)) {
            if (is_a($tag, ManagesCache::class, true)) {
                return $tag::modelCacheTag();
            }

            return str($tag)->snake();
        }

        return static::withCacheTagPrefix($tag);
    }

    /**
     * Get the cache key for the model
     */
    protected static function modelCacheTag(): string
    {
        $tag = str(static::class)
            ->replace('\\', '_')
            ->snake();

        return static::withCacheTagPrefix($tag);
    }

    protected static function withCacheTagPrefix(string $tag): string
    {
        $prefix = static::getCacheTagPrefix();

        if (str($tag)->startsWith(static::getCacheTagPrefix())) {
            return $tag;
        }

        return $prefix.$tag;
    }

    /**
     * Get the prefix for the cache keys
     */
    protected static function getCacheTagPrefix(): string
    {
        return '';
    }

    protected function instanceCacheTag(): string
    {
        return static::withCacheTagPrefix($this->getKey());
    }

    protected static function withCacheKeyPrefix(string $key)
    {
        $prefix = static::getCacheKeyPrefix();

        if (str($key)->startsWith($prefix)) {
            return $key;
        }

        return $prefix.$key;
    }

    protected static function getCacheKeyPrefix(): string
    {
        return str(class_basename(static::class))
            ->snake()
            ->append('_');
    }

    /**
     * Determine if the configured cache driver supports tagging.
     */
    protected static function cacheSupportsTags(): bool
    {
        if (! Cache::getStore() instanceof TaggableStore) {
            return false;
        }

        if (config()->get('cache.default') === 'redis') {
            return config()->get('database.redis.client') === 'phpredis';
        }

        return true;
    }
}
