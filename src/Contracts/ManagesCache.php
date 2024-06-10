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
        ?int $ttl = null,
    ): mixed;

    /**
     * Cache a value forever that will be invalidated whenever any model of this 
     * type changes, and take into account a user's permissions
     */
    public static function rememberWithPermissionsForever(
        string $key,
        ?Permissable $user,
        callable $callable,
        array $tags = []
    ): mixed;

    /**
     * Cache something that will be invalidated whenever any model of this type changes
     */
    public static function remember(
        string $key,
        callable $callable,
        array $tags = [],
        ?int $ttl = null,
    ): mixed;

    /**
     * Cache something forever that will be invalidated whenever
     * any model of this type changes
     */
    public static function rememberForever(
        string $key,
        callable $callable,
        array $tags = [],
    ): mixed;

    /**
     * Cache something that will be invalidated whenever this particular
     * model changes and take into account a user's permissions
     *
     * @return mixed
     */
    public function rememberOnSelfWithPermissions(
        string $key,
        ?Permissable $user,
        callable $callable,
        array $tags = [],
        ?int $ttl = null,
    );

    /**
     * Cache something that will be invalidated whenever this particular
     * model changes and take into account a user's permissions
     *
     * @return mixed
     */
    public function rememberOnSelfWithPermissionsForever(
        string $key,
        ?Permissable $user,
        callable $callable,
        array $tags = []
    );

    /**
     * Cache something that will be invalidated whenever this
     * particular model changes
     *
     * @return mixed
     */
    public function rememberOnSelf(
        string $key,
        callable $callable,
        array $tags = [],
        ?int $ttl = null,
    );

    /**
     * Cache something forever that will be invalidated whenever 
     * this particular model changes
     *
     * @return mixed
     */
    public function rememberOnSelfForever(
        string $key,
        callable $callable,
        array $tags = [],
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
