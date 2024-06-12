<?php

namespace de\interaapps\ulole\orm\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * @template T
 */
class HasMany {
    /**
     * @param class-string<T> $class
     */
    public function __construct(
        public string $class,
        public string $fieldId,
        public bool $fetch = true,
    ) {
    }
}