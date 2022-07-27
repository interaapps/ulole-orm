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
        return array_map(fn($f) => $f[0], $this->query("SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $name . "';")->fetchAll());
    }

    public function getIndexes(string $name): array {
        return array_map(fn($f) => $f[0], $this->query("SELECT indexname FROM pg_indexes WHERE tablename = '" . $name . "';")->fetchAll());
    }

    protected function getColumnQuery(Column $column, bool $new = false): string {
        if ($column->isAi())
            $column->setType("SERIAL");
        return parent::getColumnQuery($column);
    }

    public function edit(string $name, callable $callable): bool {
        $existingColumns = $this->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $name . "';")->fetchAll();
        $existingColumnsNames = array_map(fn($f) => $f[3], $existingColumns);
        $existingIndexes = $this->getIndexes($name);

        $blueprint = new Blueprint();
        $callable($blueprint);
        $sql = "ALTER TABLE " . $name . " ";
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
                if (($columnFromDB[6] == 'YES') != $column->isNullable())
                    $changes[] = "ALTER COLUMN {$column->getName()} SET " . ($column->isNullable() ? " SET NOT NULL" : " DROP NOT NULL");

                if ($column->isDefaultDefined())
                    $changes[] = "ALTER COLUMN {$column->getName()} SET DEFAULT '{$column->getDefault()}'";

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