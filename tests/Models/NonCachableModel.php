<?php

namespace Plank\ModelCache\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class NonCachableModel extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'email'];
}