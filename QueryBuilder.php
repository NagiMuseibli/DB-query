<?php

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

    public function wehere($column, $operator, $value){
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




}