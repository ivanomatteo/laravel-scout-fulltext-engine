<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine\Scopes;

use Illuminate\Database\Eloquent\Scope;

abstract class BaseScope implements Scope
{
    /** @var \Illuminate\Database\Connection */
    protected $conn;

    /**
     * @param \Illuminate\Database\Connection $conn
    */
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /** @return string */
    protected function escapeColumn($colName)
    {
        if ($colName instanceof \Illuminate\Database\Query\Expression) {
            return $colName->__toString();
        }

        return '`' . implode('`', explode('.', trim(str_replace('`', '', $colName)))) . '`';
    }

    /** @return string */
    protected function quote($value)
    {
        if ($value instanceof \Illuminate\Database\Query\Expression) {
            return $value->__toString();
        }

        return $this->conn->getPdo()->quote($value);
    }

    /** @return string */
    protected function likeEscape($value)
    {
        if ($value instanceof \Illuminate\Database\Query\Expression) {
            return $value->__toString();
        }

        return str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $value);
    }

    /** @return array */
    protected function escapeColumns(array $columns)
    {
        return collect($columns)->map(function ($c) {
            return $this->escapeColumn($c);
        })->toArray();
    }
}
