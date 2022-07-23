<?php
namespace de\interaapps\ulole\orm\attributes;

use Attribute;

#[Attribute]
class Column {
    public function __construct(
        public ?string $name = null,
        public ?string $sqlType = null,
        public bool $index = false,
        public string|int|null $size = null
    )
    {
    }
}