<?php
namespace de\interaapps\jsonplus\typemapper;


use de\interaapps\jsonplus\attributes\ArrayType;
use de\interaapps\jsonplus\attributes\Serialize;
use de\interaapps\jsonplus\JSONPlus;
use ReflectionClass;

class ObjectTypeMapper implements TypeMapper {
    public function __construct(
        private JSONPlus $jsonPlus,
        private EnumTypeMapper $enumTypeMapper
    ){
    }

    public function map(mixed $o, string $type): mixed {
        if ($o === null)
            return null;

        $class = new ReflectionClass(str_replace("?", "", $type));
        if ($class->isEnum()) {
            return $this->enumTypeMapper->map($class, $o);
        }

        $oo = $class->newInstance();

        foreach ($class->getProperties() as $property) {
            if (!$property->isStatic()) {
                $name = $property?->getName();
                $serializeAttribs = $property->getAttributes(Serialize::class);
                foreach ($serializeAttribs as $attrib) {
                    $attrib = $attrib->newInstance();
                    $name = $attrib->value;
                    if ($attrib->hidden)
                        continue 2;
                }

                if ($o != null && isset($o->{$name})) {
                    // Mapping Array if #[ArrayType] is given
                    if (is_array($o->{$name})) {
                        $arrayTypeAttribs = $property->getAttributes(ArrayType::class);
                        foreach ($arrayTypeAttribs as $attrib) {
                            $property->setValue($oo, $this->jsonPlus->mapTypedArray($o->{$name}, $attrib->newInstance()->value));
                            continue 2;
                        }
                    }

                    $property->setValue($oo, $this->jsonPlus->map($o?->{$name}, strval($property->getType())));
                }
            }
        }

        return $oo;
    }

    public function mapToJson(mixed $o, string $type): mixed {
        if ($o === null)
            return null;
        $class = new ReflectionClass(str_replace("?", "", $type));

        if ($class->isEnum()) {
            return $this->enumTypeMapper->mapToJson($class, $o);
        }

        $oo = [];
        foreach ($class->getProperties() as $property) {
            if (!$property->isStatic()) {
                $name = $property?->getName();

                $overrideName = $property?->getName();
                $serializeAttribs = $property->getAttributes(Serialize::class);

                foreach ($serializeAttribs as $attrib) {
                    $attrib = $attrib->newInstance();
                    $overrideName = $attrib->value;
                    if ($attrib->hidden)
                        continue 2;
                }


                if ($o !== null && isset($o->{$name})) {
                    // Mapping Array if #[ArrayType] is given
                    if (is_array($o->{$name})) {
                        $outArr = [];
                        foreach ($o->{$name} as $i=>$entry)
                            $outArr[$i] = $this->jsonPlus->mapToJson($entry);
                        $oo[$overrideName] = $outArr;
                        continue;
                    }

                    // Mapping Type
                    if (gettype($o?->{$name}) == "object") {
                        $class = get_class($o?->{$name});
                        if ($class != "stdClass") {
                            $oo[$overrideName] = $this->mapToJson($o?->{$name}, $class);
                            continue;
                        }
                    }
                    $oo[$overrideName] = $o?->{$name};
                }
            }
        }
        return (object) $oo;
    }
}