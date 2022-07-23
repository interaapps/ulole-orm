<?php
namespace de\interaapps\jsonplus\typemapper;


class PassThroughTypeMapper implements TypeMapper {
    public function map(mixed $o, string $type): mixed {
        return $o;
    }

    public function mapToJson(mixed $o, string $type): mixed {
        return $o;
    }
}