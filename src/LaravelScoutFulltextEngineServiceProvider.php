<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use IvanoMatteo\LaravelScoutFullTextEngine\Commands\LaravelScoutFullTextEngineCommand;

class LaravelScoutFullTextEngineServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-scout-fulltext-engine')
            ->hasConfigFile()
            ->hasMigration('create_full_text_entries_table');
    }
}
