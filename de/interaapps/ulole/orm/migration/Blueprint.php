<?php
namespace de\interaapps\ulole\orm\migration;

class Blueprint {
    private $columns;

    public function __construct() {
        $this->columns = [];
    }

    public function id($name = "id") : Column {
        $column = (new Column($name, "INT"))
            ->ai();
        array_push($this->columns, $column);
        return $column;
    }

    public function string($name, $size = null) : Column {
        $column = new Column($name, $size === null ? "TEXT" : "VARCHAR");
        array_push($this->columns, $column);
        return $column;
    }

    public function int($name, $size = null) : Column {
        $column = new Column($name, "INT", $size);
        array_push($this->columns, $column);
        return $column;
    }

    public function enum($name, array $set) : Column {
        $column = new Column($name, "ENUM", $set);
        array_push($this->columns, $column);
        return $column;
    }

    public function timestamp($name) : Column {
        $column = new Column($name, "TIMESTAMP");
        array_push($this->columns, $column);
        return $column;
    }

    public function getColumns() {
        return $this->columns;
    }

    public function getQueries($addKeys = false){
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