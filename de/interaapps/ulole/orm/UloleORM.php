<?php
namespace de\interaapps\ulole\orm;

use de\interaapps\jsonplus\attributes\Serialize;
use ReflectionMethod;

class UloleORM {
    /**
     * @var array<string, Database>
     */
    private static array $databases = [];
    /**
     * @var array<string, ModelInformation>
     */
    private static array $modelInformation = [];
    private static bool $attributesEnabled = false;

    /**
     * @template T
     * @param class-string<T> $var1
     * @return ModelInformation<T>
     */
    public static function register(string $model) : ModelInformation {
        $modelInfo = new ModelInformation($model);
        self::$modelInformation[$model] = $modelInfo;
        return $modelInfo;
    }

    /**
     * @template T
     * @param class-string<T> $model
     * @return ModelInformation<T>
     */
    public static function registerIfNot(string $model) : ModelInformation {
        if (!isset(self::$modelInformation[$model]))
            self::register($model);
        return self::$modelInformation[$model];
    }

    public static function registerMultiple($models = []) : void {
        foreach ($models as $name => $model)
            self::register($name, $model);
    }

    public static function database(string $name, Database $database) : Database {
        self::$databases[$name] = $database;
        return $database;
    }

    public static function getDatabase($name) : Database {
        return self::$databases[$name];
    }

    public static function autoMigrate() {
        foreach (self::$databases as $database)
            $database->autoMigrate();
    }

    public static function getTableName($modelClazz) : string {
        return self::$modelInformation[$modelClazz]->getName();
    }

    public static function getModelInformation($model) : ModelInformation {
        return self::$modelInformation[$model];
    }

    public static function getAttributesEnabled() : bool {
        return self::$attributesEnabled;
    }

    public static function setAttributesEnabled($attributesEnabled): void {
        self::$attributesEnabled = $attributesEnabled;
    }

    /**
     * @return ModelInformation[]
     */
    public static function getModelInformationList() : array {
        return self::$modelInformation;
    }
}