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

    public function __construct(
        private string             $fieldName,
        private Column             $columnAttribute,
        private ReflectionProperty $property
    ) {
        $this->type = $property->getType();
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