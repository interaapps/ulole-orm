<?php

namespace de\interaapps\ulole\orm\migration;

use de\interaapps\ulole\orm\drivers\Driver;

class Column {
    private string|null $name;
    private string $type;
    private bool $ai = false;
    private bool $unique = false;
    private bool $primary = false;
    private mixed $size; // Can also be value
    private mixed $default;
    private bool $nullable = true;
    private bool $index = false;
    private string|null $renameTo = null;
    private bool $drop = false;

    private bool $defaultDefined = false;
    private bool $escapeDefault = true;

    public function __construct(string $name, string $type, mixed $size = null) {
        $this->name = $name;
        $this->type = $type;

        $this->size = $size;

        if (($type == 'ENUM' || $type == 'SET')) {
            // if (!is_array($size))

            $comma = false;
            $this->size = "";
            foreach ($size as $key) {
                if ($comma)
                    $this->size .= ", ";
                else
                    $comma = true;
                $this->size .= "'" . $key . "'";
            }
        }
    }

    public function currentTimestamp(): Column {
        return $this->default("CURRENT_TIMESTAMP", false);
    }

    public function ai($ai = true): Column {
        $this->ai = $ai;
        $this->primary = true;
        return $this;
    }

    public function unique($unique = true): Column {
        $this->unique = $unique;
        return $this;
    }

    public function index($index = true): Column {
        $this->index = $index;
        return $this;
    }

    public function setType(string $type): void {
        $this->type = $type;
    }

    public function rename($rename): Column {
        $this->renameTo = $rename;
        return $this;
    }

    public function drop(): Column {
        $this->drop = true;
        return $this;
    }

    public function primary($primary = true): Column {
        $this->primary = $primary;
        return $this;
    }

    public function default($value, $escape = true): Column {
        $this->defaultDefined = true;
        $this->default = $value;
        $this->escapeDefault = $escape;
        return $this;
    }

    public function nullable(bool $nullable = true): Column {
        $this->nullable = $nullable;
        return $this;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function isPrimary(): bool {
        return $this->primary;
    }

    public function isAi(): bool {
        return $this->ai;
    }

    public function isDefaultDefined(): bool {
        return $this->defaultDefined;
    }

    public function isDrop(): bool {
        return $this->drop;
    }

    public function isEscapeDefault(): bool {
        return $this->escapeDefault;
    }

    public function isNullable(): bool {
        return $this->nullable;
    }

    public function isUnique(): bool {
        return $this->unique;
    }

    public function isIndex(): bool {
        return $this->index;
    }

    public function getDefault(): mixed {
        return $this->default;
    }

    public function getRenameTo(): ?string {
        return $this->renameTo;
    }

    public function getSize(): mixed {
        return $this->size;
    }

    public function getType(): string {
        return $this->type;
    }
}