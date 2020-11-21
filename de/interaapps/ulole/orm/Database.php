<?php
namespace de\interaapps\ulole\orm;

use de\interaapps\ulole\orm\migration\Blueprint;

class Database {
    private $connection;

    public function __construct($username, $password=false, $database=false,$host='localhost',$port=3306, $driver="mysql") {
        if ($driver=="sqlite")
            $this->connection = new \PDO($driver.':'.$database);
        else
            $this->connection = new \PDO($driver.':host='.$host.';dbname='.$database, $username, $password);
    }

    public function getConnection(){
        return $this->connection;
    }

    public function create($name, $callable, $ifNotExists = false){
        $blueprint = new Blueprint();
        $callable($blueprint);
        $sql = "CREATE TABLE ".($ifNotExists ? "IF NOT EXISTS " : "")."`".$name."` (\n";
        $sql .= implode(",\n", $blueprint->getQueries(true));
        $sql .= "\n) ENGINE = InnoDB;";
        
        return $this->connection->query($sql);
    }

    public function edit($name, $callable){
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
        
        return $this->connection->query($sql);
    }


    public function drop($name){
        return $this->connection->query("DROP TABLE `".$name."`;");
    }

}