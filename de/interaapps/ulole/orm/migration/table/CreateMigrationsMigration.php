<?php

namespace de\interaapps\ulole\orm\migration\table;

use de\interaapps\ulole\orm\Database;
use de\interaapps\ulole\orm\migration\Blueprint;
use de\interaapps\ulole\orm\migration\Migration;

class CreateMigrationsMigration implements Migration {
    public function up(Database $database) {
        return $database->create("uloleorm_migrations", function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string("migrated_model");
            $blueprint->int("version");
            $blueprint->timestamp("created")->currentTimestamp();
        }, true);
    }

    public function down(Database $database) {
        return $database->drop("uloleorm_migrations");
    }
}