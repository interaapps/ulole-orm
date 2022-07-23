<?php
namespace de\interaapps\ulole\orm;

use de\interaapps\ulole\orm\migration\Blueprint;
use PDO;
use PDOStatement;

class Database {
    private PDO $connection;

    private const PHP_SQL_TYPES = [
        "int" => "INTEGER",
        "float" => "FLOAT",
        "string" => "TEXT",
        "bool" => "TINYINT"
    ];

    /**
     * @param $username
     * @param $password
     * @param $database
     * @param $host
     * @param $port
     * @param $driver
     * @throws \PDOException
     */
    public function __construct($username, $password=false, $database=false,$host='localhost',$port=3306, $driver="mysql") {
        if ($driver=="sqlite")
            $this->connection = new PDO($driver.':'.$database);
        else
            $this->connection = new PDO($driver.':host='.$host.';dbname='.$database, $username, $password);
    }

    public function getConnection() : PDO {
        return $this->connection;
    }

    public function query($sql) : PDOStatement|bool {
        return $this->connection->query($sql);
    }

    public function create($name, $callable, $ifNotExists = false) : PDOStatement|bool {
        $blueprint = new Blueprint();
        $callable($blueprint);
        $sql = "CREATE TABLE ".($ifNotExists ? "IF NOT EXISTS " : "")."`".$name."` (\n";
        $sql .= implode(",\n", $blueprint->getQueries(true));
        $sql .= "\n) ENGINE = InnoDB;";
        
        return $this->query($sql);
    }

    public function edit($name, $callable) : PDOStatement|bool {
        $statement = $this->connection->query("SHOW COLUMNS FROM ".$name.";");
        $existingColumns = [];
        foreach ($statement->fetchAll(\PDO::FETCH_NUM) as $row) {
            array_push($existingColumns, $row[0]);
        }
        $blueprint = new Blueprint();
        $callable($blueprint);
        $sql = "ALTER TABLE `".$name."`";
        $comma = false;
        foreach ($blueprint->getQueries() as $column => $query) {
            if ($comma)
                $sql .= ", ";
                
            if (in_array($column, $existingColumns))
                $sql .= (substr( $query, 0, 4 ) === "DROP" ? "" : "CHANGE `".$column."` ").$query;
            else
                $sql .= " ADD ".$query;
            
            if (!$comma)
                $comma = true;
        }
        $sql .= ";";
        
        return $this->query($sql);
    }


    public function drop($name) : PDOStatement|bool {
        return $this->query("DROP TABLE `".$name."`;");
    }

    public function autoMigrate() {
        $tables = [];
        foreach ($this->query("SHOW TABLES;")->fetchAll() as $r) {
            $tables[] = $r[0];
        }

        foreach (UloleORM::getModelInformationList() as $modelInformation) {
            $fields = $modelInformation->getFields();
            $columns = array_map(function ($field) use ($modelInformation) {
                $type = $field->getColumnAttribute()->sqlType;
                if ($type == null) {
                    if (isset(self::PHP_SQL_TYPES[$field->getType()->getName()]))
                        $type = self::PHP_SQL_TYPES[$field->getType()->getName()];
                }

                if ($field->getColumnAttribute()->size != null)
                    $type .= "(".$field->getColumnAttribute()->size.")";

                $isIdentifier = $modelInformation->getIdentifier() == $field->getFieldName();

                return [
                    "field" => $field->getFieldName(),
                    "type" => $type,
                    "hasIndex" => $field->getColumnAttribute()->index,
                    "identifier" => $isIdentifier,
                    "query" => "`" . $field->getFieldName() . "` "
                       . $type
                       . ($field->getType()->allowsNull() ? '' : ' NOT NULL')
                       . ($type == 'INTEGER' && $isIdentifier ? ' AUTO_INCREMENT' : '')
                ];
            }, $fields);

            $indexes = array_filter($columns, fn($c) => $c["hasIndex"]);

            if (in_array($modelInformation->getName(), $tables)) {
                $existingFields = array_map(fn($f) => $f[0], $this->query('SHOW COLUMNS FROM '.$modelInformation->getName().';')->fetchAll());
                $existingIndexes = array_map(fn($f) => $f[4], $this->query('SHOW INDEXES FROM '.$modelInformation->getName().';')->fetchAll());

                $q = "ALTER TABLE `" . $modelInformation->getName() . "` ";
                $changes = [];
                foreach ($columns as $column) {
                    if (in_array($column['field'], $existingFields)) {
                        $changes[] = "MODIFY COLUMN " . $column["query"];
                    } else {
                        $changes[] = "ADD " . $column["query"];
                    }
                }
                foreach ($indexes as $index) {
                    $index = $index["field"];
                    if (!in_array($index, $existingIndexes))
                        $changes[] = 'ADD INDEX (`'.$index.'`);';
                }

                $q .= implode(", ", $changes) . ';';
                $this->query($q);
            } else {
                $q = "CREATE TABLE " . $modelInformation->getName() . " (" .
                    implode(', ', array_map(fn($c) =>
                            $c['query']
                            .($c['identifier'] ? ' PRIMARY KEY' : ''
                    ), $columns)
                );

                if (count($indexes) > 0) {
                    $q .= ", INDEX (" . implode(", ", array_map(fn($c) => $c["field"], $indexes)) . ")";
                }

                $q .= ");";
                $this->query($q);
            }
        }
    }

}