<?php
namespace de\interaapps\jsonplus\typemapper;

interface TypeMapper {
    /**
     * @template T
     * @param mixed $o
     * @param class-string<T> $type
     * @return T
     * */
    public function map(mixed $o, string $type) : mixed;

    public function mapToJson(mixed $o, string $type) : mixed;
}