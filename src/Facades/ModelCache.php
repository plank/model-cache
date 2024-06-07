<?php

namespace Plank\ModelCache\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Plank\ModelCache\ModelCache
 */
class ModelCache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Plank\ModelCache\ModelCache::class;
    }
}
