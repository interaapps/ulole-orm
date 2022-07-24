<?php

namespace de\interaapps\ulole\orm;

use de\interaapps\jsonplus\attributes\Serialize;

trait ORMModel {
    #[Serialize(hidden: true)]
    private $ormInternals_entryExists = false;

    /**
     * @param string $database
     * @return Query<static>
     */
    public static function table(string $database = 'main'): Query {
        return new Query(UloleORM::getDatabase($database), static::class);
    }

    public function save(string $database = 'main'): bool {
        if ($this->ormInternals_entryExists) {
            $query = self::table($database);
            foreach (UloleORM::getModelInformation(static::class)->getFields() as $fieldName => $modelInformation) {
                //if (in_array($fieldName, $this->ormInternals_getSettings()["exclude"]))
                //    continue;
                if (isset($this->{$fieldName}))
                    $query->set($modelInformation->getFieldName(), $this->{$fieldName});
            }
            return $query->where(UloleORM::getModelInformation(static::class)->getIdentifier(), UloleORM::getModelInformation(static::class)->getIdentifierValue())->update() !== false;
        } else {
            return $this->insert($database);
        }
    }

    public function insert(string $database = 'main'): bool {
        $fields = [];
        $values = [];
        foreach (UloleORM::getModelInformation(static::class)->getFields() as $fieldName => $modelInformation) {
            if (isset($this->{$fieldName})) {
                $fields[] = $modelInformation->getFieldName();
                $values[] = $this->{$fieldName};
            }
        }

        $query = 'INSERT INTO `' . UloleORM::getTableName(static::class) . '` (';

        foreach ($fields as $i => $field)
            $query .= ($i == 0 ? '' : ', ') . '`' . $field . '`';

        $query .= ') VALUES (';

        foreach ($values as $i => $value)
            $query .= ($i == 0 ? '' : ', ') . '?';
        $query .= ')';

        $statement = UloleORM::getDatabase($database)->getConnection()->prepare($query);

        $result = $statement->execute($values);
        $this->{UloleORM::getModelInformation(static::class)->getIdentifier()} = UloleORM::getDatabase($database)->getConnection()->lastInsertId();
        if ($result)
            $this->ormInternals_entryExists = true;
        return $result;
    }

    public function delete(string $database = 'main'): bool {
        return self::table($database)
            ->where(UloleORM::getModelInformation(static::class)->getIdentifier(), UloleORM::getModelInformation(static::class)->getIdentifierValue($this))
            ->delete();
    }

    public function ormInternals_setEntryExists(): void {
        $this->ormInternals_entryExists = true;
    }
}