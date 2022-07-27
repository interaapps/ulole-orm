<?php

namespace de\interaapps\ulole\orm;

use de\interaapps\ulole\orm\attributes\Column;
use de\interaapps\ulole\orm\attributes\CreatedAt;
use de\interaapps\ulole\orm\attributes\DeletedAt;
use de\interaapps\ulole\orm\attributes\Identifier;
use de\interaapps\ulole\orm\attributes\Table;
use de\interaapps\ulole\orm\attributes\UpdatedAt;
use de\interaapps\ulole\orm\migration\Blueprint;
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
    private ?string $name = null;
    private bool $disableAutoMigrate = false;

    private ?string $createdAt = null;
    private ?string $updatedAt = null;
    private ?string $deletedAt = null;

    private const PHP_SQL_TYPES = [
        "int" => "INTEGER",
        "float" => "FLOAT",
        "string" => "TEXT",
        "bool" => "BOOL"
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
            $this->name = $tableAttribute->name;
            $this->disableAutoMigrate = $tableAttribute->disableAutoMigrate;
        }
        if ($this->name === null) {
            $this->name = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $reflection->getShortName()));
            if (!str_ends_with($this->name, "s"))
                $this->name .= "s";
        }

        foreach ($reflection->getProperties() as $property) {
            if (!$property->isStatic()) {
                $columnAttributes = $property->getAttributes(Column::class);
                if (count($columnAttributes) > 0) {
                    $columnAttribute = $columnAttributes[0]->newInstance();
                    $columnInfo = new ColumnInformation($columnAttribute->name ?? strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $property->getName())), $columnAttribute, $property);

                    $this->fields[$property->getName()] = $columnInfo;

                    if ($columnAttribute->id)
                        $this->identifier = $property->getName();

                    if (count($property->getAttributes(CreatedAt::class)))
                        $this->createdAt = $property->getName();

                    if (count($property->getAttributes(UpdatedAt::class)))
                        $this->updatedAt = $property->getName();

                    if (count($property->getAttributes(DeletedAt::class)))
                        $this->deletedAt = $property->getName();
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

    public function autoMigrate(array|null $databases = null): ModelInformation {
        if ($databases === null)
            $databases = UloleORM::getDatabases();

        foreach ($databases as $database) {
            $tables = $database->getDriver()->getTables();

            $fields = $this->getFields();

            $columns = array_map(function ($field) {
                $type = $field->getColumnAttribute()->sqlType;
                if ($type == null) {
                    if (isset(self::PHP_SQL_TYPES[$field->getType()->getName()])) {
                        $type = self::PHP_SQL_TYPES[$field->getType()->getName()];
                        if ($type == "TEXT" && $field->getColumnAttribute()->size !== null)
                            $type = "VARCHAR";
                    }
                }

                $isIdentifier = $this->getIdentifier() == $field->getFieldName();

                return [
                    "field" => $field->getFieldName(),
                    "type" => $type,
                    "hasIndex" => $field->getColumnAttribute()->index,
                    "identifier" => $isIdentifier,
                    "blueprintHandler" => function (Blueprint $blueprint) use ($isIdentifier, $type, $field) {
                        $col = $blueprint->custom($field->getFieldName(), $type, $field->getColumnAttribute()->size);

                        if ($type == "INTEGER" && $isIdentifier)
                            $col->ai()->primary();

                        if ($field->getColumnAttribute()->unique)
                            $col->unqiue();

                        $col->nullable($field->getType()->allowsNull());
                    }
                ];
            }, $fields);

            if (in_array($this->getName(), $tables)) {
                $database->edit($this->getName(), function (Blueprint $blueprint) use ($columns) {
                    foreach ($columns as $column)
                        $column["blueprintHandler"]($blueprint);
                });
            } else {
                $database->create($this->getName(), function (Blueprint $blueprint) use ($columns) {
                    foreach ($columns as $column)
                        $column["blueprintHandler"]($blueprint);
                });
            }
        }


        return $this;
    }

    public function getCreatedAt(): ?string {
        return $this->createdAt;
    }

    public function getDeletedAt(): ?string {
        return $this->deletedAt;
    }

    public function getUpdatedAt(): ?string {
        return $this->updatedAt;
    }
}