<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine\Concerns;

use Illuminate\Support\Facades\App;
use IvanoMatteo\LaravelScoutFullTextEngine\FullTextIndexer;
use IvanoMatteo\LaravelScoutFullTextEngine\Models\FullTextEntry;

trait DirectSearch
{
    public function fullTextEntry()
    {
        return $this->morphMany($this->getFullTextEntryModel(), 'model');
    }

    public function scopeDirectSearch($q, $search)
    {
        /** @var FullTextIndexer */
        $ftindexer = App::make(FullTextIndexer::class);

        return $ftindexer->search($this, $q, $search);
    }

    public function getFullTextEntryModel()
    {
        return FullTextEntry::class;
    }

    /*


    public function prepareFullTextQuery(string $query): string
    {
        return $query;
    }

    public function getIndexFeatureExtractors(): array
    {
        return [];
    }

    */
}
