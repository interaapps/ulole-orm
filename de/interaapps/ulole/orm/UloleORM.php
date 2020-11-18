<?php
namespace de\interaapps\ulole\orm;

use ReflectionMethod;

class UloleORM {
    private static $databases = [];
    private static $tableNames = [];

    public static function register($name, $model) {
        self::$tableNames[$model] = $name;
    }

    public static function database(string $name, Database $database){
        self::$databases[$name] = $database;
    }

    public static function getDatabase($name){
        return self::$databases[$name];
    }


    public static function getTableName($modelClazz){
        return self::$tableNames[$modelClazz];
    }
}