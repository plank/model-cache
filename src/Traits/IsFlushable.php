<?php

namespace Plank\ModelCache\Traits;

use Illuminate\Cache\RedisStore;
use Illuminate\Cache\TaggableStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Contracts\Flushable;
use Plank\ModelCache\Observers\ModelCacheObserver;

/**
 * @mixin Model
 * @mixin Flushable
 */
trait IsFlushable
{
    public static function bootIsFlushable(): void
    {
        static::observe(ModelCacheObserver::class);
    }

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

    public function shouldSkipFlushing(): bool
    {
        return false;
    }

    public static function modelCacheTag(): string
    {
        $tag = str(static::class)
            ->replace('\\', '')
            ->snake();

        return ($prefix = static::modelCachePrefix())
            ? $prefix.':'.$tag
            : $tag;
    }

    public function instanceCacheTag(): string
    {
        return static::modelCacheTag().':'.$this->getKey();
    }

    public static function modelCachePrefix(): string
    {
        return '';
    }

    protected static function cacheSupportsTags(): bool
    {
        if (! ($store = Cache::getStore()) instanceof TaggableStore) {
            return false;
        }

        if ($store instanceof RedisStore) {
            return ! $store->connection() instanceof PredisConnection;
        }

        return true;
    }
}
