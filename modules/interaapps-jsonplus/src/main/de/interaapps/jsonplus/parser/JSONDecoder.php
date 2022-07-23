<?php
namespace de\interaapps\jsonplus\parser;


class JSONDecoder {
    private int $i = 0;
    public function __construct(
        private string $json
    ) {
    }

    public function jumpEmpties(){
        while ((trim($this->get()) == '' || $this->get()=="\n") && $this->i < strlen($this->json))
            $this->i++;
    }

    public function get($off = 0) : string {
        if (strlen($this->json) > $this->i+$off && $this->i+$off >= 0)
            return $this->json[$this->i+$off];
        return "";
    }

    public function readNext() : mixed {
        $this->jumpEmpties();

        if ($this->get() == '{')
            return $this->readObject();
        if ($this->get() == '[')
            return $this->readArray();
        if ($this->get() == '"')
            return $this->readString();

        return $this->readPrimitive();
    }
    public function readObject() : object|null {
        $o = (object)[];
        $this->i++;
        for (; $this->i<strlen($this->json); $this->i++) {
            $this->jumpEmpties();
            if ($this->get() == '}') {
                $this->i++;
                return $o;
            }

            $key = $this->readNext();
            $this->jumpEmpties();
            if ($this->get() == ":")
                $this->i++;
            $this->jumpEmpties();
            $value = $this->readNext();

            $o->{$key} = $value;

            $this->jumpEmpties();
            if ($this->get() == '}') {
                $this->i++;
                return $o;
            }

            if ($this->get(1) == ",")
                $this->i++;
        }

        return null;
    }
    public function readArray() : array {
        $this->i++;
        $a = [];
        for (; $this->i<strlen($this->json); $this->i++) {
            $this->jumpEmpties();
            if ($this->get() == ']') {
                $this->i++;
                return $a;
            }

            array_push($a, $this->readNext());

            $this->jumpEmpties();
            if ($this->get() == ']') {
                $this->i++;
                return $a;
            }

            if ($this->get(1) == ",")
                $this->i++;
        }
        return [];
    }

    public function readString() : string {
        $s = "";
        $this->i++;
        for (; $this->i<strlen($this->json); $this->i++) {
            $char = $this->get();

            foreach (['"'=>'"', "\\"=>"\\", "n"=>"\n", "r"=>"\r", "f"=>"\f", "t"=>"\t", "v"=>"\v", "/"=>"/"] as $k=>$v) {
                if ($char == "\\" && $this->get(1) == $k) {
                    $char = $v;
                    $this->i++;
                    break;
                }
            }

            if ($char == '"' && ($this->get(-1) != "\\" || $this->get(-2) == "\\")) {
                if ($this->get(-1) && $this->get(-2) != "\\")
                    $this->i++;
                return $s;
            }
            $s .= $char;
        }
        return "";
    }

    private function primitiveStringToPHP($p){
        $p = trim($p);
        if ($p == "false")
            return false;
        else if ($p == "true")
            return true;
        else if ($p == "null")
            return null;
        return (double) $p;
    }

    public function readPrimitive() : mixed {
        $p = "";
        for (; $this->i<strlen($this->json); $this->i++) {
            $char = $this->get();
            if ($char == '"' || $char == ',' || $char == '}' || $char == ']')
                return $this->primitiveStringToPHP($p);
            $p .= $char;
        }
        $this->i--;
        return $this->primitiveStringToPHP($p);
    }
}