<?php
namespace de\interaapps\jsonplus;

use de\interaapps\jsonplus\serializationadapter\impl\JsonSerializationAdapter;
use de\interaapps\jsonplus\serializationadapter\impl\phpjson\PHPJsonSerializationAdapter;
use de\interaapps\jsonplus\serializationadapter\SerializationAdapter;
use de\interaapps\jsonplus\typemapper\ArrayTypeMapper;
use de\interaapps\jsonplus\typemapper\EnumTypeMapper;
use de\interaapps\jsonplus\typemapper\ObjectTypeMapper;
use de\interaapps\jsonplus\typemapper\PassThroughTypeMapper;
use de\interaapps\jsonplus\typemapper\StdClassObjectTypeMapper;
use de\interaapps\jsonplus\typemapper\TypeMapper;
use ReflectionClass;

class JSONPlus {
    private bool $prettyPrinting = false;
    private array $typeMapper = [];
    private TypeMapper $defaultTypeMapper;
    private TypeMapper $passThroughTypeMapper;
    public static JSONPlus $default;

    public function __construct(
        private SerializationAdapter $serializationAdapter
    ){
        $this->defaultTypeMapper = new ObjectTypeMapper($this, new EnumTypeMapper());
        $this->passThroughTypeMapper = new PassThroughTypeMapper();
        $this->typeMapper = [
            "object" => $this->passThroughTypeMapper,
            "string" => $this->passThroughTypeMapper,
            "float" => $this->passThroughTypeMapper,
            "int" => $this->passThroughTypeMapper,
            "integer" => $this->passThroughTypeMapper,
            "double" => $this->passThroughTypeMapper,
            "bool" => $this->passThroughTypeMapper,
            "array" => new ArrayTypeMapper($this),
            "boolean" => $this->passThroughTypeMapper,
            "NULL" => $this->passThroughTypeMapper,
            "stdClass" => new StdClassObjectTypeMapper($this),
        ];
    }

    /**
     * @template T
     * @param string $json The input json
     * @param null|class-string<T> $type A class (className::class), type (example: "array", "int"...) or null (Detects type automatically)
     * @return T
     * */
    public function fromJson(string $json, string|null $type = null){
        return $this->map($this->serializationAdapter->fromJson($json), $type);
    }

    /**
     * @template T
     * @param string $json The input json
     * @param class-string<T> $type A class (className::class), type (example: "array", "int"...) or null (Detects type automatically)
     * @return array<T>
     * */
    public function fromMappedArrayJson(string $json, string $type) : array {
        return $this->mapTypedArray($this->serializationAdapter->fromJson($json), $type);
    }
    /**
     * @template T
     * @param array $arr
     * @param class-string<T> $type A class (className::class), type (example: "array", "int"...) or null (Detects type automatically)
     * @return array<T>
     * */
    public function mapTypedArray(array $arr, string $type) : array {
        $out = [];
        foreach ($arr as $i=>$v)
            $out[$i] = $this->map($v, $type);
        return $out;
    }

    /**
     * @template T
     * @param $o
     * @param null|class-string<T> $type $type A class (className::class), type (example: "array", "int"...) or null (Detects type automatically)
     * @return T
     * */
    public function map($o, null|string $type = null){
        if ($type == null) {
            $type = gettype($o);

            if ($type == "object")
                $type = get_class($o);
        }

        foreach ($this->typeMapper as $typeName => $typeMapper) {
            if ($type == $typeName)
                return $typeMapper->map($o, $type);
        }
        return $this->defaultTypeMapper->map($o, $type);
    }

    public function toJson($o, $type = null) : string {
        return $this->serializationAdapter->toJson($this->mapToJson($o, $type), $this->prettyPrinting);
    }

    public function mapToJson($o, $type = null){
        if ($type == null) {
            $type = gettype($o);
            if ($type == "object")
                $type = get_class($o);
        }
        foreach ($this->typeMapper as $typeName => $typeMapper) {
            if ($type == $typeName)
                return $typeMapper->mapToJson($o, $type);
        }
        return $this->defaultTypeMapper->mapToJson($o, $type);
    }

    public function getSerializationAdapter(): SerializationAdapter {
        return $this->serializationAdapter;
    }

    public function setPrettyPrinting(bool $prettyPrinting): JSONPlus {
        $this->prettyPrinting = $prettyPrinting;
        return $this;
    }

    public static function createDefault() : JSONPlus {
        return new JSONPlus(function_exists("json_decode") ? new PHPJsonSerializationAdapter() : new JsonSerializationAdapter());
    }
}
JSONPlus::$default = JSONPlus::createDefault();