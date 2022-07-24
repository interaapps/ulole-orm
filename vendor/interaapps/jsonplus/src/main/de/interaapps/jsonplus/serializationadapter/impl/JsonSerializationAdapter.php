<?php
namespace de\interaapps\jsonplus\serializationadapter\impl;

use de\interaapps\jsonplus\parser\JSONDecoder;
use de\interaapps\jsonplus\parser\JSONEncoder;
use de\interaapps\jsonplus\serializationadapter\SerializationAdapter;


class JsonSerializationAdapter implements SerializationAdapter {
    public function fromJson($json){
        return (new JSONDecoder($json))->readNext();
    }

    public function toJson($v, bool $prettyPrint) {
        return (new JSONEncoder())
            ->setPrettyPrint($prettyPrint)
            ->encode($v, "");
    }
}