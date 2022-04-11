<?php

namespace IvanoMatteo\LaravelFulltextIndexer\Parsers\Extractors;

interface FeatureExtractor
{
    public function extract($searchText): array;
}
