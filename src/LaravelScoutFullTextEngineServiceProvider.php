<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine;

use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Extractors\CompositeNameExtractor;
use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Query\QueryParser;
use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Query\QueryParserMysqlFullTextBool;
use IvanoMatteo\LaravelScoutFullTextEngine\Scout\ScoutEngine;
use Laravel\Scout\EngineManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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

    public function bootingPackage()
    {
        $this->app->singleton(CompositeNameExtractor::class, function ($app) {
            return (new CompositeNameExtractor());
        });

        $this->app->singleton(QueryParserMysqlFullTextBool::class, function ($app) {
            $p = (new QueryParserMysqlFullTextBool())
                ->matchAll()
                ->startsWith();

            collect(Pkg::configGet('pre_processing.query.extractors'))
                ->each(fn ($extr) => $p->addExtractor($app->make(
                    $extr['class'],
                    [
                        'mustMatch' => $extr['must_match'] ?? false,
                        'startsWith' => $extr['starts_with'] ?? false,
                    ]
                )));

            return $p;
        });

        if (Pkg::configGet('pre_processing.query.parser')) {
            $this->app->singleton(QueryParser::class, function ($app) {
                return $app->make(Pkg::configGet('pre_processing.query.parser'));
            });
        }

        $this->app->singleton(FullTextIndexer::class);

        if (class_exists(EngineManager::class)) {
            $this->app->make(EngineManager::class)
                ->extend(Pkg::configGet('scout_engine_name'), function () {
                    return new ScoutEngine();
                });
        }
    }
}
