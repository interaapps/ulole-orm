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
    public static function all($limit = null, $offset = null): array {
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
    public static function get(string|int|null $id): static|null {
        return static::table()->where(UloleORM::getModelInformation(static::class)->getIdentifier(), $id)->first();
    }

    /**
     * @param string $var1
     * @param mixed|null $var2
     * @param mixed|null $var3
     * @return Query<static>
     */
    public static function where(string $var1, mixed $var2, mixed $var3 = null): Query {
        return static::table()->where($var1, $var2, $var3);
    }

    /**
     * @param string $field
     * @param mixed|null $val
     * @return Query<static>
     */
    public static function like(string $field, mixed $val): Query {
        return static::table()->like($field, $val);
    }

    /**
     * @param string $field
     * @param mixed $val1
     * @param mixed $val2
     * @return Query<static>
     */
    public static function between(string $field, mixed $val1, mixed $val2): Query {
        return static::table()->between($field, $val1, $val2);
    }

    public static function count(): int {
        return static::table()->count();
    }

    public static function sum(string $field): int|float {
        return static::table()->sum($field);
    }

    public static function sub(string $field): int|float {
        return static::table()->sub($field);
    }

    public static function avg(string $field): int|float {
        return static::table()->avg($field);
    }

    public static function max(string $field): int|float {
        return static::table()->max($field);
    }

    public static function min(string $field): int|float {
        return static::table()->min($field);
    }
}