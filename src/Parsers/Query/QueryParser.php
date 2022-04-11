<?php

namespace IvanoMatteo\LaravelFulltextIndexer\Parsers\Query;

interface QueryParser
{
    public function parseSearchText(string $query): string;
}
