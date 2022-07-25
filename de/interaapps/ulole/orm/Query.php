<?php

namespace de\interaapps\ulole\orm;

use Closure;
use PDO;
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
    private bool $temporaryQuery = false;
    private bool $withDeleted = false;
    private ModelInformation $modelInformation;

    /**
     * @param Database $database
     * @param class-string<T> $model
     */
    public function __construct(Database $database, string $model) {
        $this->database = $database;
        $this->model = $model;
        $this->modelInformation = UloleORM::getModelInformation($model);
    }

    /**
     * @return $this
     */
    public function whereRaw(string $field, mixed $operator, mixed $val = null, array|null $vals = null): Query {
        $this->queries[] = ['type' => 'AND', 'query' => $field . ' ' . $operator . ' ' . $val, 'vals' => $vals];
        return $this;
    }

    /**
     * if var3 is set var2 will be the operator
     *
     * @return $this
     */
    public function where(string $field, mixed $var2, mixed $var3 = null): Query {
        $operator = $var2;
        $value = $var2;
        if ($var3 === null) {
            $operator = '=';
        } else
            $value = $var3;
        return $this->whereRaw('`' . $this->modelInformation->getFieldName($field) . '`', $operator, '?', [$value]);
    }

    public function whereId($id): Query {
        $this->whereRaw("`" . $this->modelInformation->getIdentifier() . "`", "=", "?", [$id]);
        return $this;
    }

    public function whereDay(string $field, mixed $val): Query {
        $this->whereRaw('DAY(`' . $this->modelInformation->getFieldName($field) . '`)', "=", "?", [$val]);
        return $this;
    }

    public function whereMonth(string $field, mixed $val): Query {
        $this->whereRaw('MONTH(`' . $this->modelInformation->getFieldName($field) . '`)', "=", "?", [$val]);
        return $this;
    }

    public function whereYear(string $field, mixed $val): Query {
        $this->whereRaw('YEAR(`' . $this->modelInformation->getFieldName($field) . '`)', "=", "?", [$val]);
        return $this;
    }

    public function whereDate(string $field, mixed $val): Query {
        $this->whereRaw('DATE(`' . $this->modelInformation->getFieldName($field) . '`)', "=", "?", [$val]);
        return $this;
    }

    public function whereTime(string $field, mixed $val): Query {
        $this->whereRaw('TIME(`' . $this->modelInformation->getFieldName($field) . '`)', "=", "?", [$val]);
        return $this;
    }

    public function isNull(string $field): Query {
        $this->whereRaw('`' . $this->modelInformation->getFieldName($field) . '`', "IS", "NULL");
        return $this;
    }

    public function notNull(string $field): Query {
        $this->whereRaw('`' . $this->modelInformation->getFieldName($field) . '`', "IS", "NOT NULL");
        return $this;
    }

    /**
     * @param class-string $field1Table
     * @param class-string $field2Table
     * @return $this
     */
    public function whereColumns(string $field1Table, string $field1Name, string $operator, string $field2Table, string $field2Name): Query {
        $this->queries[] = [
            'type' => 'AND',
            'query' =>
                UloleORM::getTableName($field1Table) . '.`' . UloleORM::getModelInformation($field1Table)->getFieldName($field1Name) . '` '
                . $operator . ' '
                . UloleORM::getTableName($field2Table) . '.`' . UloleORM::getModelInformation($field2Table)->getFieldName($field2Name) . '`'];
        return $this;
    }

    /**
     * @return $this
     */
    public function orWhere(string $var1, mixed $var2, mixed $var3 = null): Query {
        return $this->or(fn($q) => $q->where($var1, $var2, $var3));
    }

    /**
     * @return $this
     */
    public function like(string $field, mixed $like): Query {
        return $this->where($field, "LIKE", $like);
    }

    /**
     * @return $this
     */
    public function orLike(string $field, mixed $like): Query {
        return $this->orWhere($field, "LIKE", $like);
    }

    /**
     * @return $this
     */
    public function search($field, $like): Query {
        return $this->where($field, "LIKE", "%" . $like . "%");
    }

    public function notBetween(string $field, mixed $val1, mixed $val2): Query {
        $this->queries[] = ['type' => 'AND', 'query' => '`' . $this->modelInformation->getFieldName($field) . '` NOT BETWEEN ? AND ?', 'vals' => [$val1, $val2]];
        return $this;
    }

    public function between(string $field, mixed $val1, mixed $val2): Query {
        $this->queries[] = ['type' => 'AND', 'query' => '`' . $this->modelInformation->getFieldName($field) . '` BETWEEN ? AND ?', 'vals' => [$val1, $val2]];
        return $this;
    }

    /**
     * @return $this
     */
    public function or(callable $callable): Query {
        $query = new Query($this->database, $this->model);
        $query->temporaryQuery = true;
        $callable($query);
        $this->queries[] = ['type' => 'OR', 'queries' => $query];
        return $this;
    }

    /**
     * @return $this
     */
    public function and(callable $callable): Query {
        $query = new Query($this->database, $this->model);
        $query->temporaryQuery = true;
        $callable($query);
        $this->queries[] = ['type' => 'AND', 'queries' => $query];
        return $this;
    }

    /**
     * @return $this
     */
    public function not(callable $callable): Query {
        $query = new Query($this->database, $this->model);
        $query->temporaryQuery = true;
        $callable($query);
        $this->queries[] = ['type' => 'NOT', 'queries' => $query];
        return $this;
    }

    /**
     * @param class-string $table
     * @return $this
     */
    public function whereExists(string $table, callable $callable): Query {
        $query = new Query($this->database, $table);

        $callable($query);
        $this->queries[] = ['type' => 'WHERE_EXISTS', 'query' => 'SELECT * FROM `' . UloleORM::getTableName($table) . '`' . $query->buildQuery()->query];

        return $this;
    }

    /**
     * @throws Null
     */
    public function in(string $field, callable|array $var, string|null $table = null): Query {
        $query = ['type' => 'WHERE_IN', "column" => '`' . $this->modelInformation->getFieldName($field) . '`', "not" => false];
        if (is_array($var)) {
            $query["query"] = implode(",", array_map(fn() => "?", $var));
            $query["vars"] = $var;
        } else {
            if ($table === null)
                throw new \Exception("third argument is not given but needed because second is a callable");
            $query = new Query($this->database, $table);

            $var($query);
            $query["query"] = 'SELECT * FROM `' . UloleORM::getTableName($table) . '`' . $query->buildQuery()->query;
        }
        $this->queries[] = $query;

        return $this;
    }

    public function notIn(string $field, callable|array $var, string|null $table = null): Query {
        $this->in($field, $var, $table);
        $this->queries[count($this->queries) - 1]["not"] = true;
        return $this;
    }

    /**
     * @param class-string $table
     * @return $this
     */
    public function whereNotExists(string $table, callable $callable): Query {
        $query = new Query($this->database, $table);

        $callable($query);
        $this->queries[] = ['type' => 'WHERE_NOT_EXISTS', 'query' => 'SELECT * FROM `' . UloleORM::getTableName($table) . '`' . $query->buildQuery()->query];

        return $this;
    }

    /**
     * @return $this
     */
    public function set($field, $value): Query {
        $this->queries[] = ['type' => 'SET', 'query' => '`' . $this->modelInformation->getFieldName($field) . '` = ?', 'val' => $value];
        return $this;
    }

    /**
     * @return T
     */
    public function first(): mixed {
        if ($this->limit === null)
            $this->limit(1);

        return $this->all()[0] ?? null;
    }

    /**
     * @return T[]
     */
    public function all(): array {
        $vars = [];

        $query = $this->buildQuery();
        $vars = array_merge($vars, $query->vars);

        $statement = $this->run('SELECT * FROM ' . UloleORM::getTableName($this->model) . $query->query . ';', $vars);
        $result = $statement->fetchAll();
        foreach ($result as $entry) {
            $entry->ormInternals_setEntryExists();
            foreach ($this->modelInformation->getFields() as $name => $colInfo) {
                if (isset($entry->{$colInfo->getFieldName()}))
                    $entry->{$name} = $entry->{$colInfo->getFieldName()};
            }
        }
        return $result;
    }

    /**
     * @return T[]
     */
    public function get(): mixed {
        return $this->all();
    }

    /**
     * @return $this
     */
    public function each(callable $closure): Query {
        foreach ($this->all() as $entry)
            $closure($entry);
        return $this;
    }

    private function selectNumber(string $f): int|float {
        $vars = [];
        $query = $this->buildQuery();
        $vars = array_merge($vars, $query->vars);
        $statement = $this->run('SELECT ' . $f . ' as num FROM ' . UloleORM::getTableName($this->model) . $query->query . ';', $vars);
        $statement->setFetchMode(PDO::FETCH_NUM);
        $result = $statement->fetch();
        if ($result === false)
            return 0;

        return $result[0];
    }

    public function count(): int {
        return $this->selectNumber("COUNT(*)");
    }

    public function sum(string $field): float|int {
        return $this->selectNumber("SUM(`" . $this->modelInformation->getFieldName($field) . "`)");
    }

    public function sub(string $field): float|int {
        return $this->selectNumber("SUM(-`" . $this->modelInformation->getFieldName($field) . "`)");
    }

    public function avg(string $field): float|int {
        return $this->selectNumber("AVG(`" . $this->modelInformation->getFieldName($field) . "`)");
    }

    public function min(string $field): float|int {
        return $this->selectNumber("MIN(`" . $this->modelInformation->getFieldName($field) . "`)");
    }

    public function max(string $field): float|int {
        return $this->selectNumber("MAX(`" . $this->modelInformation->getFieldName($field) . "`)");
    }

    public function update(): bool {
        $vars = [];

        $updatedAt = $this->modelInformation->getUpdatedAt();
        if ($updatedAt !== null) {
            $this->set($updatedAt, date("Y-m-d H:i:s"));
        }

        $query = $this->buildQuery();
        $vars = array_merge($vars, $query->vars);

        return $this->run('UPDATE `' . UloleORM::getTableName($this->model) . '`' . $query->query . ';', $vars, true) !== false;
    }

    public function delete(): bool {
        $vars = [];
        $query = $this->buildQuery();
        $vars = array_merge($vars, $query->vars);

        $deletedAt = $this->modelInformation->getDeletedAt();
        if ($deletedAt !== null)
            $this->set($this->modelInformation->getFieldName($deletedAt), date("Y-m-d H:i:s"))->update();

        return $this->run('DELETE FROM `' . UloleORM::getTableName($this->model) . '`' . $query->query . ';', $vars, true) !== false;
    }

    public function run(string $query, array $vars = [], bool $returnResult = false): PDOStatement|bool|null {
        $statement = $this->database->getConnection()->prepare($query);

        $result = $statement->execute($vars);
        if ($returnResult)
            return $result;
        if ($result === false)
            return null;
        $statement->setFetchMode(PDO::FETCH_CLASS, $this->model);
        return $statement;
    }

    protected function buildQuery(): object {
        $deletedAt = $this->modelInformation->getDeletedAt();
        if ($deletedAt !== null && !$this->withDeleted)
            $this->isNull($this->modelInformation->getFieldName($deletedAt));

        $out = (object)["query" => '', 'vars' => []];

        $usedWhere = $this->temporaryQuery;
        $usedSet = false;
        $useCondition = false;

        foreach (array_filter($this->queries, fn($q) => $q["type"] == "SET") as $query) {
            if (!$usedSet) {
                $out->query .= " SET ";
                $usedSet = true;
            } else
                $out->query .= ",";
            $out->query .= ' ' . $query["query"] . ' ';
            $out->vars[] = $query['val'];
        }

        foreach ($this->queries as $query) {
            if ($query["type"] == 'AND' || $query["type"] == 'OR' || $query["type"] == 'NOT') {
                if (!$usedWhere) {
                    $out->query .= " WHERE ";
                    $usedWhere = true;
                }
                if ($useCondition || $query["type"] == 'NOT') {
                    if ($query["type"] == 'NOT') {
                        if ($useCondition) {
                            $out->query .= " AND ";
                        } else
                            $useCondition = true;
                    }
                    $out->query .= $query["type"];
                } else
                    $useCondition = true;
                if (isset($query["query"])) {
                    $out->query .= ' ' . $query["query"] . ' ';
                    if (isset($query['val']))
                        $out->vars[] = $query['val'];
                    if (isset($query['vals']))
                        $out->vars = array_merge($out->vars, $query['vals']);
                } else if (isset($query["queries"])) {
                    $out->query .= ' (';
                    $where = ($query["queries"])->buildQuery();
                    $out->vars = array_merge($out->vars, $where->vars);
                    $out->query .= $where->query;
                    $out->query .= ') ';
                }
            } else if ($query["type"] == 'WHERE_EXISTS' || $query["type"] == 'WHERE_NOT_EXISTS') {
                $out->query .= " WHERE " . ($query["type"] == 'WHERE_NOT_EXISTS' ? "NOT " : "") . "EXISTS (" . $query["query"] . ")";
            } else if ($query["type"] == 'WHERE_IN') {
                $out->query .= " WHERE " . $query["column"] . ($query["not"] ? " NOT " : ' ') . " IN (" . $query["query"] . ")";
                $out->vars = $query["vars"];
            }
        }

        if ($this->orderBy !== null)
            $out->query .= ' ORDER BY ' . $this->orderBy["orderBy"] . ' ' . ($this->orderBy["desc"] ? 'DESC ' : ' ');

        if ($this->limit !== null)
            $out->query .= ' LIMIT ' . $this->limit . ' ' . ($this->offset === null ? '' : 'OFFSET ' . $this->offset) . ' ';

        return $out;
    }

    public function getQueries(): array {
        return $this->queries;
    }

    public function withDeleted($withDeleted = true): Query {
        $this->withDeleted = $withDeleted;
        return $this;
    }

    /**
     * @return $this
     */
    public function limit(int $limit): Query {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return $this
     */
    public function orderBy(string $orderBy, $desc = false): Query {
        $this->orderBy = ["orderBy" => $orderBy, "desc" => $desc];
        return $this;
    }

    /**
     * @return $this
     */
    public function offset(int $offset): Query {
        $this->offset = $offset;
        return $this;
    }
}
