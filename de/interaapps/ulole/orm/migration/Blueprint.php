<?php
namespace de\interaapps\ulole\orm\migration;

class Blueprint {
    private array $columns;

    public function __construct() {
        $this->columns = [];
    }

    public function custom($name, $type, $size=null) : Column {
        $column = new Column($name, $type, $size);
        $this->columns[] = $column;
        return $column;
    }

    public function id($name = "id") : Column {
        $column = (new Column($name, "INT"))
            ->ai();
        $this->columns[] = $column;
        return $column;
    }

    public function string($name, $size = null) : Column {
        $column = new Column($name, $size === null ? "TEXT" : "VARCHAR");
        $this->columns[] = $column;
        return $column;
    }

    public function int($name, $size = null) : Column {
        $column = new Column($name, "INT", $size);
        $this->columns[] = $column;
        return $column;
    }

    public function text($name) : Column {
        $column = new Column($name, "TEXT");
        $this->columns[] = $column;
        return $column;
    }

    public function varChar($name, $size = null) : Column {
        $column = new Column($name, "VARCHAR", $size);
        $this->columns[] = $column;
        return $column;
    }
    
    public function tinyInt($name) : Column {
        return $this->custom($name, "TINYINT");
    }

    public function bigInt($name) : Column {
        return $this->custom($name, "BIGINT");
    }

    public function double($name) : Column {
        return $this->custom($name, "DOUBLE");
    }

    public function bit($name) : Column {
        return $this->custom($name, "BIT");
    }

    public function float($name) : Column {
        return $this->custom($name, "FLOAT");
    }

    public function mediumInt($name) : Column {
        return $this->custom($name, "MEDIUMINT");
    }

    public function longText($name) : Column {
        return $this->custom($name, "LONGTEXT");
    }

    public function tinyText($name) : Column {
        return $this->custom($name, "TINYTEXT");
    }

    public function date($name) : Column {
        return $this->custom($name, "DATE");
    }

    public function datetime($name) : Column {
        return $this->custom($name, "DATETIME");
    }

    public function year($name) : Column {
        return $this->custom($name, "YEAR");
    }

    public function enum($name, array $set) : Column {
        $column = new Column($name, "ENUM", $set);
        $this->columns[] = $column;
        return $column;
    }

    public function set($name, array $set) : Column {
        $column = new Column($name, "ENUM", $set);
        $this->columns[] = $column;
        return $column;
    }

    public function timestamp($name) : Column {
        $column = new Column($name, "TIMESTAMP");
        $this->columns[] = $column;
        return $column;
    }

    public function getColumns() : array {
        return $this->columns;
    }

    public function getQueries($addKeys = false) : array {
        $columns = [];
        $primaryKeys = [];
        foreach ($this->columns as $column) {
            $columns[$column->name] = $column->generateStructure();
            if ($column->primary)
                array_push($primaryKeys, "`".($column->renameTo === null ?$column->name : $column->renameTo)."`");
        }
        if ($addKeys)
            $columns[":primary_key"] = "PRIMARY KEY (". implode(",", $primaryKeys) .")";

        return $columns;
    }
}