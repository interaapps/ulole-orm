<?php

namespace de\interaapps\ulole\orm\attributes;

use Attribute;

#[Attribute]
class Table {
    public function __construct(
        /** Name */
        public string|null $name = null,
        public bool        $disableAutoMigrate = false
    ) {
    }
}