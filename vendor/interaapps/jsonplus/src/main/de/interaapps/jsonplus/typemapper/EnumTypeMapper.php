<?php

namespace de\interaapps\jsonplus\typemapper;

class EnumTypeMapper {

    public function map(\ReflectionClass $enum, string $name): mixed {
        foreach ($enum->getMethod("cases")->invoke(null) as $entry) {
            if ($entry->name == $name)
                return $entry;
        }

        return null;
    }

    public function mapToJson(\ReflectionClass $enum, mixed $type): mixed {
        return $type->name;
    }
}