<?php
require 'vendor/autoload.php';

use App\Database\DbConnect;
use App\Query\DB;

DbConnect::connect('localhost', 'kargolux', 'root', '');


$sqlQuery =  DB::table('zamex_branches')
    ->where('foreign_id', '=', 1)
    ->select('id', 'name', 'address')
    ->orderBy('created_at')
    ->get();
echo '<pre>';
print_r($sqlQuery);
echo '</pre>';
//echo "SQL Query: " . $sqlQuery . PHP_EOL;


echo '<br><br>';
$sumResult = DB::table('packages')
    ->where('status', '=', 1)
    ->orWhere(function($query) {
        $query->where('cargo_total', '>', 4.5)
            ->where('cargo_weight', '>', 1);
    })
//    ->toSql();
    ->sum(DB::raw('cargo_total * cargo_weight'));
//    ->toSql();
echo "SQL Query: " . $sumResult . PHP_EOL;

/**
 *
 * toSql() metodu sql sorgusubu gosterir.
 * get() metodu sorugunu cap edir
 * sum() metodu sorugunu get() metodu istifade etmeden cap edir..
 */

