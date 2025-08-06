<?php

namespace Plank\ModelCache\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Plank\ModelCache\Contracts\Cachable;
use Plank\ModelCache\Traits\IsCachable;

class Tag extends Model implements Cachable
{
    use HasFactory, IsCachable;

    protected $fillable = ['name'];

    public static function modelCachePrefix(): string
    {
        return 'test_prefix';
    }
}