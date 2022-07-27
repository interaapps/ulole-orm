<?php

namespace de\interaapps\ulole\orm\drivers;

use de\interaapps\ulole\orm\migration\Blueprint;
use de\interaapps\ulole\orm\migration\Column;
use de\interaapps\ulole\orm\Query;
use de\interaapps\ulole\orm\UloleORM;
use PDO;
use PDOStatement;
use stdClass;

abstract class SQLDriver implements Driver {
    protected string $aiType = "AUTO_INCREMENT";

    public function __construct(
        protected PDO $connection
    ) {
    }

    public function query($sql): PDOStatement|bool {
        return $this->connection->query($sql);
    }

    public function preparedQuery(string $model, string $query, array $vars = [], bool $returnResult = false): PDOStatement|bool|null {
        $statement = $this->connection->prepare($query);

        $result = $statement->execute($vars);
        if ($returnResult)
            return $result;
        if ($result === false)
            return null;
        $statement->setFetchMode(PDO::FETCH_CLASS, $model);
        return $statement;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    public function create(string $name, $callable, bool $ifNotExists = false): bool {
        $blueprint = new Blueprint();
        $callable($blueprint);
        $sql = "CREATE TABLE " . ($ifNotExists ? "IF NOT EXISTS " : "") . $name . " (\n";
        $sql .= implode(",\n", $this->getQueries($blueprint, true));

        $indexes = $blueprint->getIndexes();

        if (count($indexes) > 0)
            $sql .= ", INDEX (" . implode(", ", array_map(fn(Column $c) => $c->getName(), $indexes)) . ")";

        $sql .= "\n);";

        return $this->query($sql) !== false;
    }

    public function getColumns(string $name) : array {
        return array_map(fn($f) => $f[0], $this->query('SHOW COLUMNS FROM ' . $name . ';')->fetchAll());
    }

    public function getIndexes(string $name) : array {
        return array_map(fn($f) => $f[0], $this->query('SHOW COLUMNS FROM ' . $name . ';')->fetchAll());
    }

    public function edit(string $name, callable $callable): bool {
        $existingColumns = $this->getColumns($name);
        $existingIndexes = $this->getIndexes($name);

        $blueprint = new Blueprint();
        $callable($blueprint);
        $sql = "ALTER TABLE " . $name . " ";
        $changes = [];
        foreach ($this->getQueries($blueprint) as $column => $query) {
            if (in_array($column, $existingColumns))
                $changes[] = str_starts_with($query, "DROP") ? "" : ("MODIFY COLUMN  " . $query);
            else
                $changes[] = " ADD " . $query;
        }

        foreach ($blueprint->getIndexes() as $index) {
            $index = $index->getName();
            if (!in_array($index, $existingIndexes))
                $changes[] = 'ADD INDEX (' . $index . ');';
        }
        $sql .= implode(", ", $changes);

        $sql .= ";";

        return $this->query($sql) !== false;
    }

    public function drop(string $name): bool {
        return $this->query("DROP TABLE " . $name . ";") !== false;
    }

    public function insert(string $table, array $fields, array $values): string|false {
        $query = 'INSERT INTO ' . $table . ' (';

        $query .= implode(", ",  $fields);
        $query .= ') VALUES (';
        $query .= implode(", ", array_map(fn ($f) => '?', $values));
        $query .= ')';

        $statement = $this->connection->prepare($query);

        $result = $statement->execute(array_map(fn($f) => is_bool($f) ? ($f ? "true" : "false") : $f, $values));
        if (!$result)
            return false;

        return $this->connection->lastInsertId();
    }

    protected function getQueries(Blueprint $blueprint, bool $new = false): array {
        $columns = [];
        foreach ($blueprint->getColumns() as $column) {
            $columns[$column->getName()] = $this->getColumnQuery($column, $new);
        }

        return $columns;
    }

    protected function getColumnQuery(Column $column, bool $new = false): string {
        if ($column->isDrop())
            return "DROP COLUMN " . $column->getName();

        // "`" . ($column->getRenameTo() === null ? $column->getName() : $column->getRenameTo()). "`"
        $structure = $column->getName() . " " . $column->getType();
        if ($column->getSize() !== null)
            $structure .= "(" . $column->getSize() . ")";
        $structure .= " ";
        if ($column->isDefaultDefined()) {
            $structure .= "DEFAULT ";
            if ($column->getDefault() === null) {
                $structure .= "NULL";
            } else if (!$column->isEscapeDefault()) {
                $structure .= $column->getDefault();
            } else {
                $structure .= "'" . addslashes($column->getDefault()) . "'";
            }
            $structure .= " ";
        }
        $structure .= $column->isNullable() ? "NULL " : "NOT NULL ";
        if ($column->isPrimary() && $new)
            $structure .= "PRIMARY KEY ";
        if ($column->isAi())
            $structure .= " $this->aiType";
        return $structure;
    }

    public function getTables(): array {
        return array_map(fn ($r) => $r[0], $this->query("SHOW TABLES;")->fetchAll());
    }

    public function createQuery(Query $q): stdClass {
        $deletedAt = $q->getModelInformation()->getDeletedAt();
        if ($deletedAt !== null && !$q->isWithDeleted())
            $q->isNull($q->getModelInformation()->getFieldName($deletedAt));

        $out = (object)["query" => '', 'vars' => []];

        $usedWhere = $q->isTemporaryQuery();
        $usedSet = false;
        $useCondition = false;

        foreach (array_filter($q->getQueries(), fn($q) => $q["type"] == "SET") as $query) {
            if (!$usedSet) {
                $out->query .= " SET ";
                $usedSet = true;
            } else
                $out->query .= ",";
            $out->query .= ' ' . $query["query"] . ' ';
            $out->vars[] = $query['val'];
        }

        foreach ($q->getQueries() as $query) {
            if ($query["type"] == 'AND' || $query["type"] == 'OR' || $query["type"] == 'NOT') {
                if (!$usedWhere) {
                    $out->query .= " WHERE ";
                    $usedWhere = true;
                }
                if ($useCondition || $query["type"] == 'NOT') {
                    if ($query["type"] == 'NOT') {
                        if ($useCondition) {
                            $out->query .= " AND ";
                        } else
                            $useCondition = true;
                    }
                    $out->query .= $query["type"];
                } else
                    $useCondition = true;
                if (isset($query["query"])) {
                    $out->query .= ' ' . $query["query"] . ' ';
                    if (isset($query['val']))
                        $out->vars[] = $query['val'];
                    if (isset($query['vals']))
                        $out->vars = array_merge($out->vars, $query['vals']);
                } else if (isset($query["queries"])) {
                    $out->query .= ' (';
                    $where = ($query["queries"])->buildQuery();
                    $out->vars = array_merge($out->vars, $where->vars);
                    $out->query .= $where->query;
                    $out->query .= ') ';
                }
            } else if ($query["type"] == 'WHERE_EXISTS' || $query["type"] == 'WHERE_NOT_EXISTS') {
                $out->query .= " WHERE " . ($query["type"] == 'WHERE_NOT_EXISTS' ? "NOT " : "") . "EXISTS (" . $query["query"] . ")";
            } else if ($query["type"] == 'WHERE_IN') {
                $out->query .= " WHERE " . $query["column"] . ($query["not"] ? " NOT " : ' ') . " IN (" . $query["query"] . ")";
                $out->vars = $query["vars"];
            }
        }

        if ($q->getOrderBy() !== null)
            $out->query .= ' ORDER BY ' . $q->getOrderBy()["orderBy"] . ' ' . ($q->getOrderBy()["desc"] ? 'DESC ' : ' ');

        if ($q->getLimit() !== null)
            $out->query .= ' LIMIT ' . $q->getLimit() . ' ' . ($q->getOffset() === null ? '' : 'OFFSET ' . $q->getOffset()) . ' ';
        return $out;
    }

    public function delete(string $model, Query $query): bool {
        $q = $this->createQuery($query);
        return  $this->preparedQuery($model, 'DELETE FROM ' . UloleORM::getTableName($model) . $q->query . ';', $q->vars, true) !== false;
    }

    public function update(string $model, Query $query): bool {
        $q = $this->createQuery($query);
        return $this->preparedQuery($model, 'UPDATE ' . UloleORM::getTableName($model) . $q->query . ';', $q->vars, true) !== false;
    }

    public function get(string $model, Query $query): array {
        $q = $this->createQuery($query);

        $statement = $this->preparedQuery($model, 'SELECT * FROM ' . UloleORM::getTableName($model) . $q->query . ';', $q->vars);
        $result = $statement->fetchAll();
        foreach ($result as $entry) {
            $entry->ormInternals_setEntryExists();
            foreach (UloleORM::getModelInformation($model)->getFields() as $name => $colInfo) {
                if (isset($entry->{$colInfo->getFieldName()}))
                    $entry->{$name} = $entry->{$colInfo->getFieldName()};
            }
        }
        return $result;
    }

    private function getField(string $model, Query $query, string $raw, string|null $values = null): mixed {
        $q = $this->createQuery($query);

        if ($values !== null)
            $raw .= "($values)";

        $statement = $this->preparedQuery($model, 'SELECT ' . $raw . ' as num FROM ' . UloleORM::getTableName($model) . $q->query . ';', $q->vars);
        $statement->setFetchMode(PDO::FETCH_NUM);
        $result = $statement->fetch();
        if ($result === false)
            return null;

        return $result[0];
    }

    public function count(string $model, Query $query): int|float {
        return $this->getField($model, $query, "COUNT", '*');
    }
    public function sum(string $model, Query $query, string $field): int|float {
        return $this->getField($model, $query, "SUM", $field);
    }
    public function sub(string $model, Query $query, string $field): int|float {
        return $this->getField($model, $query, "SUM", "-$field");
    }

    public function avg(string $model, Query $query, string $field): int|float {
        return $this->getField($model, $query, "AVG", $field);
    }
    public function min(string $model, Query $query, string $field): int|float {
        return $this->getField($model, $query, "MIN", $field);
    }

    public function max(string $model, Query $query, string $field): int|float {
        return $this->getField($model, $query, "MAX", $field);
    }
}