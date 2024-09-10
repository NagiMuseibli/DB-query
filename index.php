<?php
require 'vendor/autoload.php';

use App\Database\DB;
use App\Query\QueryBuilder;



DB::connect('localhost', 'kargolux', 'root', '');
$queryBuilder = new QueryBuilder();
$sqlQuery = $queryBuilder->table('zamex_branches')
    ->where('foreign_id', '=', 1)
    ->select('id', 'foreign_id', 'name', 'address')
    ->orderBy('created_at')
    ->toSql();

echo "SQL Query: " . $sqlQuery . PHP_EOL;
try {
    $results = $queryBuilder->get();
    foreach ($results as $result){
        echo 'Name: '. $result['name'] . '<br>';
    }
//    print_r($results);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo '<br><br>';
//$sumQuery = $queryBuilder->table('packages')
//    ->where('status', '=', 1)
//    ->orWhere(function($query) {
//        $query->where('cargo_total', '>', 4.5)
//            ->where('cargo_weight', '>', 1);
//    })
//    ->select('id', 'cargo_total', 'cargo_weight')
//    ->sum('cargo_total * cargo_weight')
//    ->toSql();
//
//echo "SQL Query: " . $sumQuery . PHP_EOL;
//
//try {
//    $sumResult = $queryBuilder->get();
//    echo "Sum Result: " . $sumResult . PHP_EOL;
//} catch (Exception $e) {
//    echo "Error: " . $e->getMessage();
//}

