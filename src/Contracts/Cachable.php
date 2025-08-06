<?php

namespace Plank\ModelCache\Contracts;

use Closure;
use Plank\ModelCache\Enums\ExpireAfter;

interface Cachable extends Flushable
{
    /**
     * This method caches data which will be invalidated when any Model of this
     * type changes.
     *
     * @template TReturn
     * 
     * @param callable():TReturn|class-string $callable
     * @param callable():string|class-string|string $prefix
     * @return TReturn
     */
    public static function remember(
        callable|string $callable,
        array $tags = [],
        callable|string $prefix = '',
        ExpireAfter|int|null $ttl = null,
    ): mixed;

    /**
     * This method caches data which will be invalidated when this specific
     * Model instance changes. It is important to note that this cache will not
     * be invalidated when other instances of this Model change.
     *
     * @template TReturn
     * 
     * @param callable():TReturn|class-string $callable
     * @param callable():string|string|class-string $prefix
     * @return TReturn
     */
    public function rememberOnSelf(
        callable|string $callable,
        array $tags = [],
        callable|string $prefix = '',
        ExpireAfter|int|null $ttl = null,
    ): mixed;
}
