<?php

namespace Plank\ModelCache\Commands;

use Illuminate\Console\Command;

class ModelCacheCommand extends Command
{
    public $signature = 'model-cache';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
