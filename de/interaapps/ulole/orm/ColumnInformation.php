<?php

namespace de\interaapps\ulole\orm;

use de\interaapps\ulole\orm\attributes\Column;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

class ColumnInformation {
    private ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $type;

    private ?bool $isReference = null;

    public function __construct(
        private string             $fieldName,
        private Column             $columnAttribute,
        private ReflectionProperty $property
    ) {
        $this->type = $property->getType();
    }

    /**
     * @return bool|null
     */
    public function isReference(): ?bool
    {
        if ($this->isReference === null) {
            $type = $this->type?->getName();
            $this->isReference = $type !== null && class_exists($type) && in_array(ORMModel::class, class_uses($type));
        }
        return $this->isReference;
    }


    public function getFieldName(): string {
        return $this->fieldName;
    }

    public function getColumnAttribute(): Column {
        return $this->columnAttribute;
    }

    public function getProperty(): ReflectionProperty {
        return $this->property;
    }

    public function getType(): ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null {
        return $this->type;
    }
}