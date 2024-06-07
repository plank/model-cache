<?php

namespace Plank\ModelCache\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface Permissable extends Authenticatable
{
    public function permissionsKey(): string;
}
