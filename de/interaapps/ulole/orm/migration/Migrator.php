<?php

namespace de\interaapps\ulole\orm\migration;

use de\interaapps\ulole\orm\migration\table\CreateMigrationsMigration;
use de\interaapps\ulole\orm\migration\table\MigrationModel;
use de\interaapps\ulole\orm\UloleORM;

class Migrator {
    private $database;
    private $migrations;
    private $logging = false;

    public function __construct(string $database) {
        $this->database = $database;
        $this->migrations = [];
        $db = UloleORM::getDatabase($this->database);
        (new CreateMigrationsMigration())->up($db);
        UloleORM::register(MigrationModel::class);

        if (MigrationModel::table($this->database)->count() == 0) {
            if ($this->logging) echo "Creating first migration\n";
            $migration = new MigrationModel;
            $migration->migratedModel = MigrationModel::class;
            $migration->version = 0;
            $migration->save();
        }
    }

    public function fromFolder($folder, $namespace = null): Migrator {
        if ($namespace == null)
            $namespace = str_replace("/", "\\", $folder);

        foreach (scandir($folder) as $migrationFile) {
            if ($migrationFile !== "." && $migrationFile !== "..") {
                $clazz = $namespace . "\\" . str_replace(".php", "", $migrationFile);
                if (class_exists($clazz)) {

                    array_push($this->migrations, $clazz);
                } else {
                    if ($this->logging) echo "[x] Couln't add $clazz" . "\n";
                }
            }
        }
        return $this;
    }

    public function up(): Migrator {
        $db = UloleORM::getDatabase($this->database);

        $latestVersion = 0;
        $latestVersionObject = MigrationModel::table($this->database)->orderBy("version", true)->first();
        if ($latestVersionObject !== null) {
            $latestVersion = $latestVersionObject->version;
        }

        $newerVersion = $latestVersion + 1;
        foreach ($this->migrations as $clazz) {
            if (MigrationModel::table($this->database)->where("migrated_model", $clazz)->first() === null) {
                $returnValue = (new $clazz())->up($db);
                if ($returnValue === null)
                    $returnValue = true;
                if ($returnValue) {
                    $migration = new MigrationModel;
                    $migration->migratedModel = $clazz;
                    $migration->version = $newerVersion;
                    if ($migration->save())
                        if ($this->logging) echo "Migrated " . $migration->migratedModel . "\n";
                        else
                            if ($this->logging) echo "[x] Couldn't migrated " . $migration->migratedModel . "\n";
                }
            }
        }
        return $this;
    }

    public function down($down = 1): Migrator {
        $db = UloleORM::getDatabase($this->database);

        $version = 0;
        $latestVersionObject = MigrationModel::table($this->database)->orderBy("version", true)->first();
        if ($latestVersionObject !== null) {
            $version = $latestVersionObject->version;
        }
        $version -= $down - 1;
        foreach (MigrationModel::table($this->database)->where("version", ">=", $version)->orderBy("id", true)->all() as $migration) {
            if (in_array($migration->migrated_model, $this->migrations)) {
                $clazz = $migration->migrated_model;
                $returnValue = (new $clazz())->down($db);
                if ($returnValue === null)
                    $returnValue = true;
                if ($returnValue) {
                    if ($migration->delete())
                        if ($this->logging) echo "Downgraded " . $migration->migrated_model . "\n";
                        else
                            if ($this->logging) echo "[x] Couldn't remove table-entry of migrations " . $migration->migrated_model . "\n";
                } else {
                    if ($this->logging) echo "[x] Couldn't downgrade " . $migration->migrated_model . "\n";
                }
            }
        }

        return $this;
    }


    public function getMigrations() {
        return $this->migrations;
    }

    public function addMigrations($migration): Migrator {
        array_push($this->migrations, $migration);
        return $this;
    }

    public function setLogging(bool $logging): Migrator {
        $this->logging = $logging;
        return $this;
    }
}