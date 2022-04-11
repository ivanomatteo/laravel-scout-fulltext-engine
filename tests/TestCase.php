<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use IvanoMatteo\LaravelScoutFullTextEngine\LaravelScoutFullTextEngineServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'IvanoMatteo\\LaravelScoutFullTextEngine\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelScoutFullTextEngineServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-scout-fulltext-engine_table.php.stub';
        $migration->up();
        */
    }
}
