<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Extractors;

interface FeatureExtractor
{
    public function extract($searchText): array;
}
