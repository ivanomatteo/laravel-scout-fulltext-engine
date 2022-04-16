<?php

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

            // extractors will extrapolate metadata from the
            // query text
            'extractors' => [
                [
                    // composite name extractor will find words
                    // composed by 1 or 2 characters followed by
                    // a word longer than 3 characters, for example:
                    // from "Robert De Niro" --> "De_niro"
                    //
                    // This is useful to overcome fulltext default min-length
                    // settings that ignore words < 3 chars
                    // (but it will work only if used also in index data section)
                    'class' => CompositeNameExtractor::class,

                    'must_match' => false, // true -> will prepend "+", for boolean mode, but depends by the parser class

                    'starts_with' => true, // true -> will append "*", for boolean mode, but depends by the parser class
                ]
            ],
        ],
        'index_data' => [
            'extractors' => [
                //this will add extracted metadata to te index
                CompositeNameExtractor::class,
            ],
        ],
    ],


];
