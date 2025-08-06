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
            ? ($prefix . ':' . $key)
            : $key;

        $key = ($prefix = static::modelCachePrefix())
            ? ($prefix . ':' . $key)
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
            ? ($prefix . ':' . $key)
            : $key;

        $ttl ??= ExpireAfter::default();

        $ttl = $ttl instanceof ExpireAfter
            ? $ttl->inSeconds()
            : $ttl;

        if (! static::cacheSupportsTags()) {
            return Cache::remember(static::modelCachePrefix() . ':' . $key, $ttl, $callable);
        }

        $tags = array_merge(
            static::defaultTags(),
            [$this->instanceCacheTag()],
            array_map([static::class, 'handleTag'], $tags),
        );

        return Cache::tags($tags)
            ->remember(static::modelCachePrefix() . ':' . $key, $ttl, $callable);
    }

    protected static function handleTag(string $tag)
    {
        if (class_exists($tag)) {
            if (is_a($tag, Cachable::class, true)) {
                return $tag::modelCacheTag();
            }

            $tag = str($tag)->snake();
        }

        return ($prefix = static::modelCachePrefix())
            ? ($prefix . ':' . $tag)
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

    protected static function closureKey(callable $closure): string
    {
        return match (true) {
            $closure instanceof Closure => static::handleClosureKey($closure),
            is_array($closure) => static::handleArrayCallableKey($closure),
            is_object($closure) => static::handleObjectCallableKey($closure),
            is_string($closure) => static::handleStringCallableKey($closure),
            default => throw new \Exception('Unknown Callable Type'),
        };
    }

    private static function handleClosureKey(Closure $closure): string
    {
        $reflection = new ReflectionFunction($closure);

        if ($scopeClass = $reflection->getClosureScopeClass()) {
            return $scopeClass->getName() . '::' . $reflection->getShortName() . ':' . $reflection->getStartLine();
        }

        return 'global::' . $reflection->getShortName() . ':' . $reflection->getStartLine();
    }

    private static function handleArrayCallableKey(array $callable): string
    {
        [$classOrObject, $method] = $callable;

        if (is_object($classOrObject)) {
            return get_class($classOrObject) . '::' . $method . '_instance';
        }

        return (string) ($classOrObject . '::' . $method . '_static');
    }

    private static function handleObjectCallableKey(object $callable): string
    {
        return get_class($callable) . '::__invoke';
    }

    private static function handleStringCallableKey(string $callable): string
    {
        return 'function::' . $callable;
    }
}
