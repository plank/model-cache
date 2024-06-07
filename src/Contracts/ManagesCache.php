<?php

namespace Plank\ModelCache\Contracts;

interface ManagesCache
{
    /**
     * Determine if caching is disabled for this model
     */
    public static function modelCacheDisabled(): bool;

    /**
     * Cache a value that will be invalidated whenever any model of this type changes, 
     * and take into account a user's permissions
     */
    public static function rememberWithPermissions(
        string $key,
        ?Permissable $user,
        callable $callable,
        array $tags = [],
        int $ttl = 3600
    ): mixed;

    /**
     * Cache something that will be invalidated whenever any model of this type changes
     */
    public static function remember(
        string $key,
        callable $callable,
        array $tags = [],
        int $ttl = 3600,
    ): mixed;

    /**
     * Cache something that will be invalidated whenever this particular model changes
     *
     * @return mixed
     */
    public function rememberOnSelf(
        string $key,
        callable $callable,
        array $tags = [],
        int $ttl = 3600
    );

    /**
     * Flush the all cached items tagged with the models cache key
     */
    public function flushModelCache();

    /**
     * Get the cache key for the model
     */
    public static function modelCacheTag(): string;

    /**
     * Get the cache key for the model
     */
    public function instanceCacheTag(): string;
}
