<?php

namespace Plank\ModelCache\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Plank\ModelCache\Contracts\Cachable;
use Plank\ModelCache\Traits\IsCachable;

class User extends Model implements Cachable
{
    use HasFactory, IsCachable;

    protected $fillable = ['name', 'email'];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}