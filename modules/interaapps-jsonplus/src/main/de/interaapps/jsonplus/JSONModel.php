<?php
namespace de\interaapps\jsonplus;

trait JSONModel {
    private static JSONPlus $jsonPlusInstance;

    public static function fromJson($json) : self {
        if (!isset(self::$jsonPlusInstance)) self::$jsonPlusInstance = JSONPlus::$default;
        return self::$jsonPlusInstance->fromJson($json, self::class);
    }

    public function toJson() : string {
        if (!isset(self::$jsonPlusInstance)) self::$jsonPlusInstance = JSONPlus::$default;
        return self::$jsonPlusInstance->toJson($this, self::class);
    }

    public static function setJsonPlusInstance(JSONPlus $jsonPlusInstance): void {
        self::$jsonPlusInstance = $jsonPlusInstance;
    }

}