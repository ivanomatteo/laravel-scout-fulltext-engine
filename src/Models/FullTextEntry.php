<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine\Models;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use IvanoMatteo\LaravelScoutFullTextEngine\FullTextIndexer;
use IvanoMatteo\LaravelScoutFullTextEngine\Pkg;
use IvanoMatteo\LaravelScoutFullTextEngine\Scopes\Search\MysqlFullTextScope;

class FullTextEntry extends Model
{
    protected $guarded = [];

    public function model()
    {
        return $this->morphTo('model');
    }

    public function scopeSearch(Builder $q, string $search, Model $targetModel)
    {
        /** @var FullTextIndexer */
        $ftindexer = App::make(FullTextIndexer::class);

        $q->where('index_name', $targetModel->searchableAs());

        $ftindexer->applyFulltextCondition($q, $search, $targetModel);
    }
}
