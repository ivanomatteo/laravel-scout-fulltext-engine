<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Extractors;

class CompositeNameExtractor implements FeatureExtractor
{
    public function __construct(
        private string $glue = '_',
        private int $minPrefixLen = 1,
        private int $maxPrefixLen = 2,
        private int $minSuffixLen = 3,
    ) {}

    public function extract($searchText): array
    {
        $searchText = preg_replace('/[[:punct:]]/', ' ', $searchText);

        $regex = '/((^|\\s)[^[:punct:]\\s]{'.$this->minPrefixLen.','.$this->maxPrefixLen.
            '}\\s+[^[:punct:]\\s]{'.$this->minSuffixLen.',})/';

        if (! preg_match_all(
            $regex,
            $searchText,
            $matches
        )) {
            return [];
        }

        return collect($matches[0])->map(function ($v) {
            return trim(implode($this->glue, preg_split('/\\s+/', trim($v))));
        })->unique()->toArray();
    }
}
