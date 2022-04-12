<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine;

use DASPRiD\Enum\Exception\IllegalArgumentException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use IvanoMatteo\LaravelScoutFullTextEngine\Models\FullTextEntry;
use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Extractors\FeatureExtractor;
use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Query\QueryParser;
use IvanoMatteo\LaravelScoutFullTextEngine\Scopes\Search\MysqlFullTextScope;

class FullTextIndexer
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

    public function search(Model $model, Builder|EloquentBuilder $q, string $search)
    {
        $bind_mode = Pkg::configGet('fulltext_options.bind_mode');

        if ($bind_mode === 'join') {
            return $this->searchUsingJoin($model, $q, $search);
        }

        if ($bind_mode === 'exists') {
            return $this->searchUsingExists($model, $q, $search);
        }

        throw new IllegalArgumentException('unknown bind_mode:' . $bind_mode);
    }

    private function searchUsingJoin(Model $model, Builder|EloquentBuilder $q, string $search)
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
            )->where(
                "$relatedTable.index_name",
                '=',
                $model->searchableAs()
            );
        });

        $q->select(collect($q->getQuery()->columns ?? [])
            ->map(function ($col) use ($model) {
                if ($col instanceof \Illuminate\Database\Query\Expression) {
                    return $col;
                }
                if (! Str::contains($col, '.')) {
                    return $model->getTable() . '.' . $col;
                }

                return $col;
            })->toArray());

        if (empty($q->getQuery()->columns)) {
            $q->select($model->getTable() . '.*');
        }

        $this->applyFulltextCondition($q, $search, $model, $relatedTable);

        return $q;
    }

    private function searchUsingExists(Model $model, Builder|EloquentBuilder $q, string $search)
    {
        $relatedModel = method_exists($model, 'getFullTextEntryModel') ?
            $model->getFullTextEntryModel() : FullTextEntry::class;

        $relatedTable = (new $relatedModel())->getTable();

        $q->whereExists(function ($q) use ($relatedTable, $model, $search) {
            $q->select(DB::raw(1))
                ->from($relatedTable)
                ->whereColumn($relatedTable . '.model_id', $model->getTable() . '.id')
                ->where($relatedTable . '.model_type', get_class($model))
                ->where('index_name', $model->searchableAs());

            $this->applyFulltextCondition($q, $search, $model);
        });

        return $q;
    }

    private function applyFulltextCondition(Builder|EloquentBuilder $q, string $search, Model $model, $table = '')
    {
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
            'fulltext_options' => Pkg::configGet('fulltext_options'),
        ];

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
                ($table ? "$table." : '') . 'text',
                $options['query_prepared'],
                $options['fulltext_options']
            );
        }

        return $q;
    }
}
