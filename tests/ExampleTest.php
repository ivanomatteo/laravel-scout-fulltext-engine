<?php

use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Extractors\CompositeNameExtractor;
use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Query\QueryParserMysqlFullTextBool;

it('can test', function () {
    expect(true)->toBeTrue();
});


it('can parse fulltext query', function () {

    $p = new QueryParserMysqlFullTextBool();

    $p->addExtractor(new CompositeNameExtractor('_'), true, true);

    $search = 'foò\' ba@r baz l\' acquila l\' acquila l\'acqua jisji sjij jijiiji de rossi';

    /* 
    $tokenized = $p->tokenize($search);

    dump('tokenized', $tokenized->toArray());

    $filtered = $p->filterTokens($tokenized);

    dump('filtered', $filtered->toArray());

    $quantized = $p->addQuantizers($filtered);

    dump('quantized', $quantized->toArray());

    $extracted = $p->runExtractors($tokenized->implode(' '));

    dump('extracted', $extracted->toArray()); 
    */


    $parsed = $p->parseSearchText($search);

    //dump('parsed', $parsed);

    expect($parsed)->toContain('l_acquila');
});
