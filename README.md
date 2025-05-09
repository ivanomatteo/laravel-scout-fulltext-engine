# A scout DB fulltext-based driver that store index data in related tables

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ivanomatteo/laravel-scout-fulltext-engine.svg?style=flat-square)](https://packagist.org/packages/ivanomatteo/laravel-scout-fulltext-engine)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ivanomatteo/laravel-scout-fulltext-engine/run-tests.yml?branch=main&label=tests)](https://github.com/ivanomatteo/laravel-scout-fulltext-engine/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ivanomatteo/laravel-scout-fulltext-engine/phpstan.yml?branch=main&label=phpstan)](https://github.com/ivanomatteo/laravel-scout-fulltext-engine/actions/workflows/phpstan.yml?query=branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ivanomatteo/laravel-scout-fulltext-engine/php-cs-fixer.yml?label=code%20style)](https://github.com/ivanomatteo/laravel-scout-fulltext-engine/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ivanomatteo/laravel-scout-fulltext-engine.svg?style=flat-square)](https://packagist.org/packages/ivanomatteo/laravel-scout-fulltext-engine)

This package provide a Laravel/Scout Engine based on database/fulltext only, but work in a different way compared to the default database Engine.

You don't need to add fulltext indexes to your tables: the data used for search will be stored in a table with a polymorphic relation.

This provide several advantages:

- you don't need to change current tables's schema
- it's easy to add metadata
- indexing process can be deferred in jobs, so it will not slow down database inserts/updates


## Installation

You can install the package via composer:

```bash
composer require ivanomatteo/laravel-scout-fulltext-engine
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="scout-fulltext-engine-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="scout-fulltext-engine-config"
```

This is the contents of the published config file:

```php

use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Extractors\CompositeNameExtractor;
use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Query\QueryParserMysqlFullTextBool;

return [


    'scout_engine_name' => 'scout-fulltext-engine',

    'fulltext_options' => [

        'mode' => 'boolean',

        /*
            Note on bind_mode == 'join':
                it will try to modify selected columns by
                adding "model_table.*" if no column was selected or
                adding "model_table." prefix to selected columns.
                In some cases, for example when using DB::raw(),
                you must be careful because the query will be a join,
                and you have to avoid column names collisions
        */
        'bind_mode' => 'exists', // 'exists' | 'join'

        /*
            By default fulltext searches will return records
            ordered by match score:
                but in some case you may want the records to be ordered by
                multiple fields, for example: match_score, name
                in these cases is necessary to be explicit
            !!! only supported with bind_mode = 'join'
        */
        'order_by_score' => false,

        // this will add a field named 'fulltext_score' to the results.
        // it can be useful for tuning fulltext searches
        // !!! only supported with bind_mode = 'join'
        'add_select_score' => false,
    ],


    'pre_processing' => [
        'query' => [
            // the parser will process the text passed to
            // search function preparing it for the specific
            // fulltext query type
            'parser' => QueryParserMysqlFullTextBool::class,

            // Extractors will extrapolate metadata from the query text
         
            'extractors' => [
                [
                    // useful to match dotted words
                    // must be used also in index_data section
                    // "N.A.S.A"  --extract--> [ "NASA", "N_A_S_A" ]
                    'class' => DottedWordsExtractor::class,
                    'must_match' => false, // true -> will prepend "+", for boolean mode, but depends by the parser class
                    'starts_with' => true, // true -> will append "*", for boolean mode, but depends by the parser class
                ],
                [
                    // composite name extractor will find words
                    // composed by 1 or 2 characters followed by
                    // a word longer than 3 characters, for example:
                    // from "Robert De Niro" --extract--> [ "De_niro" ]
                    // this is useful to overcome fulltext default words min-length (3 chars)
                    // (but it will work only if used also in index data section)
                    'class' => CompositeNameExtractor::class,
                    'must_match' => false,
                    'starts_with' => true,
                ]
            ],
        ],

        'index_data' => [
            'extractors' => [
                //this will add extracted metadata to te index
                DottedWordsExtractor::class,
                CompositeNameExtractor::class,
            ],
        ],
    ],


];
```

## Storing indexed data in different tables

It's also possible use different tables to store indexed data: 
- creating another table with the same structure of "full_text_entries"
- the model (that should extend FullTextEntry)

and adding this method to your models:

```php
public function getFullTextEntryModel()
{
    return FullTextEntry2::class;
}
```


## Usage

Simply configure Laravel Scout to use this driver:
(in your .env file)

```
SCOUT_DRIVER=scout-fulltext-engine
```

and refer to [laravel scout documentation](https://laravel.com/docs/scout) for standard usage.

## Direct Search Mode

This package also provide a "direct search" mode: 
you just need to add DirectSearch Trait to your Model:

```php

use Laravel\Scout\Searchable;
use IvanoMatteo\LaravelScoutFullTextEngine\Concerns\DirectSearch;

class MyModel extends Model
{
    use Searchable;
    use DirectSearch;

}

```

In this way you will get:
- **fullTextEntry()**: relation to indexed table
- **directSearch()**: scope, that you can use intead of search()

Scout's **search()** function, returns an instance of **Laravel\Scout\Builder** that has limited functionalities.

**directSearch()** instead, will return an instance of **Illuminate\Database\Eloquent\Builder** that allow you to build your query as usual.



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
