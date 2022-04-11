<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use IvanoMatteo\LaravelScoutFullTextEngine\Models\FullTextEntry;
use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Extractors\FeatureExtractor;
use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Query\QueryParser;
use IvanoMatteo\LaravelScoutFullTextEngine\Scopes\Search\MysqlFullTextScope;

class LaravelScoutFullTextEngine
{
    private ?Collection $defaultExtractors = null;

    public function addModelToIndex(Model $model): void
    {
        $stringData = collect($model->toSearchableArray())->flatten()->implode(' ');

        $indexModel = method_exists($model, 'getFullTextEntryModel') ?
            $model->getFullTextEntryModel() : FullTextEntry::class;


        $indexModel::updateOrCreate([
            'index_name' => $model->searchableAs(),
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
        ], [
            'text' => $this->runExtractors($model, $stringData),
        ]);
    }

    public function removeModelFromIndex(Model $model): void
    {
        FullTextEntry::where('index_name', $model->searchableAs())
            ->where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->delete();
    }

    public function flushModel(Model $model): void
    {
        FullTextEntry::where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->delete();
    }

    private function getDefaultExtractors()
    {
        if (! isset($this->defaultExtractors)) {
            $this->defaultExtractors = collect(Pkg::configGet('pre_processing.index_data.extractors'))
                ->map(fn ($extr) => App::make($extr));
        }

        return $this->defaultExtractors;
    }

    private function runExtractors(Model $model, string $stringData): string
    {
        $extractors = null;

        if (method_exists($model, 'getIndexFeatureExtractors')) {
            $tmp = $model->getIndexFeatureExtractors();
            if (! empty($tmp)) {
                $extractors = $tmp;
            }
        }

        if ($extractors === null) {
            $extractors = $this->getDefaultExtractors();
        }

        $extracted = $extractors->reduce(function ($carry, FeatureExtractor $extr) use ($stringData) {
            $tmp = trim(implode(' ', $extr->extract($stringData)));
            if ($tmp) {
                $carry = ' ' . $tmp;
            }

            return $carry;
        }, '');

        if ($extracted) {
            $stringData .= ' ' . $extracted;
        }

        return $stringData;
    }

    public function searchWithJoin(Model $model, Builder $q, string $search)
    {
        $relatedModel = method_exists($model, 'getFullTextEntryModel') ?
            $model->getFullTextEntryModel() : FullTextEntry::class;

        $relatedTable = (new $relatedModel())->getTable();

        $q->join($relatedTable, function ($join) use ($model, $relatedTable) {
            $join->on(
                $model->getTable() . '.' . $model->getKeyName(),
                '=',
                "$relatedTable.model_id"
            )->on(
                "$relatedTable.model_type",
                '=',
                DB::raw(DB::getPdo()->quote(get_class($model)))
            );
        });


        if (empty($q->getQuery()->columns)) {
            $q->select($model->getTable() . '.*');
        }

        $query_prepared = $search;

        if (method_exists($model, 'prepareFullTextQuery')) {
            $query_prepared = $model->prepareFullTextQuery();
        } elseif (App::bound(QueryParser::class)) {
            /** @var QueryParser */
            $parser = App::make(QueryParser::class);
            $query_prepared = $parser->parseSearchText($search);
        }

        $options = [
            'query' => $search,
            'query_prepared' => $query_prepared,
            'index' => $model->searchableAs(),
            'fulltext_options' => Pkg::configGet('fulltext_options'),
        ];

        $q->where('index_name', $options['index']);

        $connection = $model->getConnection();

        if ($connection->getDriverName() === 'mysql') {
            $scope = (new MysqlFullTextScope($connection, ['text']))
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
            $scope->apply($q, $model);
        } else {
            $q->whereFullText(
                'text',
                $options['query_prepared'],
                $options['fulltext_options']
            );
        }

        return $q;
    }
}
