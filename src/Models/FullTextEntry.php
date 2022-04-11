<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine\Models;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use IvanoMatteo\LaravelScoutFullTextEngine\Pkg;
use IvanoMatteo\LaravelScoutFullTextEngine\Scopes\Search\MysqlFullTextScope;

class FullTextEntry extends Model
{
    protected $guarded = [];

    public function model()
    {
        return $this->morphTo('model');
    }

    public function scopeSearch(Builder $q, string $search, Model $model, ?Closure $customize = null)
    {
        $options = [
            'query' => $search,
            'query_prepared' => $model->prepareFullTextQuery($search),
            'index' => $model->searchableAs(),
            'fulltext_options' => Pkg::configGet('fulltext_options'),
        ];

        if ($customize) {
            $options = $customize($options);
        }

        $q->where('index_name', $options['index']);


        $scope = (new MysqlFullTextScope($this->getConnection(), ['text']))
            ->search($options['query_prepared']);

        if (! empty($options['fulltext_options']['mode'])) {
            $scope->inBooleanMode();
        }
        if (! empty($options['fulltext_options']['order_by_score'])) {
            $scope->orderByscore();
        }
        if (! empty($options['fulltext_options']['add_select_score'])) {
            $scope->addSelectScore(true);
        }

        $scope->apply($q, $this);

        /* $q->whereFullText(
            'text',
            $options['query_prepared'],
            $options['fulltext_options']
        ); */
    }
}
