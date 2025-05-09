<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine\Scopes;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Scope;

abstract class BaseScope implements Scope
{
    protected Connection $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    protected function escapeColumn($colName): string
    {
        if ($colName instanceof \Illuminate\Database\Query\Expression) {
            return $colName->getValue($this->conn->getQueryGrammar());
        }

        return '`'.implode('`', explode('.', trim(str_replace('`', '', $colName)))).'`';
    }

    protected function quote($value): string
    {
        if ($value instanceof \Illuminate\Database\Query\Expression) {
            return $value->getValue($this->conn->getQueryGrammar());
        }

        return $this->conn->getPdo()->quote($value);
    }

    protected function likeEscape($value): string
    {
        if ($value instanceof \Illuminate\Database\Query\Expression) {
            return $value->getValue($this->conn->getQueryGrammar());
        }

        return str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $value);
    }

    protected function escapeColumns(array $columns): array
    {
        return collect($columns)->map(function ($c) {
            return $this->escapeColumn($c);
        })->toArray();
    }
}
