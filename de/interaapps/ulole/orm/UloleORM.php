<?php

namespace de\interaapps\ulole\orm;

use de\interaapps\jsonplus\attributes\Serialize;
use de\interaapps\ulole\orm\drivers\Driver;
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
    public static function register(string $model): ModelInformation {
        $modelInfo = new ModelInformation($model);
        self::$modelInformation[$model] = $modelInfo;
        return $modelInfo;
    }

    /**
     * @template T
     * @param class-string<T> $model
     * @return ModelInformation<T>
     */
    public static function registerIfNot(string $model): ModelInformation {
        if (!isset(self::$modelInformation[$model]))
            self::register($model);
        return self::$modelInformation[$model];
    }

    public static function registerAll(...$models): void {
        foreach ($models as $model)
            self::register($model);
    }

    public static function database(string $name, Database $database): Database {
        self::$databases[$name] = $database;
        return $database;
    }

    public static function getDatabase($name): Database {
        return self::$databases[$name];
    }

    /**
     * @return Database[]
     */
    public static function getDatabases(): array {
        return self::$databases;
    }

    public static function autoMigrate() {
        foreach (self::$databases as $database)
            $database->autoMigrate();
    }

    public static function getTableName($modelClazz): string {
        return self::$modelInformation[$modelClazz]->getName();
    }

    /**
     * @param $model
     * @return ModelInformation
     * @throws Null
     */
    public static function getModelInformation($model): ModelInformation {
        if (!isset(self::$modelInformation[$model]))
            throw new \Exception("Register the model first with UloleORM::register(" . $model . "::class);");
        return self::$modelInformation[$model];
    }


    public static function getAttributesEnabled(): bool {
        return self::$attributesEnabled;
    }

    public static function setAttributesEnabled($attributesEnabled): void {
        self::$attributesEnabled = $attributesEnabled;
    }

    /**
     * @return ModelInformation[]
     */
    public static function getModelInformationList(): array {
        return self::$modelInformation;
    }

    public static function transformToDB(Driver $driver, ColumnInformation $columnInformation, $value) {
        $type = $columnInformation->getType()?->getName();
        if (is_bool($value)) {
            return $value ? 1 : 0;
        } else if ($type === \DateTime::class) {
            return $value->format('Y-m-d H:i:s');
        } else if ($type !== null && class_exists($type) && in_array(ORMModel::class, class_uses($type))) {
            return UloleORM::getModelInformation($type)->getIdentifierValue($value);
        } else if ($type !== null && enum_exists($type)) {
            return $value->name;
        }

        return $value;
    }
    public static function transformFromDB(Driver $driver, ColumnInformation $columnInformation, $value) {
        $type = $columnInformation->getType()?->getName();
        if ($value !== null) {
            if ($type === \DateTime::class) {
                return new \DateTime($value);
            } else if ($type !== null && class_exists($type) && in_array(ORMModel::class, class_uses($type))) {
                return (new \ReflectionClass($type))->newInstanceWithoutConstructor()->table()->whereId($value)->first();
            }  else if ($type !== null && enum_exists($type)) {
                return (new \ReflectionEnum($type))->getCase($value)->getValue();
            }
        }

        return $value;
    }
}