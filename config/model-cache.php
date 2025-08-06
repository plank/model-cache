<?php

use Plank\ModelCache\Enums\ExpireAfter;

return [
    /*
    |--------------------------------------------------------------------------
    | Model Cache Enabled
    |--------------------------------------------------------------------------
    | 
    | Determines if model caching is enabled. When disabled, all cache
    | operations will be bypassed and closures will execute normally.
    |
    */
    'enabled' => env('MODEL_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default TTL
    |--------------------------------------------------------------------------
    | 
    | The default time-to-live for cached entries when no TTL is specified.
    | Can be an ExpireAfter enum value or integer seconds.
    |
    */
    'ttl' => env('MODEL_CACHE_TTL', ExpireAfter::Forever),
];
