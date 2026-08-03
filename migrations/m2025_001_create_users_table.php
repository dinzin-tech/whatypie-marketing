<?php

namespace Migrations;

use Core\MigrationBase;

class m2025_001_create_users_table extends MigrationBase
{
    public function up(): void
    {
        $this->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `email` VARCHAR(191) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` VARCHAR(50) NOT NULL DEFAULT 'admin',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
        SQL);
    }

    public function down(): void
    {
        $this->exec("DROP TABLE IF EXISTS `users`;");
    }
}
