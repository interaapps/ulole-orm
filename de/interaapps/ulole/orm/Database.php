<?php
namespace de\interaapps\ulole\orm;

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

}