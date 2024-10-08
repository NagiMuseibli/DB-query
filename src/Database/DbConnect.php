<?php
namespace App\Database;

use PDO;
use PDOException;

class DbConnect
{
    protected static $pdo;

    public static function connect($host, $db, $user, $pass): void
    {
        try {
            self::$pdo = new PDO("mysql:host=$host; dbname=$db", $user, $pass);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }catch (PDOException $e){
            die("Database connected failed: " . $e->getMessage());
        }
    }

    public static function getPdo()
    {
        return self::$pdo;
    }
}