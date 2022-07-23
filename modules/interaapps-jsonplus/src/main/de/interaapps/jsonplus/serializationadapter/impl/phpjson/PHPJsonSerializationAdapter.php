<?php
namespace de\interaapps\jsonplus\serializationadapter\impl\phpjson;

use de\interaapps\jsonplus\serializationadapter\SerializationAdapter;

class PHPJsonSerializationAdapter implements SerializationAdapter {
    public function fromJson($json){
        return json_decode($json);
    }

    public function toJson($v, bool $prettyPrint) {
        if ($prettyPrint)
            return json_encode($v, JSON_PRETTY_PRINT);

        return json_encode($v);
    }
}