<?php

namespace de\interaapps\ulole\orm;

use de\interaapps\ulole\orm\migration\Blueprint;
use PDO;
use PDOStatement;

class Database {
    private PDO $connection;

    /**
     * @param string $username
     * @param string|null $password
     * @param string|null $database
     * @param string $host
     * @param int $port
     * @param string $driver
     */
    public function __construct(string $username, string|null $password = null, string|null $database = null, string $host = 'localhost', int $port = 3306, string $driver = "mysql") {
        if ($driver == "sqlite")
            $this->connection = new PDO($driver . ':' . $database);
        else
            $this->connection = new PDO($driver . ':host=' . $host . ';dbname=' . $database, $username, $password);
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    public function query($sql): PDOStatement|bool {
        return $this->connection->query($sql);
    }

    public function create(string $name, $callable, bool $ifNotExists = false): PDOStatement|bool {
        $blueprint = new Blueprint();
        $callable($blueprint);
        $sql = "CREATE TABLE " . ($ifNotExists ? "IF NOT EXISTS " : "") . "`" . $name . "` (\n";
        $sql .= implode(",\n", $blueprint->getQueries(true));
        $sql .= "\n) ENGINE = InnoDB;";

        return $this->query($sql);
    }

    public function edit(string $name, $callable): PDOStatement|bool {
        $statement = $this->connection->query("SHOW COLUMNS FROM " . $name . ";");
        $existingColumns = [];
        foreach ($statement->fetchAll(\PDO::FETCH_NUM) as $row) {
            $existingColumns[] = $row[0];
        }
        $blueprint = new Blueprint();
        $callable($blueprint);
        $sql = "ALTER TABLE `" . $name . "`";
        $comma = false;
        foreach ($blueprint->getQueries() as $column => $query) {
            if ($comma)
                $sql .= ", ";

            if (in_array($column, $existingColumns))
                $sql .= (substr($query, 0, 4) === "DROP" ? "" : "CHANGE `" . $column . "` ") . $query;
            else
                $sql .= " ADD " . $query;

            if (!$comma)
                $comma = true;
        }
        $sql .= ";";

        return $this->query($sql);
    }


    public function drop(string $name): PDOStatement|bool {
        return $this->query("DROP TABLE `" . $name . "`;");
    }

    public function autoMigrate(): Database {
        foreach (UloleORM::getModelInformationList() as $modelInformation) {
            if ($modelInformation->isAutoMigrateDisabled())
                continue;

            $modelInformation->autoMigrate([$this]);
        }

        return $this;
    }

}