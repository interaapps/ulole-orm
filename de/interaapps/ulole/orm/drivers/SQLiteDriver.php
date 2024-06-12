<?php

namespace de\interaapps\ulole\orm\drivers;

use PDO;

class SQLiteDriver extends SQLDriver {
    public function __construct(PDO $connection) {
        $this->connection = $connection;
        $this->aiType = "AUTOINCREMENT";
    }

    public function getTables(): array {
        return array_map(fn ($r) => $r[0], $this->query("SELECT name FROM sqlite_master WHERE type='table';")->fetchAll());
    }

    public function edit(string $name, callable $callable): bool {
        // Currently you can't edit the table with sqlite
        return false;
    }

    public function getColumns(string $name): array {
        $colnames = [];
        $sql = $this->query("SELECT sql FROM sqlite_master WHERE tbl_name = '$name'")->fetch()[0];

        $r = preg_match("/\(\s*(\S+)[^,)]*/", $sql, $m, PREG_OFFSET_CAPTURE) ;
        while ($r) {
            $colnames[] = $m[1][0];
            $r = preg_match("/,\s*(\S+)[^,)]*/", $sql, $m, PREG_OFFSET_CAPTURE, $m[0][1] + strlen($m[0][0]) ) ;
        }
        return $colnames;
    }

    public function getIndexes(string $name): array {
        return array_map(fn($f) => $f[0], $this->query("PRAGMA index_list($name);")->fetchAll());
    }

    public function isSupported(string $feature)
    {
        if ($feature === 'ENUM_MIGRATION')
            return false;

        return parent::isSupported($feature);
    }
}