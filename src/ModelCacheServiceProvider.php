<?php

namespace Plank\ModelCache;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Plank\ModelCache\Commands\ModelCacheCommand;

class ModelCacheServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('model-cache')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_model-cache_table')
            ->hasCommand(ModelCacheCommand::class);
    }
}
