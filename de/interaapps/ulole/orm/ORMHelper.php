<?php
namespace de\interaapps\ulole\orm;

/**
 * Requires ORMModel
 */
trait ORMHelper {
    /**
     * @param null $limit
     * @param null $offset
     * @return static[]
     */
    public static function all($limit = null, $offset = null) : array {
        $query = static::table();
        if ($limit !== null)
            $query->limit($limit);

        if ($offset !== null)
            $query->offset($offset);
        return $query->all();
    }

    /**
     * @param mixed $id
     * @return static|null
     */
    public static function get(mixed $id) : static|null {
        return static::table()->where(UloleORM::getModelInformation(static::class)->getIdentifier(), $id)->get();
    }

    /**
     * @param string $var1
     * @param mixed|null $var2
     * @param mixed|null $var3
     * @return Query<static>
     */
    public static function where(string $var1, mixed $var2, mixed $var3 = null) : Query {
        return static::table()->where($var1, $var2, $var3);
    }

    /**
     * @param string $field
     * @param mixed|null $val
     * @return Query<static>
     */
    public static function like(string $field, mixed $val) : Query {
        return static::table()->like($field, $val);
    }
}