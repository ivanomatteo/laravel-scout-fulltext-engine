<?php

namespace IvanoMatteo\LaravelFulltextIndexer\Concerns;

use Illuminate\Support\Facades\App;
use IvanoMatteo\LaravelFulltextIndexer\LaravelFulltextIndexer;
use IvanoMatteo\LaravelFulltextIndexer\Models\FullTextEntry;

trait DirectSearch
{
    public function fullTextEntry()
    {
        return $this->morphMany($this->getFullTextEntryModel(), 'model');
    }

    public function scopeDirectSearch($q, $search)
    {
        /** @var LaravelFulltextIndexer */
        $ftindexer = App::make(LaravelFulltextIndexer::class);

        return $ftindexer->searchWithJoin($this, $q, $search);
    }


    /*

    public function getFullTextEntryModel()
    {
        return FullTextEntry::class;
    }

    public function prepareFulltextQuery(string $query): string
    {
        return $query;
    }

    public function getIndexFeatureExtractors(): array
    {
        return [];
    }

    */
}
