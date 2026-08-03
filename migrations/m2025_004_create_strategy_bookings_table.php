<?php

namespace Migrations;

use Core\MigrationBase;

class m2025_004_create_strategy_bookings_table extends MigrationBase
{
    public function up(): void
    {
        $this->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS `strategy_bookings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `full_name` VARCHAR(100) NOT NULL,
            `work_email` VARCHAR(191) NOT NULL,
            `phone_number` VARCHAR(30) NOT NULL,
            `company_name` VARCHAR(150) NOT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
            `admin_notes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
        SQL);
    }

    public function down(): void
    {
        $this->exec("DROP TABLE IF EXISTS `strategy_bookings`;");
    }
}
