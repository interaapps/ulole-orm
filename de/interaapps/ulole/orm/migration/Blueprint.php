<?php

namespace de\interaapps\ulole\orm\migration;

class Blueprint {
    /**
     * @var Column[]
     */
    private array $columns;

    public function __construct() {
        $this->columns = [];
    }

    public function custom(string $name, string $type, mixed $size = null): Column {
        $column = new Column($name, $type, $size);
        $this->columns[] = $column;
        return $column;
    }

    public function id($name = "id"): Column {
        $column = $this->int($name)
            ->ai();
        $this->columns[] = $column;
        return $column;
    }

    public function string($name, $size = null): Column {
        $column = $this->custom($name, $size === null ? "TEXT" : "VARCHAR");
        $this->columns[] = $column;
        return $column;
    }

    public function int($name, $size = null): Column {
        $column = $this->custom($name, "INTEGER", $size);
        $this->columns[] = $column;
        return $column;
    }

    public function text($name): Column {
        $column = $this->custom($name, "TEXT");
        $this->columns[] = $column;
        return $column;
    }

    public function varChar($name, $size = null): Column {
        $column = $this->custom($name, "VARCHAR", $size);
        $this->columns[] = $column;
        return $column;
    }

    public function tinyInt($name): Column {
        return $this->custom($name, "TINYINT");
    }

    public function bigInt($name): Column {
        return $this->custom($name, "BIGINT");
    }

    public function double($name): Column {
        return $this->custom($name, "DOUBLE");
    }

    public function bit($name): Column {
        return $this->custom($name, "BIT");
    }

    public function float($name): Column {
        return $this->custom($name, "FLOAT");
    }

    public function mediumInt($name): Column {
        return $this->custom($name, "MEDIUMINT");
    }

    public function longText($name): Column {
        return $this->custom($name, "LONGTEXT");
    }

    public function tinyText($name): Column {
        return $this->custom($name, "TINYTEXT");
    }

    public function date($name): Column {
        return $this->custom($name, "DATE");
    }

    public function datetime($name): Column {
        return $this->custom($name, "DATETIME");
    }

    public function year($name): Column {
        return $this->custom($name, "YEAR");
    }

    public function enum($name, array $enum): Column {
        $column = $this->custom($name, "ENUM", $enum);
        $this->columns[] = $column;
        return $column;
    }

    public function set($name, array $set): Column {
        $column = $this->custom($name, "SET", $set);
        $this->columns[] = $column;
        return $column;
    }

    public function timestamp($name): Column {
        $column = $this->custom($name, "TIMESTAMP");
        $this->columns[] = $column;
        return $column;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array {
        return $this->columns;
    }

    /**
     * @return Column[]
     */
    public function getIndexes(): array {
        return array_filter($this->columns, fn(Column $col) => $col->isIndex());
    }
}