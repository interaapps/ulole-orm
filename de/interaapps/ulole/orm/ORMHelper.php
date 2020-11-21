<?php
namespace de\interaapps\ulole\orm;

/**
 * Requires ORMModel
 */
trait ORMHelper {
    public static function all($limit = null, $offset = null){
        $query = static::table();
        if ($limit !== null)
            $query->limit($limit);

        if ($offset !== null)
            $query->offset($offset);
        return $query->all();
    }

    public static function get($id) {
        $instance = new static();
        return static::table()->where($instance->ormInternals_getFieldName('-id'), $id)->get();
    }

    public static function where($var1, $var2, $var3 = null) {
        return static::table()->where($var1, $var2, $var3);
    }

    public static function like($field, $val) {
        return static::table()->like($field, $val);
    }
}