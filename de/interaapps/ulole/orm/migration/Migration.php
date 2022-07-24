<?php

namespace de\interaapps\ulole\orm\migration;

use de\interaapps\ulole\orm\Database;

interface Migration {
    public function up(Database $database);

    public function down(Database $database);
}