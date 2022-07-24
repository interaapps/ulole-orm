<?php

namespace de\interaapps\ulole\orm;

use de\interaapps\ulole\orm\attributes\Column;
use de\interaapps\ulole\orm\attributes\Table;
use ReflectionClass;
use ReflectionException;

/**
 * @template T
 */
class ModelInformation {
    private string $identifier = "id";
    /**
     * @var ColumnInformation[]
     */
    private array $fields;
    private ?string $name;
    private bool $disableAutoMigrate = false;

    private const PHP_SQL_TYPES = [
        "int" => "INTEGER",
        "float" => "FLOAT",
        "string" => "TEXT",
        "bool" => "TINYINT"
    ];

    /**
     * @throws ReflectionException
     */
    public function __construct(
        private string $class
    ) {
        $reflection = new ReflectionClass($this->class);

        $tableAttributes = $reflection->getAttributes(Table::class);

        if (count($tableAttributes) > 0) {
            $tableAttribute = $tableAttributes[0]->newInstance();
            $this->name = $tableAttribute->value;
            $this->disableAutoMigrate = $tableAttribute->disableAutoMigrate;
        } else {
            $this->name = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $reflection->getShortName()));
            if (!str_ends_with($this->name, "s"))
                $this->name .= "s";
        }

        foreach ($reflection->getProperties() as $property) {
            if (!$property->isStatic()) {
                $columnAttributes = $property->getAttributes(Column::class);
                if (count($columnAttributes) > 0) {
                    $columnAttribute = $columnAttributes[0]->newInstance();
                    $this->fields[$property->getName()] = new ColumnInformation($columnAttribute->name ?? strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $property->getName())), $columnAttribute, $property);
                }
            }
        }
    }

    public function getName(): string {
        return $this->name;
    }

    public function getClass(): string {
        return $this->class;
    }

    /**
     * @return ColumnInformation[]
     */
    public function getFields(): array {
        return $this->fields;
    }

    public function setIdentifier(string $identifier): void {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): string {
        return $this->identifier;
    }

    public function setName(string $name): ModelInformation {
        $this->name = $name;
        return $this;
    }

    public function getFieldName(string $field): string {
        return $this->getColumnInformation($field)?->getFieldName() ?? $field;
    }

    public function getColumnInformation(string $name): ColumnInformation|null {
        return $this->fields[$name] ?? null;
    }

    public function getFieldValue($obj, string $field): string {


        return $obj->{$this->getFieldName($field)};
    }

    public function getIdentifierValue($obj): string {
        return $obj->{$this->getIdentifier()};
    }

    public function isAutoMigrateDisabled(): bool {
        return $this->disableAutoMigrate;
    }
    
    public function autoMigrate(array|null $databases = null) : ModelInformation {
        if ($databases === null)
            $databases = UloleORM::getDatabases();
        
        foreach ($databases as $database) {
            $tables = [];
            foreach ($database->query("SHOW TABLES;")->fetchAll() as $r) {
                $tables[] = $r[0];
            }

            $fields = $this->getFields();

            $columns = array_map(function ($field) {
                $type = $field->getColumnAttribute()->sqlType;
                if ($type == null) {
                    if (isset(self::PHP_SQL_TYPES[$field->getType()->getName()]))
                        $type = self::PHP_SQL_TYPES[$field->getType()->getName()];
                }

                if ($field->getColumnAttribute()->size != null)
                    $type .= "(" . $field->getColumnAttribute()->size . ")";

                $isIdentifier = $this->getIdentifier() == $field->getFieldName();

                return [
                    "field" => $field->getFieldName(),
                    "type" => $type,
                    "hasIndex" => $field->getColumnAttribute()->index,
                    "identifier" => $isIdentifier,
                    "query" => "`" . $field->getFieldName() . "` "
                        . $type
                        . ($field->getType()->allowsNull() ? '' : ' NOT NULL')
                        . ($type == 'INTEGER' && $isIdentifier ? ' AUTO_INCREMENT' : '')
                ];
            }, $fields);

            $indexes = array_filter($columns, fn($c) => $c["hasIndex"]);

            if (in_array($this->getName(), $tables)) {
                $existingFields = array_map(fn($f) => $f[0], $database->query('SHOW COLUMNS FROM ' . $this->getName() . ';')->fetchAll());
                $existingIndexes = array_map(fn($f) => $f[4], $database->query('SHOW INDEXES FROM ' . $this->getName() . ';')->fetchAll());

                $q = "ALTER TABLE `" . $this->getName() . "` ";
                $changes = [];
                foreach ($columns as $column) {
                    if (in_array($column['field'], $existingFields)) {
                        $changes[] = "MODIFY COLUMN " . $column["query"];
                    } else {
                        $changes[] = "ADD " . $column["query"];
                    }
                }
                foreach ($indexes as $index) {
                    $index = $index["field"];
                    if (!in_array($index, $existingIndexes))
                        $changes[] = 'ADD INDEX (`' . $index . '`);';
                }

                $q .= implode(", ", $changes) . ';';
                $database->query($q);
            } else {
                $q = "CREATE TABLE " . $this->getName() . " (" .
                    implode(', ', array_map(fn($c) => $c['query']
                            . ($c['identifier'] ? ' PRIMARY KEY' : ''
                            ), $columns)
                    );

                if (count($indexes) > 0) {
                    $q .= ", INDEX (" . implode(", ", array_map(fn($c) => $c["field"], $indexes)) . ")";
                }

                $q .= ");";
                $database->query($q);
            }
        }

        
        return $this;
    }
}