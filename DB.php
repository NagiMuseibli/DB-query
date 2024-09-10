<?php

class DB
{
    protected static $pdo;

    public function connect($host, $db, $user, $pass){
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