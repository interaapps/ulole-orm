<?php
namespace de\interaapps\ulole\orm\migration\table;

use de\interaapps\ulole\orm\ORMModel;

class MigrationModel {
    use ORMModel;

    public $id, $migrated_model, $version, $created;
}