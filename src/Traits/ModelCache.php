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
trait Cached
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

    public function rememberOnSelfWithPermissions(
        string $key,
        ?Permissable $user,
        Closure $callable,
        array $tags = [],
        int $ttl = 3600
    ) {
        $permissionsKey = $user instanceof Permissable ? $user->permissionsKey() : 'guest';

        return $this->rememberOnSelf($key.'-'.$permissionsKey, $callable, $tags, $ttl);
    }

    public function rememberOnSelf(
        string $key,
        Closure $callable,
        array $tags = [],
        int $ttl = 3600
    ) {
        return static::remember($key.'-'.$this->getKey(), $callable, [
            $this->instanceCacheTag(),
            ...$tags,
        ], $ttl);
    }

    public static function rememberWithPermissions(
        string $key,
        ?Permissable $user,
        Closure $callable,
        array $tags = [],
        int $ttl = 3600
    ): mixed {
        $permissionsKey = $user instanceof Permissable ? $user->permissionsKey() : 'guest';

        return static::remember($key.'-'.$permissionsKey, $callable, $tags, $ttl);
    }

    public static function remember(
        string $key,
        Closure $callable,
        array $tags = [],
        int $ttl = 3600
    ): mixed {
        if (static::modelCacheDisabled()) {
            return $callable();
        }

        if (! static::cacheSupportsTags()) {
            return Cache::remember(static::withCacheKeyPrefix($key), $ttl, $callable);
        }

        $tags = array_merge(
            static::defaultTags(),
            static::modelCacheTag(),
            array_map([static::class, 'handleTag'], $tags),
        );

        return Cache::tags($tags)
            ->remember(static::withCacheKeyPrefix($key), $ttl, $callable);
    }

    /**
     * Flush the model's entire cache
     */
    public static function flushModelCache(Model&ManagesCache $model)
    {
        if ($model->shouldSkipFlushing()) {
            return;
        }

        if (! static::cacheSupportsTags()) {
            Cache::flush();

            return;
        }

        Cache::tags([static::modelCacheTag(), $model->instanceCacheTag()])->flush();
    }

    public static function handleTag(string $tag)
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
    public static function modelCacheTag(): string
    {
        $tag = str(static::class)
            ->replace('\\', '_')
            ->snake();

        return static::withCacheTagPrefix($tag);
    }

    public static function withCacheTagPrefix(string $tag): string
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
    public static function getCacheTagPrefix(): string
    {
        return '';
    }

    public function instanceCacheTag(): string
    {
        return static::withCacheTagPrefix($this->getKey());
    }

    public static function withCacheKeyPrefix(string $key)
    {
        $prefix = static::getCacheKeyPrefix();

        if (str($key)->startsWith($prefix)) {
            return $key;
        }

        return $prefix.$key;
    }

    public static function getCacheKeyPrefix(): string
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
