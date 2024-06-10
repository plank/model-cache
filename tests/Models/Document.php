<?php

namespace Plank\ModelCache\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Plank\ModelCache\Contracts\ManagesCache;
use Plank\ModelCache\Traits\ModelCache;

class Document extends Model implements ManagesCache
{
    use HasFactory;
    use ModelCache;

    protected $guarded = [];
}
