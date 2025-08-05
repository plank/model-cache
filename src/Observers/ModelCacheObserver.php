<?php

namespace Plank\ModelCache\Observers;

use Illuminate\Database\Eloquent\Model;
use Plank\ModelCache\Contracts\Cachable;

class ModelCacheObserver
{
    public function saved(Model&Cachable $model)
    {
        $model->flushModelCache();
    }

    public function deleted(Model&Cachable $model)
    {
        $model->flushModelCache();
    }

    public function restored(Model&Cachable $model)
    {
        $model->flushModelCache();
    }
}
