<?php

namespace Plank\ModelCache\Contracts;

use Closure;

interface ManagesCache
{
    /**
     * Determine if caching is disabled for this model
     */
    public static function modelCacheDisabled(): bool;

    /**
     * Determine if the model should skipping flushing the cache
     */
    public function shouldSkipFlushing(): bool;

    /**
     * Flush the all cached items tagged with the models cache key
     */
    public function flushModelCache(): void;

    /**
     * Cache something that will be invalidated whenever this particular
     * model changes and take into account a user's permissions
     */
    public function rememberOnSelfWithPermissions(
        string $key,
        ?Permissable $user,
        Closure $callable,
        array $tags = [],
        ?int $ttl = null,
    ): mixed;

    /**
     * Cache a value that will be invalidated whenever any model of this type changes,
     * and take into account a user's permissions
     */
    public static function rememberWithPermissions(
        string $key,
        ?Permissable $user,
        Closure $callable,
        array $tags = [],
        ?int $ttl = null,
    ): mixed;

    /**
     * Cache a value forever that will be invalidated whenever any model of this
     * type changes, and take into account a user's permissions f0ff1d3 (Basic test passing and redis added to github actions)
     */
    public function rememberOnSelf(
        string $key,
        Closure $callable,
        array $tags = [],
        ?int $ttl = null,
    ): mixed;

    /**
     * Cache something that will be invalidated whenever any model of this type changes
     */
    public static function remember(
        string $key,
        Closure $callable,
        array $tags = [],
        ?int $ttl = null,
    ): mixed;

    /**
     * Cache something that will be invalidated whenever this particular
     * model changes and take into account a user's permissions
     */
    public function rememberOnSelfWithPermissionsForever(
        string $key,
        ?Permissable $user,
        Closure $callable,
        array $tags = []
    ): mixed;
    
    /**
     * Cache a value forever that will be invalidated whenever any model of this 
     * type changes, and take into account a user's permissions
     */
    public static function rememberWithPermissionsForever(
        string $key,
        ?Permissable $user,
        Closure $callable,
        array $tags = []
    ): mixed;

    /**
     * Cache something forever that will be invalidated whenever 
     * this particular model changes
     */
    public function rememberOnSelfForever(
        string $key,
        Closure $callable,
        array $tags = [],
    ): mixed;

    /**
     * Cache something forever that will be invalidated whenever
     * any model of this type changes
     */
    public static function rememberForever(
        string $key,
        Closure $callable,
        array $tags = [],
    ): mixed;
}
