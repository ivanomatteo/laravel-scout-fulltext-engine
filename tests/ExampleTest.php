<?php

use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Extractors\CompositeNameExtractor;
use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Query\QueryParserMysqlFullTextBool;

it('can test', function () {
    expect(true)->toBeTrue();
});


it('can parse fulltext query', function () {
    $p = new QueryParserMysqlFullTextBool();
    $p->addExtractor(new CompositeNameExtractor('_'), true, true);

    $parsed = $p->parseSearchText('foò\' ba@r baz l\' acquila l\' acquila l\'acqua jisji sjij jijiiji de rossi');

    expect($parsed)->toContain('l_acquila');
});
