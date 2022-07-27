<?php

namespace de\interaapps\ulole\orm\drivers;

use de\interaapps\ulole\orm\migration\Blueprint;
use de\interaapps\ulole\orm\migration\Column;
use PDO;

class PostgresDriver extends SQLDriver {
    public function __construct(PDO $connection) {
        $this->connection = $connection;
        $this->aiType = "";
    }

    public function getTables(): array {
        return array_map(fn ($r) => $r[1], $this->query("SELECT * FROM pg_catalog.pg_tables;")->fetchAll());
    }

    public function getColumns(string $name): array {
        return array_map(fn($f) => $f[0], $this->query("SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$name';")->fetchAll());
    }

    public function getIndexes(string $name): array {
        return array_map(fn($f) => $f[0], $this->query("SELECT indexname FROM pg_indexes WHERE tablename = '$name';")->fetchAll());
    }

    public function getConstraints(string $name): array {
        return $this->query("select constraint_type, constraint_name from information_schema.table_constraints where table_name='$name';")->fetchAll();
    }

    protected function getColumnQuery(Column $column, bool $new = false): string {
        if ($column->isAi())
            $column->setType("SERIAL");
        return parent::getColumnQuery($column);
    }

    public function edit(string $name, callable $callable): bool {
        $existingColumns = $this->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $name . "';")->fetchAll();
        $existingIndexes = $this->getIndexes($name);
        $existingConstraints = $this->getConstraints($name);
        $uniqueKeys = array_map(fn ($f) => $f["constraint_name"], array_filter($existingConstraints, fn($f) => $f["constraint_type"] == "UNIQUE"));
        var_dump($existingColumns);
        $blueprint = new Blueprint();
        $callable($blueprint);
        $sql = "ALTER TABLE $name ";
        $changes = [];

        foreach ($blueprint->getColumns() as $column) {
            $columnFromDB = null;

            foreach ($existingColumns as $dbCol) {
                if ($dbCol[3] === $column->getName()) {
                    $columnFromDB = $dbCol;
                    break;
                }
            }

            if ($columnFromDB !== null) {
                $changes[] = "ALTER COLUMN {$column->getName()} TYPE " . $column->getType() . ($column->getSize() !== null ? "(" . $column->getSize() . ")" : "");

                if (($columnFromDB[6] == 'YES') != $column->isNullable())
                    $changes[] = "ALTER COLUMN {$column->getName()} " . ($column->isNullable() ? " SET NOT NULL" : " DROP NOT NULL");

                if ($column->isDefaultDefined())
                    $changes[] = "ALTER COLUMN {$column->getName()} SET DEFAULT '{$column->getDefault()}'";

                if (!in_array($column->getName() . '_unique', $uniqueKeys) && $column->isUnique())
                    $changes[] = "ADD CONSTRAINT {$column->getName()}_unique UNIQUE ({$column->getName()})";

                if (in_array($column->getName() . '_unique', $uniqueKeys) && !$column->isUnique())
                    $changes[] = "DROP CONSTRAINT {$column->getName()}_unique";

                if ($column->isUnique())
                    $changes[] = "ADD UNIQUE ({$column->getName()})";

                if ($column->isDrop())
                    $changes[] = "DROP COLUMN {$column->getName()}";
            } else {
                $changes[] = "ADD COLUMN {$this->getColumnQuery($column)}";
            }
        }

        if (count($changes) > 0 && $this->query($sql . implode(", ", $changes) . ";") === false)
            return false;

        foreach ($blueprint->getIndexes() as $index) {
            $index = $index->getName();
            if (!in_array($index, $existingIndexes))
                $this->query("CREATE INDEX $index ON $name ($index);");
        }

        foreach ($blueprint->getColumns() as $column) {
            if ($column->getRenameTo() !== null)
                $this->query("ALTER TABLE $name RENAME COLUMN {$column->getName()} to {$column->getRenameTo()};");
        }

        return true;
    }

}