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
                    $query->set($fieldName, $this->{$fieldName});
            }
            return $query->whereId(UloleORM::getModelInformation(static::class)->getIdentifierValue($this))->update();
        } else {
            return $this->insert($database);
        }
    }

    public function insert(string $databaseName = 'main'): bool {
        $database = UloleORM::getDatabase($databaseName);
        $fields = [];
        $values = [];
        $modelInfo = UloleORM::getModelInformation(static::class);

        $createdAt = $modelInfo->getCreatedAt();
        if ($createdAt !== null && !isset($this->{$createdAt})) {
            if ($modelInfo->getColumnInformation($createdAt)?->getType()?->getName() === \DateTime::class) {
                $this->{$createdAt} = new \DateTime();
            } else {
                $this->{$createdAt} = date("Y-m-d H:i:s");
            }
        }

        foreach ($modelInfo->getFields() as $fieldName => $columnInformation) {
            if (isset($this->{$fieldName})) {
                $fields[] = $columnInformation->getFieldName();

                $values[] = UloleORM::transformToDB($database->getDriver(), $columnInformation, $this->{$fieldName});
            }
        }

        $insertion = UloleORM::getDatabase($databaseName)->getDriver()->insert(UloleORM::getTableName(static::class), $fields, $values);

        if ($insertion === false)
            return false;

        $this->{UloleORM::getModelInformation(static::class)->getIdentifier()} = $insertion;
        $this->ormInternals_entryExists = true;
        return true;
    }

    public function delete(string $database = 'main'): bool {
        return self::table($database)
            ->whereId(UloleORM::getModelInformation(static::class)->getIdentifierValue($this))
            ->delete();
    }

    public function ormInternals_setEntryExists(): void {
        $this->ormInternals_entryExists = true;
    }
}