<?php

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
