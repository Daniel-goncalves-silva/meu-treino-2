<?php
namespace App\Helpers;
use PDO;
final class Database {
    public static function connect(): PDO {
        return new PDO('mysql:host='.Config::get('DB_HOST','127.0.0.1').';port='.Config::get('DB_PORT','3306').';dbname='.Config::get('DB_NAME','meu_treino').';charset=utf8mb4',
            Config::get('DB_USER'), Config::get('DB_PASS'), [
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES=>false
            ]);
    }
}
