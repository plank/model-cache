<?php

namespace Plank\ModelCache;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ModelCacheServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('model-cache')
            ->hasConfigFile()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->startWith(function (InstallCommand $command) {
                    $command->info("🗄️ Laravel Model Cache Installer... \n");

                    if ($command->confirm('Would you like to publish the config file?')) {
                        $command->publishConfigFile();
                    }
                });

                $command->endWith(function (InstallCommand $command) {
                    $command->info('✅ Installation complete.');

                    $command->askToStarRepoOnGitHub('plank/model-cache');
                });
            });
    }
}
