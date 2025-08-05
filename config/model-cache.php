<?php

use Plank\ModelCache\Enums\ExpireAfter;

return [
    'enabled' => env('MODEL_CACHE_ENABLED', true),
    'ttl' => env('MODEL_CACHE_TTL', ExpireAfter::Forever),
];
