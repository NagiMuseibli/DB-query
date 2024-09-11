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
    protected $groupByClause = '';
    protected $havingClauses = [];
    protected $joinClauses = [];
    protected $limitClause = '';
    protected $offsetClause = '';

    public function __construct()
    {
        $this->query = '';
    }

    protected function executeQuery()
    {
        $pdo = DbConnect::getPdo();
        $stmt = $pdo->prepare($this->query);
        $stmt->execute($this->bindings);
        return $stmt;
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
        if (empty($this->whereClauses)) {
            $this->whereClauses[] = $clause;
        } else {
            if ($isOrWhere) {
                $this->whereClauses[] = "OR ($clause)";
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

    public function groupBy(...$columns)
    {
        $this->groupByClause = " GROUP BY " . implode(', ', $columns);
        return $this;
    }

    public function having($column, $operator, $value)
    {
        $clause = "$column $operator ?";
        $this->havingClauses[] = $clause;
        $this->bindings[] = $value;
        return $this;
    }

    public function join($table, $first, $operator, $second)
    {
        $this->joinClauses[] = "JOIN $table ON $first $operator $second";
        return $this;
    }

    public function leftJoin($table, $first, $operator, $second)
    {
        $this->joinClauses[] = "LEFT JOIN $table ON $first $operator $second";
        return $this;
    }

    public function limit($limit)
    {
        $this->limitClause = " LIMIT $limit";
        return $this;
    }

    public function offset($offset)
    {
        $this->offsetClause = " OFFSET $offset";
        return $this;
    }



    public function insert($data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $this->query = "INSERT INTO $this->table ($columns) VALUES ($placeholders)";
        $this->bindings = array_values($data);

        $stmt = $this->executeQuery();
        return DbConnect::getPdo()->lastInsertId();
    }

    public function update($data)
    {
        $setClause = implode(', ', array_map(function($key) {
            return "$key = ?";
        }, array_keys($data)));

        $this->query = "UPDATE $this->table SET $setClause";

        if (!empty($this->whereClauses)) {
            $this->query .= ' WHERE ' . implode(' ', $this->whereClauses);
        }

        $this->bindings = array_merge(array_values($data), $this->bindings);

        $stmt = $this->executeQuery();
        return $stmt->rowCount();
    }

    public function delete()
    {
        $this->query = "DELETE FROM $this->table";

        if (!empty($this->whereClauses)) {
            $this->query .= ' WHERE ' . implode(' ', $this->whereClauses);
        }

        $stmt = $this->executeQuery();
        return $stmt->rowCount();
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

        if (!empty($this->joinClauses)) {
            $this->query .= ' ' . implode(' ', $this->joinClauses);
        }

        if (!empty($this->whereClauses)) {
            $whereClause = implode(' ', $this->whereClauses);
            $whereClause = preg_replace('/\sAND\s\(/', ' (', $whereClause, 1);
            $this->query .= ' WHERE ' . $whereClause;
        }

        if ($this->groupByClause) {
            $this->query .= $this->groupByClause;
        }

        if (!empty($this->havingClauses)) {
            $this->query .= ' HAVING ' . implode(' AND ', $this->havingClauses);
        }

        if ($this->orderByClause) {
            $this->query .= $this->orderByClause;
        }

        if ($this->limitClause) {
            $this->query .= $this->limitClause;
        }

        if ($this->offsetClause) {
            $this->query .= $this->offsetClause;
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

        $stmt = $this->executeQuery();

        if ($this->aggregateFunction) {
            return $stmt->fetchColumn();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

