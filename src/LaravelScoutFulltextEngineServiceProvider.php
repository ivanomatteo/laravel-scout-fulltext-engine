<?php

namespace IvanoMatteo\LaravelScoutFulltextEngine;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use IvanoMatteo\LaravelScoutFulltextEngine\Commands\LaravelScoutFulltextEngineCommand;

class LaravelScoutFulltextEngineServiceProvider extends PackageServiceProvider
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
