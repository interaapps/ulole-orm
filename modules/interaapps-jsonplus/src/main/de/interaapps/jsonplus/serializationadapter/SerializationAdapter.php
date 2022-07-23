<?php
namespace de\interaapps\jsonplus\serializationadapter;

interface SerializationAdapter {
    public function fromJson($json);
    public function toJson($v, bool $prettyPrint);
}