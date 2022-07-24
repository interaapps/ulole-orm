<?php
namespace de\interaapps\jsonplus\typemapper;


use de\interaapps\jsonplus\JSONPlus;

class ArrayTypeMapper implements TypeMapper {
    public function __construct(
        private JSONPlus $jsonPlus
    ){
    }

    public function map(mixed $o, string $type, $entriesType = null): mixed {
        if ($entriesType !== null) {
            return $this->jsonPlus->mapTypedArray($o, $entriesType);
        } else
            return $o;
    }

    public function mapToJson(mixed $o, string $type): mixed {
        $out = [];
        foreach ($o as $i=>$val) {
            $out[$i] = $this->jsonPlus->mapToJson($val);
        }
        return $out;
    }
}