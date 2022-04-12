<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine\Scout;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\App;
use IvanoMatteo\LaravelScoutFullTextEngine\FullTextIndexer;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;

class ScoutEngine extends Engine
{
    private FullTextIndexer $ftindexer;

    public function __construct()
    {
        $this->ftindexer = App::make(FullTextIndexer::class);
    }

    /**
     * Update the given model in the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function update($models)
    {
        $models->each(fn ($m) => $this->ftindexer->addModelToIndex($m));
    }

    /**
     * Remove the given model from the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function delete($models)
    {
        $models->each(fn ($m) => $this->ftindexer->removeModelFromIndex($m));
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @return mixed
     */
    public function search(Builder $builder)
    {
        $model = $builder->model;
        $query = $builder->query;

        $q = $model::query();

        if ($builder->limit) {
            $q->limit($builder->limit);
        }

        $wheres = $builder->wheres;
        $withTrashed = $wheres['__soft_deleted'] ?? true;
        unset($wheres['__soft_deleted']);

        foreach ($wheres as $field => $value) {
            $q->where($field, '=', $value);
        }

        if ($withTrashed && $this->usesSoftDelete($model)) {
            if ($withTrashed === 1) {
                $q->onlyTrashed();
            } else {
                $q->withTrashed();
            }
        }

        foreach ($builder->whereIns as $field => $values) {
            $q->whereIn($field, $values);
        }

        foreach ($builder->orders as $order) {
            $q->orderBy($order['column'], $order['direction']);
        }

        return $this->ftindexer->search($model, $q, $query);
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  int  $perPage
     * @param  int  $page
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        return $this->search($builder)->paginate($perPage, ['*'], 'page', $page)
            ->map(fn ($x) => $x);
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  mixed  $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        return $results->get()->modelKeys();
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        if (
            $results instanceof EloquentBuilder ||
            $results instanceof QueryBuilder
        ) {
            return $results->get();
        }

        return $results;
    }

    /**
     * Map the given results to instances of the given model via a lazy collection.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Support\LazyCollection
     */
    public function lazyMap(Builder $builder, $results, $model)
    {
        return $results->lazy();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed  $results
     * @return int
     */
    public function getTotalCount($results)
    {
        $results->count();
    }

    /**
     * Flush all of the model's records from the engine.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function flush($model)
    {
        $this->ftindexer->flushModel($model);
    }

    /**
     * Create a search index.
     *
     * @param  string  $name
     * @param  array  $options
     * @return mixed
     */
    public function createIndex($name, array $options = [])
    {
        //
    }

    /**
     * Delete a search index.
     *
     * @param  string  $name
     * @return mixed
     */
    public function deleteIndex($name)
    {
        //
    }

    private static function usesSoftDelete($class)
    {
        return ! empty(class_uses_recursive($class)[SoftDeletes::class]);
    }
}
