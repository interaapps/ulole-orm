<?php

namespace de\interaapps\ulole\orm\drivers;

use de\interaapps\ulole\orm\Query;

interface Driver {

    public function create(string $name, callable $callable, bool $ifNotExists = false): bool;
    public function edit(string $name, callable $callable): bool;
    public function drop(string $name): bool;
    public function insert(string $table, array $fields, array $values): mixed;
    public function getTables(): array;
    public function delete(string $model, Query $query): bool;
    public function update(string $model, Query $query): bool;
    public function get(string $model, Query $query): array;

    public function count(string $model, Query $query): int|float;
    public function sum(string $model, Query $query, string $field): int|float;
    public function sub(string $model, Query $query, string $field): int|float;
    public function avg(string $model, Query $query, string $field): int|float;
    public function min(string $model, Query $query, string $field): int|float;
    public function max(string $model, Query $query, string $field): int|float;

}