<?php

namespace de\interaapps\ulole\orm\migration\table;

use de\interaapps\ulole\orm\attributes\Column;
use de\interaapps\ulole\orm\attributes\Table;
use de\interaapps\ulole\orm\ORMModel;

#[Table("uloleorm_migrations", disableAutoMigrate: true)]
class MigrationModel {
    use ORMModel;

    #[Column]
    public int $id;

    #[Column]
    public ?string $migratedModel;

    #[Column]
    public string $version;

    #[Column]
    public string $created;
}