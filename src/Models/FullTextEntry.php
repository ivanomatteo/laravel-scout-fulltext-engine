<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use IvanoMatteo\LaravelScoutFullTextEngine\FullTextIndexer;

/**
 * @property int $id
 * @property string $model_type
 * @property int $model_id
 * @property string $index_name
 * @property string $text
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
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
