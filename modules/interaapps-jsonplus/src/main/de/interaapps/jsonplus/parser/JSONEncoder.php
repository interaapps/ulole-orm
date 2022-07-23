<?php


namespace de\interaapps\jsonplus\parser;


class JSONEncoder {
    private bool $prettyPrint = false;
    public function encode($v, $tabs="") : string {
        $this->prettyPrintNewLine = $this->prettyPrint ? "\n": '';
        $identedTabs = $this->prettyPrint ? $tabs."    " : '';

        if (!isset($v)) {
            return "null";
        } if (is_bool($v)) {
            return $v ? "true" : "false";
        } else if (is_int($v) || is_bool($v) || is_double($v)) {
            return strval($v);
        } else if (is_array($v)) {
            if (count($v) == 0)
                return "[]";
            return '['.$this->prettyPrintNewLine.implode(", ".$this->prettyPrintNewLine, array_map(fn($k)=>$identedTabs.$this->encode($k, $identedTabs), $v)).$this->prettyPrintNewLine.$tabs.']';
        } else if (is_string($v)) {
            return  '"'.$this->escapeString($v).'"';
        } else if (is_object($v)) {
            $v = (array) $v;
            if (count($v) == 0)
                return "{}";
            return '{'.$this->prettyPrintNewLine.implode(", ".$this->prettyPrintNewLine, array_map(fn($k, $val)=>$identedTabs.$this->encode($k, $identedTabs).': '.$this->encode($val, $identedTabs), array_keys($v), array_values($v))).$this->prettyPrintNewLine.$tabs.'}';
        }
        return "";
    }

    private function escapeString($str) : string {
        return
            str_replace("\n", "\\n",
            str_replace("\r", "\\r",
            str_replace("\t", "\\t",
            str_replace("/", "\\/",
            str_replace("\f", "\\f",
            str_replace("\v", "\\v",
                str_replace('"', '\"',
                    str_replace("\\","\\\\", $str))))))));
    }

    public function setPrettyPrint(bool $prettyPrint): JSONEncoder {
        $this->prettyPrint = $prettyPrint;
        return $this;
    }
}