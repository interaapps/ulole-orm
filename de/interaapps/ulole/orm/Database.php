<?php

namespace de\interaapps\ulole\orm;

use de\interaapps\ulole\orm\drivers\Driver;
use de\interaapps\ulole\orm\drivers\MySQLDriver;
use de\interaapps\ulole\orm\drivers\PostgresDriver;
use de\interaapps\ulole\orm\drivers\SQLiteDriver;
use de\interaapps\ulole\orm\migration\Blueprint;
use PDO;
use PDOStatement;

class Database {
    private Driver $driver;

    private static $driverFactories = [];

    public function __construct(string|Driver $driver = "mysql", string $username = "", string|null $password = null, string|null $database = null, string $host = 'localhost', int|null $port = null) {
        if ($driver instanceof Driver) {
            $this->driver = $driver;
        } else {
            $this->driver = self::getDriverFactories()[$driver]($username, $password, $database, $host, $port, $driver);
        }

    }

    public function create(string $name, callable $callable, bool $ifNotExists = false): bool {
        return $this->driver->create($name, $callable);
    }

    public function edit(string $name, callable $callable): bool {
        return $this->driver->edit($name, $callable);
    }

    public function drop(string $name): PDOStatement|bool {
        return $this->driver->drop($name);
    }

    public function autoMigrate(): Database {
        foreach (UloleORM::getModelInformationList() as $modelInformation) {
            if ($modelInformation->isAutoMigrateDisabled())
                continue;

            $modelInformation->autoMigrate([$this]);
        }

        return $this;
    }

    public function getDriver(): Driver {
        return $this->driver;
    }

    public static function setDriverFactory(string $name, callable $callable) {
        self::$driverFactories[$name] = $callable;
    }

    public static function getDriverFactories(): array {
        return self::$driverFactories;
    }
}

Database::setDriverFactory("mysql", function (string $username, string|null $password, string|null $database, string $host, int|null $port, string $driver) : Driver {
    return new MySQLDriver(new PDO($driver . ':host=' . $host. ':' . ($port ?? 3306) . ';dbname=' . $database, $username, $password));
});

Database::setDriverFactory("pgsql", function (string $username, string|null $password, string|null $database, string $host, int|null $port, string $driver) : Driver {
    return new PostgresDriver(new PDO($driver . ':host=' . $host . ';dbname=' . $database, $username, $password));
});

Database::setDriverFactory("sqlite", function (string $username, string|null $password, string|null $database, string $host, int|null $port, string $driver) : Driver {
    return new SQLiteDriver(new PDO($driver . ':' . $database));
});