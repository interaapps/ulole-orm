<?php

namespace de\interaapps\ulole\orm\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column {
    public function __construct(
        public ?string         $name = null,
        public ?string         $sqlType = null,
        public string|int|null $size = null,
        public bool            $index = false,
        public bool            $id = false,
        public bool            $unique = false,
        public bool            $fetch = true,
    ) {
    }
}