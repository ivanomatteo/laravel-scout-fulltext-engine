<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Query;

interface QueryParser
{
    public function parseSearchText(string $query): string;
}
