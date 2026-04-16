<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the MySQL 8 baseline schema for MediRDV domain entities, notifications, fixtures support, and refresh tokens.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform,
            'Migration can only be executed safely on MySQL.'
        );

        $this->addSql(<<<'SQL'
CREATE TABLE `user` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(180) NOT NULL,
    `password` VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt',
    `roles` JSON NOT NULL COMMENT '["ROLE_PATIENT"] | ["ROLE_ADMIN"] | ["ROLE_MEDECIN"]',
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `date_of_birth` DATE DEFAULT NULL,
    `gender` ENUM('M', 'F') DEFAULT NULL,
    `address` VARCHAR(255) DEFAULT NULL,
    `avatar_url` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `email_verified_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_email` (`email`),
    INDEX `idx_user_active` (`is_active`),
    INDEX `idx_user_name` (`last_name`, `first_name`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `specialty` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL,
    `icon` VARCHAR(50) DEFAULT NULL COMMENT 'Clé d''icône Bootstrap Icons',
    `description` LONGTEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_specialty_name` (`name`),
    UNIQUE KEY `uk_specialty_slug` (`slug`),
    INDEX `idx_specialty_active_order` (`is_active`, `display_order`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `medecin_profile` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `specialty_id` INT UNSIGNED NOT NULL,
    `bio` LONGTEXT DEFAULT NULL,
    `consultation_duration` INT NOT NULL DEFAULT 30 COMMENT 'Durée en minutes',
    `office_location` VARCHAR(255) DEFAULT NULL,
    `years_experience` INT DEFAULT NULL,
    `diploma` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_medecin_user` (`user_id`),
    INDEX `idx_medecin_specialty` (`specialty_id`),
    CONSTRAINT `fk_medecin_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_medecin_specialty` FOREIGN KEY (`specialty_id`) REFERENCES `specialty` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `availability` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `medecin_id` INT UNSIGNED NOT NULL,
    `day_of_week` SMALLINT UNSIGNED DEFAULT NULL,
    `specific_date` DATE DEFAULT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `is_recurring` TINYINT(1) NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_avail_medecin` (`medecin_id`),
    INDEX `idx_avail_day` (`day_of_week`),
    INDEX `idx_avail_date` (`specific_date`),
    CONSTRAINT `fk_avail_medecin` FOREIGN KEY (`medecin_id`) REFERENCES `medecin_profile` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `chk_avail_time` CHECK (`start_time` < `end_time`),
    CONSTRAINT `chk_avail_type` CHECK (
        (`is_recurring` = 1 AND `day_of_week` IS NOT NULL AND `specific_date` IS NULL)
        OR
        (`is_recurring` = 0 AND `specific_date` IS NOT NULL AND `day_of_week` IS NULL)
    )
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `appointment` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `patient_id` INT UNSIGNED NOT NULL,
    `medecin_id` INT UNSIGNED NOT NULL,
    `date_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `status` ENUM('pending', 'confirmed', 'refused', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `reason` LONGTEXT DEFAULT NULL,
    `admin_note` LONGTEXT DEFAULT NULL,
    `validated_by` INT UNSIGNED DEFAULT NULL,
    `validated_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_appt_patient` (`patient_id`),
    INDEX `idx_appt_medecin` (`medecin_id`),
    INDEX `idx_appt_status` (`status`),
    INDEX `idx_appt_datetime` (`date_time`),
    INDEX `idx_appt_medecin_date` (`medecin_id`, `date_time`),
    CONSTRAINT `fk_appt_patient` FOREIGN KEY (`patient_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_appt_medecin` FOREIGN KEY (`medecin_id`) REFERENCES `medecin_profile` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_appt_validator` FOREIGN KEY (`validated_by`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `consultation` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `appointment_id` INT UNSIGNED NOT NULL,
    `notes` LONGTEXT DEFAULT NULL,
    `diagnosis` VARCHAR(500) DEFAULT NULL,
    `vital_signs` JSON DEFAULT NULL,
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `completed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_consultation_appt` (`appointment_id`),
    CONSTRAINT `fk_consult_appt` FOREIGN KEY (`appointment_id`) REFERENCES `appointment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `medication` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL,
    `generic_name` VARCHAR(200) DEFAULT NULL,
    `default_dosage` VARCHAR(100) DEFAULT NULL,
    `form` ENUM('comprimé', 'gélule', 'sirop', 'injection', 'pommade', 'gouttes', 'suppositoire', 'autre') NOT NULL DEFAULT 'comprimé',
    `category` VARCHAR(100) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_med_name` (`name`),
    INDEX `idx_med_category` (`category`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `prescription` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `consultation_id` INT UNSIGNED NOT NULL,
    `notes` LONGTEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_prescription_consult` (`consultation_id`),
    CONSTRAINT `fk_presc_consult` FOREIGN KEY (`consultation_id`) REFERENCES `consultation` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `prescription_item` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `prescription_id` INT UNSIGNED NOT NULL,
    `medication_id` INT UNSIGNED DEFAULT NULL,
    `custom_name` VARCHAR(200) DEFAULT NULL,
    `dosage` VARCHAR(150) NOT NULL,
    `duration` VARCHAR(100) DEFAULT NULL,
    `frequency` VARCHAR(100) DEFAULT NULL,
    `instructions` LONGTEXT DEFAULT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_pi_prescription` (`prescription_id`),
    INDEX `idx_pi_medication` (`medication_id`),
    CONSTRAINT `fk_pi_prescription` FOREIGN KEY (`prescription_id`) REFERENCES `prescription` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pi_medication` FOREIGN KEY (`medication_id`) REFERENCES `medication` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `notification` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` LONGTEXT NOT NULL,
    `type` ENUM('appointment_confirmed', 'appointment_refused', 'appointment_reminder', 'consultation_ready') NOT NULL,
    `reference_id` INT UNSIGNED DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_notif_user` (`user_id`),
    INDEX `idx_notif_read` (`user_id`, `is_read`),
    INDEX `idx_notif_type` (`type`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `refresh_tokens` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `refresh_token` VARCHAR(128) NOT NULL,
    `username` VARCHAR(255) NOT NULL,
    `valid` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `UNIQ_C74F2195C05FB297` (`refresh_token`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform,
            'Migration can only be executed safely on MySQL.'
        );

        $this->addSql('DROP TABLE `refresh_tokens`');
        $this->addSql('DROP TABLE `notification`');
        $this->addSql('DROP TABLE `prescription_item`');
        $this->addSql('DROP TABLE `prescription`');
        $this->addSql('DROP TABLE `medication`');
        $this->addSql('DROP TABLE `consultation`');
        $this->addSql('DROP TABLE `appointment`');
        $this->addSql('DROP TABLE `availability`');
        $this->addSql('DROP TABLE `medecin_profile`');
        $this->addSql('DROP TABLE `specialty`');
        $this->addSql('DROP TABLE `user`');
    }
}
