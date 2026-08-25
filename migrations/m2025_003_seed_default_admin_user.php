<?php

namespace Migrations;

use Core\MigrationBase;

class m2025_003_seed_default_admin_user extends MigrationBase
{
    public function up(): void
    {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $this->exec("
            INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`)
            VALUES ('admin', 'admin@whatypie.in', '$hash', 'admin');
        ");
    }

    public function down(): void
    {
        $this->exec("DELETE FROM `users` WHERE `email` = 'admin@whatypie.in';");
    }
}
