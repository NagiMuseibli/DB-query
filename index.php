<?php
require 'vendor/autoload.php';

use App\Database\DB;
use App\Query\QueryBuilder;
DB::connect('localhost', 'kargolux', 'root', '');

$query = QueryBuilder::class
    ->table('zamex_branches')
    ->where('foreign_id', '=', 1)
    ->select('id', 'name', 'address')
    ->orderBy('created_at')
    ->toSql();
echo $query;