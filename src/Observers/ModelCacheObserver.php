<?php

namespace Plank\ModelCache\Observers;

use Illuminate\Database\Eloquent\Model;
use Plank\ModelCache\Contracts\ManagesCache;

class ModelCacheObserver
{
    public function saved(Model&ManagesCache $model)
    {
        $model->flushModelCache();
    }

    public function deleted(Model&ManagesCache $model)
    {
        $model->flushModelCache();
    }

    public function restored(Model&ManagesCache $model)
    {
        $model->flushModelCache();
    }
}
