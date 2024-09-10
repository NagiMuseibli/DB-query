<?php
namespace App\Query;
use App\Database\DB;
use PDO;

class QueryBuilder
{
    protected $table;
    protected $selects = [];
    protected $wheres = [];
    protected $orders = [];
    protected $groupBy = [];
    protected $having = [];
    protected $bindings = [];
    protected $sql;

    public function table($table){
        $this->table = $table;

        return $this;
    }

    public function select(...$columns){
        $this->selects = $columns;

        return $this;
    }

    public function where($column, $operator, $value){
        $this->wheres[] = "$column $operator ?";
        $this->bindings[] = $value;

        return $this;

    }

    public function orWhere(callable $callback){
        $query = new static;
        call_user_func($callback, $query);

        $this->wheres[] = '('.implode(' OR ', $query->wheres). ')';
        $this->bindings = array_merge($this->bindings, $query->bindings);

        return $this;
    }

    public function orderBy($column, $direction = 'ASC'){
        $this->orders[] = "$column $direction";

        return $this;
    }

    public function groupBy(...$columns){
        $this->groupBy = $columns;

        return $this;

    }

    public function having($column, $operator, $value){
        $this->having[] = "$column $operator ?";
        $this->bindings[] = $value;

        return $this;

    }

    public function toSql(){
        $this->sql = 'SELECT' . implode(',', $this->selects).'FROM'.$this->table;

        if($this->wheres){
            $this->sql .= 'WHERE'.implode('AND', $this->wheres);
        }

        if($this->groupBy){
            $this->sql .= 'GROUP BY'. implode(',', $this->groupBy);
        }

        if($this->having){
            $this->sql .= 'HAVING' . implode('AND', $this->having);

        }
        if ($this->orders){
            $this->sql .= 'ORDER BY' . implode(',', $this->orders);
        }

        return $this->sql;
    }

    public function get(){
        $pdo = DB::getPdo();
        $stmt = $pdo->prepare($this->toSql());
        $stmt->excute($this->bindings);

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function sum($expression){
        $this->selects = ["SUM($expression)"];
        $pdo = DB::getPdo();
        $stmt = $pdo->prepare($this->toSql());
        $stmt->excecute($this->bindings);

        return $stmt->fetchColumn();
    }



}