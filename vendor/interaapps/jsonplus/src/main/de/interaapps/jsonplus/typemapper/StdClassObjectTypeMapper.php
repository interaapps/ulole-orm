<?php
namespace de\interaapps\jsonplus\typemapper;


use de\interaapps\jsonplus\attributes\Serialize;
use de\interaapps\jsonplus\JSONPlus;
use ReflectionClass;

class StdClassObjectTypeMapper implements TypeMapper {
    public function __construct(
        private JSONPlus $jsonPlus
    ){
    }

    public function map(mixed $o, string $type): mixed {
        /* Implement if CASING is implemented
        $oo = [];

        foreach ($o as $k=>$v) {
            echo $k;
            $oo[$k] = $v;
        }*/

        return (object) $o;
    }

    public function mapToJson(mixed $o, string $type): mixed {
        return (object) $o;
    }
}