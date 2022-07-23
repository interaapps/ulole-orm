<?php

namespace de\interaapps\jsonplus\attributes;

use Attribute;

#[Attribute]
class Serialize {
    public function __construct(
        public ?string $value = null,
        public bool $hidden = false
    ){
    }
}