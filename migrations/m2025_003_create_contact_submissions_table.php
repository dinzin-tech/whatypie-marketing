<?php

namespace Migrations;

use Core\MigrationBase;

class m2025_003_create_contact_submissions_table extends MigrationBase
{
    public function up(): void
    {
        $this->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS `contact_submissions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(191) NOT NULL,
            `subject` VARCHAR(255) NULL,
            `message` TEXT NOT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'unread',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
        SQL);
    }

    public function down(): void
    {
        $this->exec("DROP TABLE IF EXISTS `contact_submissions`;");
    }
}
