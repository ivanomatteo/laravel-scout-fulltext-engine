<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine\Scopes\Search;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use IvanoMatteo\LaravelScoutFullTextEngine\Scopes\BaseScope;
use RuntimeException;

class MysqlFullTextScope extends BaseScope
{
    private ?string $searchText = null;

    private string $mode = 'in natural language mode';

    private ?Closure $conditionManipulator = null;

    private bool $addSelectScore = false;

    private bool $orderByScore = false;

    private bool $selectAllIfNoColumn = false;

    private array $columns;

    public function __construct(Connection $conn, array $columns)
    {
        parent::__construct($conn);
        $this->columns = $columns;
    }

    public function apply(Builder|EloquentBuilder $q, Model $model)
    {
        $cond = $this->getFullTextCondition();

        $q->whereRaw($cond);

        if ($this->addSelectScore) {
            if ($this->selectAllIfNoColumn && empty($q->getQuery()->columns)) {
                $q->addSelect('*');
            }
            $q->addSelect($this->conn->raw("($cond) as fulltext_score"));
        }

        if ($this->orderByScore) {
            $q->orderByRaw("($cond) DESC");
        }
    }

    public function search(string $searchText): static
    {
        $this->searchText = $searchText;

        return $this;
    }

    public function inBooleanMode(): static
    {
        $this->mode = 'in boolean mode';

        return $this;
    }

    public function conditionManipulator(Closure $conditionManipulator): static
    {
        $this->conditionManipulator = $conditionManipulator;

        return $this;
    }

    public function orderByscore(): static
    {
        $this->orderByScore = true;

        return $this;
    }

    public function addSelectScore(bool $selectAllIfNoColumn = false): static
    {
        $this->addSelectScore = true;
        $this->selectAllIfNoColumn = $selectAllIfNoColumn;

        return $this;
    }

    public function getFullTextCondition(): string
    {
        if ($this->searchText === null) {
            throw new RuntimeException('searchText not specified');
        }

        $sqlCond = 'MATCH ('.implode(', ', $this->escapeColumns($this->columns)).') AGAINST ('.
            $this->quote($this->searchText)
            ." {$this->mode} )";

        if ($this->conditionManipulator) {
            return ($this->conditionManipulator)($sqlCond, $this->searchText);
        }

        return $sqlCond;
    }
}
