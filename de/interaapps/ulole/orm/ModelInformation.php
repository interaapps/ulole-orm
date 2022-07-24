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
}