<?php
namespace de\interaapps\ulole\orm;

use PDOStatement;

class Query {
    private $queries = [];
    private $model;
    private $database;
    private $limit = null;
    private $offset = null;
    private $orderBy = null;
    protected $temporaryQuery = false;

    public function __construct($database, $model) {
        $this->database = $database;
        $this->model    = $model;
    }

    public function where($var1, $var2, $var3 = null) : Query {        
        $field = $var1;
        $operator = $var2;
        $value = $var2;
        if ($var3 === null) {
            $operator = '=';
        } else
            $value = $var3;

        array_push($this->queries, ['type'=>'AND', 'query'=>'`'.$field.'` '.$operator.' ?', 'val' => $value]);
        return $this;
    }

    public function orWhere($var1, $var2, $var3 = null){
        return $this->or(function($query) use ($var1, $var2, $var3) {
            $query->where($var1, $var2, $var3);
        });
    }

    public function like($field, $like){
        return $this->where($field, "LIKE", $like);
    }

    public function or(Callable $callable) : Query{
        $query = new Query($this->database, $this->model);
        $query->temporaryQuery = true;
        $callable($query);
        array_push($this->queries, ['type'=>'OR', 'queries' => $query]);
        return $this;
    }

    public function and(Callable $callable) : Query{
        $query = new Query($this->database, $this->model);
        $query->temporaryQuery = true;
        $callable($query);
        array_push($this->queries, ['type'=>'AND', 'queries' => $query]);
        return $this;
    }

    public function set($field, $value) : Query {
        array_push($this->queries, ['type'=>'SET', 'query'=>'`'.$field.'` = ?', 'val' => $value]);
        return $this;
    }

    public function get() {
        $vars = [];
        if ($this->limit === null)
            $this->limit(1);
        $query = $this->buildQuery();
        $vars = array_merge($vars, $query->vars);
        $statement = $this->run('SELECT * FROM '.UloleORM::getTableName($this->model).$query->query.';', $vars);
        $result = $statement->fetch();
        if ($result !== false)
            $result->ormInternals_setEntryExists();

        return $result === false ? null : $result ;
    }

    public function all() {
        $vars = [];
        $query = $this->buildQuery();
        $vars = array_merge($vars, $query->vars);
        
        $statement = $this->run('SELECT * FROM '.UloleORM::getTableName($this->model).$query->query.';', $vars);
        $result = $statement->fetchAll();
        if (is_array($result)) {
            foreach ($result as $entry){
                $entry->ormInternals_setEntryExists();
            }
        }
        return $result;
    }

    public function count() {
        $vars = [];
        $query = $this->buildQuery();
        $vars = array_merge($vars, $query->vars);
        $statement = $this->run('SELECT COUNT(*) as count FROM '.UloleORM::getTableName($this->model).$query->query.';', $vars);
        $statement->setFetchMode(\PDO::FETCH_NUM);
        $result = $statement->fetch();
        if ($result === false)
            return 0;

        return $result[0];
    }

    public function update(){
        $vars = [];
        $query = $this->buildQuery();
        $vars = array_merge($vars, $query->vars);
        
        return $this->run('UPDATE '.UloleORM::getTableName($this->model).$query->query.';', $vars, true);
    }

    public function delete(){
        $vars = [];
        $query = $this->buildQuery();
        $vars = array_merge($vars, $query->vars);
        
        return $this->run('DELETE FROM '.UloleORM::getTableName($this->model).$query->query.';', $vars, true);
    }

    public function run($query, $vars = [], $returnResult = false) {
        echo $query."\n";

        $statement = $this->database->getConnection()->prepare($query);
        $result = $statement->execute($vars);
        if ($returnResult)
            return $result;
        if ($result === false)
            return null;
        $statement->setFetchMode(\PDO::FETCH_CLASS, $this->model);
        return $statement;
    }

    protected function buildQuery(){
        $out = (object) ["query" => '', 'vars' => []];


        $usedWhere = $this->temporaryQuery;
        $usedSet = false;
        $useCondition = false;
        foreach ($this->queries as $query) {
            if ($query["type"] == 'AND' || $query["type"] == 'OR'){
                if (!$usedWhere) {
                    $out->query .= " WHERE ";
                    $usedWhere = true;
                }
                if ($useCondition) {
                    $out->query .= $query["type"];
                } else
                    $useCondition = true;
                if (isset($query["query"])) {
                    $out->query .= ' '.$query["query"].' ';
                    array_push($out->vars, $query['val']);
                } else if (isset($query["queries"])) {
                    $out->query .= ' (';
                    $where = ($query["queries"])->buildQuery();
                    $out->vars = array_merge($out->vars, $where->vars);
                    $out->query .= $where->query;
                    $out->query .= ') ';
                } 
            } else if($query["type"] == 'SET') {
                if (!$usedSet) {
                    $out->query .= " SET ";
                    $usedSet = true;
                } else
                    $out->query .= ",";
                $out->query .= ' '.$query["query"].' ';
                array_push($out->vars, $query['val']);
            }
        }

        if ($this->orderBy !== null)
            $out->query .= ' ORDER BY '.$this->orderBy["orderBy"].' '.($this->orderBy["desc"] ? 'DESC ' : ' ' );

        if ($this->limit !== null)
            $out->query .= ' LIMIT '.$this->limit.' '.($this->offset === null ? '' : 'OFFSET '.$this->offset ).' ';

        return $out;
    }

    public function getQueries() {
        return $this->queries;
    }

    public function limit($limit) {
        $this->limit = $limit;
        return $this;
    }

    public function orderBy($orderBy, $desc = false) {
        $this->orderBy = ["orderBy"=>$orderBy, "desc"=>$desc];

        return $this;
    }

    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }
}