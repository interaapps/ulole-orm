<?php
namespace de\interaapps\ulole\orm\migration;

class Column {
    public $name,
           $type,
           $ai,
           $unique,
           $primary,
           $size, // Can also be value
           $default,
           $nullable = false,
           $renameTo = null,
           $drop = false;

    private $defaultDefined = false,
            $escapeDefault = true;

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
                $this->size .= "'".$key."'";
            }
        }
    }

    public function currentTimestamp(){
        return $this->default("CURRENT_TIMESTAMP", false);
    }

    public function ai($ai = true){
        $this->ai = $ai;
        $this->primary = true;
        return $this;
    }

    public function unqiue($unique = true){
        $this->unique = $unique;
        return $this;
    }

    public function rename($rename){
        $this->renameTo = $rename;
        return $this;
    }

    public function drop(){
        $this->drop = true;
    }

    public function primary($primary = true){
        $this->primary = $primary;
        return $this;
    }

    public function default($value, $escape=true){
        $this->defaultDefined = true;
        $this->default = $value;
        $this->escapeDefault = $escape;
        return $this;
    }

    public function nullable(bool $nullable = true) : Column {
        $this->nullable = $nullable;
        return $this;
    }

    public function generateStructure() : string {
        if ($this->drop)
            return "DROP COLUMN `".$this->name."`";
        $structure = "`"
                        .($this->renameTo === null ? $this->name : $this->renameTo)
                        ."` "
                        .$this->type;
        if ($this->size !== null)
            $structure .= "(".$this->size.")";
        $structure .= " ";
        if ($this->defaultDefined) {
            $structure .= "DEFAULT ";
            if ($this->default === null) {
                $structure .= "NULL";
            } else if (!$this->escapeDefault) {
                $structure .= $this->default;
            } else {
                $structure .= "'".addslashes($this->default)."'";
            }
            $structure .= " ";
        }
        $structure .= $this->nullable ? "NULL " : "NOT NULL ";
        if ($this->ai)
            $structure .= "AUTO_INCREMENT";
        return $structure;
    }
}