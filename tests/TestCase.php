<?php

namespace Plank\ModelCache\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Plank\ModelCache\ModelCacheServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Plank\\ModelCache\\Tests\\Helper\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->artisan('migrate', [
            '--path' => __DIR__.'/Database/Migrations',
            '--realpath' => true,
        ])->run();
    }

    protected function getPackageProviders($app)
    {
        return [
            ModelCacheServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('model-cache.enabled', true);
    }
}
