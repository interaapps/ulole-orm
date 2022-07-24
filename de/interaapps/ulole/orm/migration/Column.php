<?php

namespace de\interaapps\ulole\orm\migration;

class Column {
    private string|null $name;
    private string $type;
    private bool $ai = false;
    private bool $unique = false;
    private bool $primary = false;
    private string|int|null $size; // Can also be value
    private mixed $default;
    private bool $nullable = true;
    private string|null $renameTo = null;
    private bool $drop = false;

    private bool $defaultDefined = false;
    private bool $escapeDefault = true;

    public function __construct($name, $type, $size = null) {
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

    public function currentTimestamp() {
        return $this->default("CURRENT_TIMESTAMP", false);
    }

    public function ai($ai = true) {
        $this->ai = $ai;
        $this->primary = true;
        return $this;
    }

    public function unqiue($unique = true) {
        $this->unique = $unique;
        return $this;
    }

    public function rename($rename) {
        $this->renameTo = $rename;
        return $this;
    }

    public function drop() {
        $this->drop = true;
    }

    public function primary($primary = true) {
        $this->primary = $primary;
        return $this;
    }

    public function default($value, $escape = true) {
        $this->defaultDefined = true;
        $this->default = $value;
        $this->escapeDefault = $escape;
        return $this;
    }

    public function nullable(bool $nullable = true): Column {
        $this->nullable = $nullable;
        return $this;
    }

    public function generateStructure(): string {
        if ($this->drop)
            return "DROP COLUMN `" . $this->name . "`";
        $structure = "`"
            . ($this->renameTo === null ? $this->name : $this->renameTo)
            . "` "
            . $this->type;
        if ($this->size !== null)
            $structure .= "(" . $this->size . ")";
        $structure .= " ";
        if ($this->defaultDefined) {
            $structure .= "DEFAULT ";
            if ($this->default === null) {
                $structure .= "NULL";
            } else if (!$this->escapeDefault) {
                $structure .= $this->default;
            } else {
                $structure .= "'" . addslashes($this->default) . "'";
            }
            $structure .= " ";
        }
        $structure .= $this->nullable ? "NULL " : "NOT NULL ";
        if ($this->ai)
            $structure .= "AUTO_INCREMENT";
        return $structure;
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