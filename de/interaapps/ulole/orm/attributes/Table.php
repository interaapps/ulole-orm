<?php

namespace de\interaapps\ulole\orm\attributes;

use Attribute;

#[Attribute]
class Table {
    public function __construct(
        /** Name */
        public string $value,
        public bool $disableAutoMigrate = false
    ) {
    }
}