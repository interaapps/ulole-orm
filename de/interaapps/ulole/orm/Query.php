<?php
namespace de\interaapps\ulole\orm;

use Closure;
use PDOStatement;

/**
 * @template T
 */
class Query {
    private array $queries = [];
    private string $model;
    private Database $database;
    private int|null $limit = null;
    private int|null $offset = null;
    private array|null $orderBy = null;
    protected bool $temporaryQuery = false;

    /**
     * @param Database $database
     * @param class-string<T> $model
     */
    public function __construct(Database $database, string $model) {
        $this->database = $database;
        $this->model    = $model;
    }

    /**
     * if var3 is set var2 will be the operator
     *
     * @param string $var1
     * @param mixed $var2
     * @param mixed|null $var3
     * @return $this
     */
    public function where(string $var1, mixed $var2, mixed $var3 = null) : Query {
        $field = $var1;
        $operator = $var2;
        $value = $var2;
        if ($var3 === null) {
            $operator = '=';
        } else
            $value = $var3;

        $this->queries[] = ['type' => 'AND', 'query' => '`' . $field . '` ' . $operator . ' ?', 'val' => $value];
        return $this;
    }

    /**
     * @param string $var1
     * @param mixed $var2
     * @param mixed|null $var3
     * @return $this
     */
    public function orWhere(string $var1, mixed $var2, mixed $var3 = null) : Query {
        return $this->or(function($query) use ($var1, $var2, $var3) {
            $query->where($var1, $var2, $var3);
        });
    }

    /**
     * @param string $field
     * @param mixed $like
     * @return $this
     */
    public function like(string $field, mixed $like) : Query {
        return $this->where($field, "LIKE", $like);
    }

    /**
     * @param string $field
     * @param mixed $like
     * @return $this
     */
    public function orLike(string $field, mixed $like) : Query {
        return $this->orWhere($field, "LIKE", $like);
    }

    /**
     * @param $field
     * @param $like
     * @return $this
     */
    public function search($field, $like) : Query {
        return $this->where($field, "LIKE", "%".$like."%");
    }

    /**
     * @param callable $callable
     * @return $this
     */
    public function or(Callable $callable) : Query {
        $query = new Query($this->database, $this->model);
        $query->temporaryQuery = true;
        $callable($query);
        $this->queries[] = ['type' => 'OR', 'queries' => $query];
        return $this;
    }

    /**
     * @param callable $callable
     * @return $this
     */
    public function and(Callable $callable) : Query {
        $query = new Query($this->database, $this->model);
        $query->temporaryQuery = true;
        $callable($query);
        $this->queries[] = ['type' => 'AND', 'queries' => $query];
        return $this;
    }

    /**
     * @param $field
     * @param $value
     * @return $this
     */
    public function set($field, $value) : Query {
        $this->queries[] = ['type' => 'SET', 'query' => '`' . $field . '` = ?', 'val' => $value];
        return $this;
    }

    /**
     * @return T
     */
    public function first() : mixed {
        if ($this->limit === null)
            $this->limit(1);

        return $this->get()[0] ?? null;
    }

    /**
     * @return T[]
     */
    public function all() : array {
        $vars = [];
        $query = $this->buildQuery();
        $vars = array_merge($vars, $query->vars);
        
        $statement = $this->run('SELECT * FROM '.UloleORM::getTableName($this->model).$query->query.';', $vars);
        $result = $statement->fetchAll();
        foreach ($result as $entry){
            $entry->ormInternals_setEntryExists();
            foreach (UloleORM::getModelInformation($this->model)->getFields() as $name => $colInfo) {
                if (isset($entry->{$colInfo->getFieldName()}))
                    $entry->{$name} = $entry->{$colInfo->getFieldName()};
            }
        }
        return $result;
    }

    /**
     * @return T[]
     */
    public function get() : mixed {
        return $this->all();
    }

    /**
     * @param Closure $closure
     * @return $this
     */
    public function each(Closure $closure) : Query {
        foreach ($this->all() as $entry)
            $closure($entry);
        return $this;
    }

    public function count() : int {
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

    public function update() : bool {
        $vars = [];
        $query = $this->buildQuery();
        $vars = array_merge($vars, $query->vars);
        
        return $this->run('UPDATE '.UloleORM::getTableName($this->model).$query->query.';', $vars, true);
    }

    public function delete() : bool {
        $vars = [];
        $query = $this->buildQuery();
        $vars = array_merge($vars, $query->vars);
        
        return $this->run('DELETE FROM '.UloleORM::getTableName($this->model).$query->query.';', $vars, true);
    }

    public function run($query, $vars = [], $returnResult = false) : PDOStatement|bool|null {
        $statement = $this->database->getConnection()->prepare($query);

        $result = $statement->execute($vars);
        if ($returnResult)
            return $result;
        if ($result === false)
            return null;
        $statement->setFetchMode(\PDO::FETCH_CLASS, $this->model);
        return $statement;
    }

    protected function buildQuery() : object {
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
                $out->vars[] = $query['val'];
            }
        }

        if ($this->orderBy !== null)
            $out->query .= ' ORDER BY '.$this->orderBy["orderBy"].' '.($this->orderBy["desc"] ? 'DESC ' : ' ' );

        if ($this->limit !== null)
            $out->query .= ' LIMIT '.$this->limit.' '.($this->offset === null ? '' : 'OFFSET '.$this->offset ).' ';

        return $out;
    }

    public function getQueries() : array {
        return $this->queries;
    }

    /**
     * @param $limit
     * @return $this
     */
    public function limit($limit) : Query {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param $orderBy
     * @param bool $desc
     * @return $this
     */
    public function orderBy($orderBy, $desc = false) : Query {
        $this->orderBy = ["orderBy"=>$orderBy, "desc"=>$desc];
        return $this;
    }

    /**
     * @return $this
     */
    public function offset($offset) : Query {
        $this->offset = $offset;
        return $this;
    }
}
