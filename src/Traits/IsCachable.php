<?php

namespace Plank\ModelCache\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Contracts\Cachable;
use Plank\ModelCache\Enums\ExpireAfter;
use ReflectionFunction;

/**
 * @mixin Model
 * @mixin Cachable
 */
trait IsCachable
{
    use IsFlushable;

    /**
     * @template TReturn
     * 
     * @param Closure():TReturn $callable
     * @param callable():string|string $prefix
     * @return TReturn
     */
    public static function remember(
        Closure $callable,
        array $tags = [],
        callable|string $prefix = '',
        ExpireAfter|int|null $ttl = null,
    ): mixed {
        if (static::modelCacheDisabled()) {
            return $callable();
        }

        $key = static::closureKey($callable);

        $prefix = is_string($prefix)
            ? $prefix
            : $prefix();

        $key = $prefix
            ? ($prefix.':'.$key)
            : $key;

        $key = ($prefix = static::modelCachePrefix())
            ? ($prefix.':'.$key)
            : $key;

        $ttl = $ttl instanceof ExpireAfter
            ? $ttl->inSeconds()
            : $ttl;

        if (! static::cacheSupportsTags()) {
            return Cache::remember($key, $ttl, $callable);
        }

        $tags = array_merge(
            static::defaultTags(),
            [static::modelCacheTag()],
            array_map([static::class, 'handleTag'], $tags),
        );

        return Cache::tags($tags)
            ->remember($key, $ttl, $callable);
    }

    /**
     * @template TReturn
     * 
     * @param Closure():TReturn $callable
     * @param callable():string|string $prefix
     * @return TReturn
     */
    public function rememberOnSelf(
        Closure $callable,
        array $tags = [],
        callable|string $prefix = '',
        ExpireAfter|int|null $ttl = null,
    ): mixed { 
        if (static::modelCacheDisabled()) {
            return $callable();
        }

        $key = static::closureKey($callable);

        $prefix = is_string($prefix)
            ? $prefix
            : $prefix();

        $key = $prefix
            ? ($prefix.':'.$key)
            : $key;

        $ttl ??= ExpireAfter::default();

        $ttl = $ttl instanceof ExpireAfter
            ? $ttl->inSeconds()
            : $ttl;

        if (! static::cacheSupportsTags()) {
            return Cache::remember(static::modelCachePrefix().':'.$key, $ttl, $callable);
        }

        $tags = array_merge(
            static::defaultTags(),
            [$this->instanceCacheTag()],
            array_map([static::class, 'handleTag'], $tags),
        );

        return Cache::tags($tags)
            ->remember(static::modelCachePrefix().':'.$key, $ttl, $callable);
    }

    protected static function handleTag(string $tag)
    {
        if (class_exists($tag)) {
            if (is_a($tag, Cachable::class, true)) {
                return $tag::modelCacheTag();
            }

            $tag = str($tag)->snake();
        }

        return static::withCacheTagPrefix($tag);
    }

    public static function defaultTags(): array
    {
        return [];
    }

    public static function modelCacheDisabled(): bool
    {
        return ! config()->get('model-cache.enabled', false);
    }

    protected static function closureKey(callable $closure) {
        $reflection = new ReflectionFunction($closure);
        $class = $reflection->getClosureScopeClass()->getName();

        return "{$class}{$reflection->getShortName()}:{$reflection->getStartLine()}";
    }
}
