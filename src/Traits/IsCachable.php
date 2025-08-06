<?php

namespace Plank\ModelCache\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Contracts\Cachable;
use Plank\ModelCache\Contracts\Flushable;
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
     * @param  callable():TReturn|class-string  $callable
     * @param  callable():string|class-string|string  $prefix
     * @return TReturn
     */
    public static function remember(
        callable|string $callable,
        array $tags = [],
        callable|string $prefix = '',
        ExpireAfter|int|null $ttl = null,
    ): mixed {
        if (static::modelCacheDisabled()) {
            return $callable();
        }

        $key = static::cachableKey($callable);
        $closure = static::cachableClosure($callable);

        $prefix = match (true) {
            is_callable($prefix) => $prefix(),
            is_string($prefix) && class_exists($prefix) && is_callable(new $prefix) => (new $prefix)(),
            default => $prefix,
        };

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
            return Cache::remember($key, $ttl, $closure);
        }

        $tags = array_merge(
            static::defaultTags(),
            [static::modelCacheTag()],
            array_map([static::class, 'handleTag'], $tags),
        );

        return Cache::tags($tags)
            ->remember($key, $ttl, $closure);
    }

    /**
     * @template TReturn
     *
     * @param  callable():TReturn|class-string  $callable
     * @param  callable():string|string|class-string  $prefix
     * @return TReturn
     */
    public function rememberOnSelf(
        callable|string $callable,
        array $tags = [],
        callable|string $prefix = '',
        ExpireAfter|int|null $ttl = null,
    ): mixed {
        if (static::modelCacheDisabled()) {
            return $callable();
        }

        $key = static::cachableKey($callable);
        $closure = static::cachableClosure($callable);

        $prefix = match (true) {
            is_callable($prefix) => $prefix(),
            is_string($prefix) && class_exists($prefix) && is_callable(new $prefix) => (new $prefix)(),
            default => $prefix,
        };

        $key = $prefix
            ? ($prefix.':'.$key)
            : $key;

        $ttl ??= ExpireAfter::default();

        $ttl = $ttl instanceof ExpireAfter
            ? $ttl->inSeconds()
            : $ttl;

        if (! static::cacheSupportsTags()) {
            return Cache::remember(static::modelCachePrefix().':'.$key, $ttl, $closure);
        }

        $tags = array_merge(
            static::defaultTags(),
            [$this->instanceCacheTag()],
            array_map([static::class, 'handleTag'], $tags),
        );

        return Cache::tags($tags)
            ->remember(static::modelCachePrefix().':'.$key, $ttl, $closure);
    }

    protected static function handleTag(Flushable|string $tag)
    {
        if ($tag instanceof Flushable) {
            return $tag->instanceCacheTag();
        }

        if (class_exists($tag) && is_a($tag, Flushable::class, true)) {
            return $tag::modelCacheTag();
        }

        return ($prefix = static::modelCachePrefix())
            ? ($prefix.':'.$tag)
            : $tag;
    }

    public static function defaultTags(): array
    {
        return [];
    }

    public static function modelCacheDisabled(): bool
    {
        return ! config()->get('model-cache.enabled', false);
    }

    /**
     * @param Closure|callable|class-string
     */
    protected static function cachableClosure(callable|string $callable): Closure
    {
        if ($callable instanceof Closure) {
            return $callable;
        }

        $isInvokeableClass = is_string($callable)
            && class_exists($callable)
            && is_callable(new $callable);

        $callable = $isInvokeableClass
            ? new $callable
            : $callable;

        return function () use ($callable) {
            return $callable();
        };
    }

    protected static function cachableKey(callable|string $callable): string
    {
        return match (true) {
            $callable instanceof Closure => static::handleCachableKey($callable),
            is_string($callable) => static::handleInvokeableKey($callable),
            is_array($callable) => static::handleArrayCallableKey($callable),
            is_object($callable) => static::handleObjectCallableKey($callable),
            default => throw new \Exception('Unknown Callable Type'),
        };
    }

    private static function handleInvokableKey(string $class): string
    {
        return $class.'::__invoke';
    }

    private static function handleCachableKey(Closure $closure): string
    {
        $reflection = new ReflectionFunction($closure);

        if ($scopeClass = $reflection->getClosureScopeClass()) {
            return $scopeClass->getName().'::'.$reflection->getShortName().':'.$reflection->getStartLine();
        }

        return 'global::'.$reflection->getShortName().':'.$reflection->getStartLine();
    }

    private static function handleArrayCallableKey(array $callable): string
    {
        [$classOrObject, $method] = $callable;

        if (is_object($classOrObject)) {
            return get_class($classOrObject).'::'.$method.'_instance';
        }

        return (string) ($classOrObject.'::'.$method.'_static');
    }

    private static function handleObjectCallableKey(object $callable): string
    {
        return get_class($callable).'::__invoke';
    }
}
