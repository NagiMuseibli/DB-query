<?php

namespace App\Query;

use App\Database\DB;
use PDO;

class QueryBuilder
{
    protected $query;
    protected $bindings = [];
    protected $aggregateFunction = null;
    protected $aggregateColumn = null;
    protected $whereClauses = [];
    protected $selectColumns = '*';
    protected $orderByClause = '';
    protected $groupedClauses = [];
    protected $isOrWhere = false;

    public function __construct()
    {
        $this->query = '';
    }

    public function table($table)
    {
        $this->query = "SELECT $this->selectColumns FROM $table";
        return $this;
    }

    protected function addWhereClause($column, $operator, $value, $isOrWhere = false)
    {
        if (empty($this->whereClauses)) {
            $this->whereClauses[] = "WHERE $column $operator ?";
        } else {
            if ($isOrWhere) {
                if (end($this->whereClauses) !== 'OR (') {
                    $this->whereClauses[] = "OR (";
                }
                $this->whereClauses[] = "$column $operator ?";
            } else {
                $this->whereClauses[] = "AND $column $operator ?";
            }
        }
        $this->bindings[] = $value;
    }


    public function where($column, $operator, $value)
    {
        $this->addWhereClause($column, $operator, $value);
        return $this;
    }

    public function orWhere(callable $callback)
    {
        if (empty($this->whereClauses)) {
            throw new \Exception('Cannot use orWhere without a previous where clause.');
        }

        $this->addWhereClause('', '', '', true);
        $callback($this);

        $this->whereClauses[] = ')';

        return $this;
    }

    public function select(...$columns)
    {
        if ($this->aggregateFunction) {
            $this->query = preg_replace('/SELECT\s.*?\sFROM/', "SELECT $this->aggregateFunction($this->aggregateColumn) AS aggregate FROM", $this->query);
        } else {
            $this->selectColumns = implode(', ', $columns);
            $this->query = preg_replace('/SELECT\s.*?\sFROM/', "SELECT $this->selectColumns FROM", $this->query);
        }
        return $this;
    }

    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderByClause = " ORDER BY $column $direction";
        return $this;
    }

    public function sum($column)
    {
        $this->aggregateFunction = 'SUM';
        $this->aggregateColumn = $column;
        return $this;
    }

    public function toSql()
    {
        if ($this->aggregateFunction) {
            if (strpos($this->query, 'SELECT') !== false) {
                $this->query = preg_replace('/SELECT\s.*?\sFROM/', "SELECT $this->aggregateFunction($this->aggregateColumn) AS aggregate FROM", $this->query);
            } else {
                $this->query = "SELECT $this->aggregateFunction($this->aggregateColumn) AS aggregate FROM " . substr($this->query, 7);
            }
        }

        if (!empty($this->whereClauses)) {
            $this->query .= ' ' . implode(' ', $this->whereClauses);
        }

        if ($this->orderByClause) {
            $this->query .= $this->orderByClause;
        }

        return $this->query;
    }

    public function get()
    {
        if (empty($this->query)) {
            throw new \Exception('Query boş ola bilməz.');
        }

        $pdo = DB::getPdo();
        $stmt = $pdo->prepare($this->query);
        $stmt->execute($this->bindings);

        if ($this->aggregateFunction) {
            return $stmt->fetchColumn();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
