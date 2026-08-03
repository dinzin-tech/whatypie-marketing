<?php

namespace Migrations;

use Core\MigrationBase;

class m2025_002_create_blog_posts_table extends MigrationBase
{
    public function up(): void
    {
        $this->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS `blog_posts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(255) NOT NULL UNIQUE,
            `excerpt` TEXT NOT NULL,
            `content` LONGTEXT NOT NULL,
            `featured_image` VARCHAR(255) NULL,
            `media` TEXT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT `fk_blog_posts_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB;
        SQL);
    }

    public function down(): void
    {
        $this->exec("DROP TABLE IF EXISTS `blog_posts`;");
    }
}
