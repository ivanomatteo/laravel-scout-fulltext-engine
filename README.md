# A scout DB fulltext-based driver that store index data in related tables

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ivanomatteo/laravel-scout-fulltext-engine.svg?style=flat-square)](https://packagist.org/packages/ivanomatteo/laravel-scout-fulltext-engine)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/ivanomatteo/laravel-scout-fulltext-engine/run-tests?label=tests)](https://github.com/ivanomatteo/laravel-scout-fulltext-engine/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/ivanomatteo/laravel-scout-fulltext-engine/Check%20&%20fix%20styling?label=code%20style)](https://github.com/ivanomatteo/laravel-scout-fulltext-engine/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ivanomatteo/laravel-scout-fulltext-engine.svg?style=flat-square)](https://packagist.org/packages/ivanomatteo/laravel-scout-fulltext-engine)

This package provide a Laravel/Scout Engine based on database/fulltext only, but work in a different way compared to the default database Engine.

You don't need to add fulltext indexes to your tables: the data used for search will be stored in a table with a polimorphyc relation.

This provide several advantages:

- you don't need to alter current tables's schema
- it's easy to add metadata
- indexing process can be done in jobs, so it will not slow down inserts in the tables



## Installation

You can install the package via composer:

```bash
composer require ivanomatteo/laravel-scout-fulltext-engine
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-scout-fulltext-engine-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-scout-fulltext-engine-config"
```

This is the contents of the published config file:

```php

use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Extractors\CompositeNameExtractor;
use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Query\QueryParserMysqlFullTextBool;

return [
 'scount_engine_name' => 'scout-fulltext-engine',

    'fulltext_options' => [

        'mode' => 'boolean',

        //
        // join bind mode will try to modify selected columns
        // adding "model_table.*" if no column was selected
        // or adding "model_table." prefix to selected columns
        // in some cases like when using DB::raw() you must be aware that
        // the query will be a join, and avoid column names collisions
        //
        'bind_mode' => 'exists', // 'exists' | 'join'

        // by default fulltext search will return records
        // orderred by match score, but in case you want
        // record to be ordered by: match_score, name
        // is necessary to be explicit
        //
        // only supported with bind_mode = 'join'
        'order_by_score' => false,

        // this will add a field named 'fulltext_score' to the results.
        // it can be usefull for tuning fulltext searches
        //
        // only supported with bind_mode = 'join'
        'add_select_score' => false,
    ],

    'pre_processing' => [
        'query' => [
            'parser' => QueryParserMysqlFullTextBool::class,

            'extractors' => [
                [
                    'class' => CompositeNameExtractor::class,
                    'must_match' => false,
                    'starts_with' => true,
                ]
            ],
        ],
        'index_data' => [
            'extractors' => [
                CompositeNameExtractor::class,
            ],
        ],
    ],

];
```

## Storing indexed data in different tables

It's also possible use different tables to store indexed data, simply creating another table with the same structure of "full_text_entries", the model (should extend FullTextEntry), and adding this function to your models:

```php
public function getFullTextEntryModel()
{
    return FullTextEntry2::class;
}
```


## Usage

Simpli configure Laravel Scout to use this driver:
(in your .env file)

```
SCOUT_DRIVER=scout-fulltext-engine
```

refer to [laravel scout documentation](https://laravel.com/docs/scout) for standard usage.

## Direct Search Mode

This package also provide a "direct search" mode: 
you just need to add DirectSearch Trait to your Model.

```php

use Laravel\Scout\Searchable;
use IvanoMatteo\LaravelScoutFullTextEngine\Concerns\DirectSearch;

class RubricaDipAnagrafica extends Model
{
    use Searchable;
    use DirectSearch;

}

```

In this way you get:
- fullTextEntry() relation to indexed table
- directSearch() scope, that you can use intead of search()

Scout's search() function, returns an instance of Laravel\Scout\Builder that has limited functionalities.

directSearch() intead, will return an intance of Illuminate\Database\Eloquent\Builder that allow you to combine with all database query operators as usual.



## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ivano Matteo](https://github.com/ivanomatteo)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
