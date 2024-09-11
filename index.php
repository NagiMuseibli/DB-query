<?php
require 'vendor/autoload.php';

use App\Database\DbConnect;
use App\Query\DB;

DbConnect::connect('localhost', 'kargolux', 'root', '');


$sqlQuery1 =  DB::table('package_statuses')
    ->where('package_id', '=', 139)
    ->select('id', 'user_id', 'status')
    ->orderBy('created_at')
    ->limit(2)
    ->offset(0)
    ->get();
echo '<pre>';
print_r($sqlQuery1);
echo '</pre>';
echo '<br><br>';echo '<br><br>';
$sqlQuery2 = DB::table('zamex_branches')
    ->join('filials', 'zamex_branches.foreign_id', '=', 'filials.id')
    ->select('zamex_branches.id', 'zamex_branches.name', 'zamex_branches.address')
    ->where('zamex_branches.name', '=', 'Oğuz')
    ->get();
echo '<pre>';
print_r($sqlQuery2);
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
echo "Sum result: " . $sumResult . PHP_EOL;

/**
 *
 * toSql() metodu sql sorgusunun sintaksizini gosterir.
 * get() metodu sorugunu cap edir
 * sum() metodu sorugunu get() metodu istifade etmeden cap edir..
 */

