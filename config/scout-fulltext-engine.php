<?php

use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Extractors\CompositeNameExtractor;
use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Query\QueryParserMysqlFullTextBool;

return [

    'scount_engine_name' => 'mysql',

    'fulltext_options' => [
        'mode' => 'boolean',
        'order_by_score' => true,
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
