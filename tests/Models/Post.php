<?php

namespace Plank\ModelCache\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Plank\ModelCache\Contracts\Cachable;
use Plank\ModelCache\Traits\IsCachable;

class Post extends Model implements Cachable
{
    use HasFactory, IsCachable;

    protected $fillable = ['title', 'content', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}