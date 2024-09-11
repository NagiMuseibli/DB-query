<?php
namespace App\Query;

use App\Database\DbConnect;
use PDO;

class DB
{
    protected $query;
    protected $table;
    protected $bindings = [];
    protected $aggregateFunction = null;
    protected $aggregateColumn = null;
    protected $whereClauses = [];
    protected $selectColumns = '*';
    protected $orderByClause = '';
    protected $groupedClauses = [];
    protected $isOrWhere = false;
    protected  $isFirst = true;
    public function __construct()
    {
        $this->query = '';
    }

    public static function table($table)
    {
        $instance = new self();
        $instance->table = $table;
        $instance->query = "SELECT $instance->selectColumns FROM $table";
        return $instance;
    }

    protected function addWhereClause($column, $operator, $value, $isOrWhere = false)
    {
        $clause = "$column $operator ?";

        if ($isOrWhere) {
            if (empty($this->whereClauses)) {
                $this->whereClauses[] = $clause;
            } else {
                $this->whereClauses[] = "OR ($clause)";
            }
        } else {
            if (empty($this->whereClauses)) {
                $this->whereClauses[] = $clause;
            } else {
                $this->whereClauses[] = "AND ($clause)";
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

        $this->whereClauses[] = 'OR (';
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
        $pdo = DbConnect::getPdo();
        $stmt = $pdo->prepare($this->toSql());
        $stmt->execute($this->bindings);

        return $stmt->fetchColumn();
    }

    public static function raw($expression)
    {
        return $expression;
    }

    public function toSql()
    {
        if ($this->aggregateFunction) {
            $this->query = preg_replace('/SELECT\s.*?\sFROM/', "SELECT $this->aggregateFunction($this->aggregateColumn) AS aggregate FROM", $this->query);
        }

        if (!empty($this->whereClauses)) {
            $whereClause = implode(' ', $this->whereClauses);
            $whereClause = preg_replace('/\sAND\s\(/', ' (', $whereClause, 1);
            $this->query .= ' WHERE ' . $whereClause;
        }

        if ($this->orderByClause) {
            $this->query .= $this->orderByClause;
        }

        return $this->query;
    }


    public function __toString()
    {
        return $this->toSql();
    }

    public function get()
    {
        if (empty($this->query)) {
            throw new \Exception('Query boş ola bilməz.');
        }

        echo "SQL Query: " . $this->toSql() . "\n";
        echo "Bindings: ";
        print_r($this->bindings);

        $pdo = DbConnect::getPdo();
        $stmt = $pdo->prepare($this->query);
        $stmt->execute($this->bindings);

        if ($this->aggregateFunction) {
            return $stmt->fetchColumn();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


}
