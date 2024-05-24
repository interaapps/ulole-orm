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
        return $this->whereRaw($this->modelInformation->getFieldName($field), $operator, '?', [$value]);
    }

    public function whereId($id): Query {
        $this->whereRaw($this->modelInformation->getIdentifier(), "=", "?", [$id]);
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
        $this->whereRaw($this->modelInformation->getFieldName($field), "IS", "NULL");
        return $this;
    }

    public function notNull(string $field): Query {
        $this->whereRaw($this->modelInformation->getFieldName($field), "IS", "NOT NULL");
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
                UloleORM::getTableName($field1Table) . '.' . UloleORM::getModelInformation($field1Table)->getFieldName($field1Name) . '` '
                . $operator . ' '
                . UloleORM::getTableName($field2Table) . '.' . UloleORM::getModelInformation($field2Table)->getFieldName($field2Name)];
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
        $this->queries[] = ['type' => 'AND', 'query' => $this->modelInformation->getFieldName($field) . '` NOT BETWEEN ? AND ?', 'vals' => [$val1, $val2]];
        return $this;
    }

    public function between(string $field, mixed $val1, mixed $val2): Query {
        $this->queries[] = ['type' => 'AND', 'query' => $this->modelInformation->getFieldName($field) . '` BETWEEN ? AND ?', 'vals' => [$val1, $val2]];
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
        $this->queries[] = ['type' => 'WHERE_EXISTS', 'query' => 'SELECT * FROM `' . UloleORM::getTableName($table) . $query->buildQuery()->query];

        return $this;
    }

    /**
     * @throws Null
     */
    public function in(string $field, callable|array $var, string|null $table = null): Query {
        $query = ['type' => 'WHERE_IN', "column" => $this->modelInformation->getFieldName($field), "not" => false];
        if (is_array($var)) {
            $query["query"] = implode(",", array_map(fn() => "?", $var));
            $query["vars"] = $var;
        } else {
            if ($table === null)
                throw new \Exception("third argument is not given but needed because second is a callable");
            $query = new Query($this->database, $table);

            $var($query);
            $query["query"] = 'SELECT * FROM `' . UloleORM::getTableName($table) . $query->buildQuery()->query;
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
        $this->queries[] = ['type' => 'WHERE_NOT_EXISTS', 'query' => 'SELECT * FROM `' . UloleORM::getTableName($table) . $query->buildQuery()->query];

        return $this;
    }

    /**
     * @return $this
     */
    public function set($field, $value): Query {
        $this->queries[] = ['type' => 'SET', 'query' => $this->modelInformation->getFieldName($field) . ' = ?', 'val' => UloleORM::transformToDB($this->database->getDriver(), $this->modelInformation->getColumnInformation($field), $value)];
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
        return $this->database->getDriver()->get($this->model, $this);
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

    private function selectNumber(string $f, string $field): int|float {
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
        return $this->database->getDriver()->count($this->model, $this);
    }

    public function sum(string $field): float|int {
        return $this->database->getDriver()->sum($this->model, $this, $this->modelInformation->getFieldName($field));
    }

    public function sub(string $field): float|int {
        return $this->database->getDriver()->sub($this->model, $this, $this->modelInformation->getFieldName($field));
    }

    public function avg(string $field): float|int {
        return $this->database->getDriver()->avg($this->model, $this, $this->modelInformation->getFieldName($field));
    }

    public function min(string $field): float|int {
        return $this->database->getDriver()->min($this->model, $this, $this->modelInformation->getFieldName($field));
    }

    public function max(string $field): float|int {
        return $this->database->getDriver()->max($this->model, $this, $this->modelInformation->getFieldName($field));
    }

    public function update(): bool {
        $updatedAt = $this->modelInformation->getUpdatedAt();
        if ($updatedAt !== null) {
            if ($this->modelInformation->getColumnInformation($updatedAt)?->getType()?->getName() === \DateTime::class) {
                $this->set($updatedAt, new \DateTime());
            } else {
                $this->set($updatedAt, date("Y-m-d H:i:s"));
            }
        }

        return $this->database->getDriver()->update($this->model, $this);
    }

    public function delete(): bool {
        $deletedAt = $this->modelInformation->getDeletedAt();
        if ($deletedAt !== null) {

            if ($this->modelInformation->getColumnInformation($deletedAt)?->getType()?->getName() === \DateTime::class) {
                $this->set($deletedAt, new \DateTime());
            } else {
                $this->set($deletedAt, date("Y-m-d H:i:s"));
            }
        }

        return $this->database->getDriver()->delete($this->model, $this);
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

    public function offset(int $offset): Query {
        $this->offset = $offset;
        return $this;
    }

    public function isTemporaryQuery(): bool {
        return $this->temporaryQuery;
    }

    public function isWithDeleted(): bool {
        return $this->withDeleted;
    }

    public function getDatabase(): Database {
        return $this->database;
    }

    public function getLimit(): ?int {
        return $this->limit;
    }

    public function getModelInformation(): ModelInformation {
        return $this->modelInformation;
    }

    public function getOffset(): ?int {
        return $this->offset;
    }

    public function getOrderBy(): ?array {
        return $this->orderBy;
    }
}

