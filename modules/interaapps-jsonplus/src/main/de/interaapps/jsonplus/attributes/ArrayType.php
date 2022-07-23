<?php

namespace de\interaapps\jsonplus\attributes;

use Attribute;

#[Attribute]
class ArrayType {
    /**
     * @param class-string $value
     */
    public function __construct(
        public string $value
    ){
    }

}