<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create all ChurchOS ministry, giving, attendance, forms, SMS and supporting tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
CREATE TABLE IF NOT EXISTS `members` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `full_name` VARCHAR(150) NOT NULL,
  `first_name` VARCHAR(80),
  `surname` VARCHAR(80),
  `gender` VARCHAR(10),
  `dob` DATE,
  `id_number` VARCHAR(30),
  `kra_pin` VARCHAR(20),
  `phone` VARCHAR(20),
  `alt_phone` VARCHAR(20),
  `email` VARCHAR(191),
  `address` TEXT,
  `county` VARCHAR(60),
  `church_centre_id` INT UNSIGNED,
  `conversion_date` DATE,
  `baptism_date` DATE,
  `membership_date` DATE,
  `membership_no` VARCHAR(30),
  `marital_status` VARCHAR(20),
  `occupation` VARCHAR(100),
  `employer` VARCHAR(150),
  `emergency_contact` VARCHAR(191),
  `photo` VARCHAR(255),
  `notes` TEXT,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_members_centre` (`centre_id`),
  INDEX `idx_members_name` (`full_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `leaders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `member_id` INT UNSIGNED,
  `full_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20),
  `email` VARCHAR(191),
  `ministry_role` VARCHAR(100),
  `appointment_date` DATE,
  `ordained` TINYINT(1) DEFAULT 0,
  `ordination_date` DATE,
  `bio` TEXT,
  `scope` VARCHAR(60),
  `notes` TEXT,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_leaders_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `new_believers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `full_name` VARCHAR(150) NOT NULL,
  `first_name` VARCHAR(80),
  `surname` VARCHAR(80),
  `gender` VARCHAR(10),
  `phone` VARCHAR(20),
  `address` TEXT,
  `salvation_date` DATE,
  `follow_up_centre` VARCHAR(100),
  `decision_type` VARCHAR(60),
  `follow_up_status` VARCHAR(40) DEFAULT 'pending',
  `notes` TEXT,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_nb_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `visitors` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `full_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20),
  `email` VARCHAR(191),
  `address` TEXT,
  `visited_date` DATE,
  `visit_type` VARCHAR(40),
  `referral` VARCHAR(150),
  `interested_in` VARCHAR(150),
  `follow_up_status` VARCHAR(40) DEFAULT 'pending',
  `notes` TEXT,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_visitors_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `households` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `name` VARCHAR(150) NOT NULL,
  `head_member_id` INT UNSIGNED,
  `address` TEXT,
  `county` VARCHAR(60),
  `phone` VARCHAR(20),
  `notes` TEXT,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_households_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `groups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `group_name` VARCHAR(150) NOT NULL,
  `group_kind` VARCHAR(40),
  `leader_id` INT UNSIGNED,
  `meeting_day` VARCHAR(20),
  `meeting_time` VARCHAR(20),
  `meeting_venue` VARCHAR(191),
  `region` VARCHAR(100),
  `target_size` INT,
  `notes` TEXT,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_groups_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `partners` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `full_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20),
  `email` VARCHAR(191),
  `address` TEXT,
  `partner_since` DATE,
  `tier` VARCHAR(40),
  `intent` VARCHAR(60),
  `notes` TEXT,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_partners_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `missions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `name` VARCHAR(191) NOT NULL,
  `country` VARCHAR(60),
  `region` VARCHAR(100),
  `lead_missionary` VARCHAR(150),
  `start_date` DATE,
  `stage` VARCHAR(40),
  `budget` DECIMAL(14,2),
  `clearance_level` VARCHAR(40),
  `description` TEXT,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_missions_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `diaspora` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `full_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20),
  `email` VARCHAR(191),
  `country` VARCHAR(60),
  `city` VARCHAR(80),
  `home_county` VARCHAR(60),
  `church_connection` VARCHAR(191),
  `notes` TEXT,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_diaspora_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `business_forum` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `full_name` VARCHAR(150) NOT NULL,
  `business_name` VARCHAR(191),
  `phone` VARCHAR(20),
  `email` VARCHAR(191),
  `address` TEXT,
  `sector` VARCHAR(80),
  `membership_level` VARCHAR(40),
  `notes` TEXT,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_bf_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `church_assets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `asset_name` VARCHAR(191) NOT NULL,
  `category` VARCHAR(60),
  `description` TEXT,
  `serial_no` VARCHAR(80),
  `condition_status` VARCHAR(40) DEFAULT 'good',
  `acquisition_date` DATE,
  `acquisition_cost` DECIMAL(14,2),
  `current_value` DECIMAL(14,2),
  `location` VARCHAR(191),
  `responsible_person` VARCHAR(150),
  `notes` TEXT,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_assets_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `asset_events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `event_type` VARCHAR(40) NOT NULL,
  `event_date` DATE,
  `description` TEXT,
  `done_by` VARCHAR(150),
  `cost` DECIMAL(14,2),
  `next_due` DATE,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_ae_asset` (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `asset_files` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_type` VARCHAR(60),
  `file_size` INT UNSIGNED,
  `uploaded_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_af_asset` (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `group_members` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` INT UNSIGNED NOT NULL,
  `member_id` INT UNSIGNED,
  `member_name` VARCHAR(150),
  `role_in_group` VARCHAR(60),
  `joined_date` DATE,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gm` (`group_id`, `member_id`),
  INDEX `idx_gm_group` (`group_id`),
  INDEX `idx_gm_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `member_clearances` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` INT UNSIGNED NOT NULL,
  `clearance_type` VARCHAR(60) NOT NULL,
  `granted_date` DATE,
  `expiry_date` DATE,
  `granted_by_id` INT UNSIGNED,
  `notes` TEXT,
  `created_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_mc_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `follow_ups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `subject_type` VARCHAR(40) NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `subject_name` VARCHAR(150),
  `assigned_to_id` INT UNSIGNED,
  `assigned_to_name` VARCHAR(150),
  `follow_up_date` DATE,
  `method` VARCHAR(40),
  `outcome` VARCHAR(191),
  `status` VARCHAR(40) DEFAULT 'pending',
  `notes` TEXT,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_fu_centre` (`centre_id`),
  INDEX `idx_fu_subject` (`subject_type`, `subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `service_reports` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `service_date` DATE NOT NULL,
  `service_time_id` INT UNSIGNED,
  `service_type` VARCHAR(60),
  `attendance_adults` INT DEFAULT 0,
  `attendance_youth` INT DEFAULT 0,
  `attendance_children` INT DEFAULT 0,
  `attendance_total` INT DEFAULT 0,
  `first_timers` INT DEFAULT 0,
  `new_believers` INT DEFAULT 0,
  `offering_total` DECIMAL(14,2) DEFAULT 0,
  `preacher` VARCHAR(150),
  `sermon_title` VARCHAR(255),
  `sermon_scripture` VARCHAR(100),
  `notes` TEXT,
  `status` VARCHAR(20) DEFAULT 'draft',
  `submitted_by_id` INT UNSIGNED,
  `submitted_at` DATETIME,
  `approved_by_id` INT UNSIGNED,
  `approved_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_sr_centre_date` (`centre_id`, `service_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `funds` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `name` VARCHAR(150) NOT NULL,
  `code` VARCHAR(20),
  `type` VARCHAR(40) DEFAULT 'general',
  `description` TEXT,
  `target_amount` DECIMAL(14,2),
  `balance` DECIMAL(14,2) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_funds_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `giving_batches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `batch_ref` VARCHAR(30) NOT NULL,
  `batch_date` DATE NOT NULL,
  `service_report_id` INT UNSIGNED,
  `description` VARCHAR(255),
  `total_amount` DECIMAL(14,2) DEFAULT 0,
  `status` VARCHAR(20) DEFAULT 'open',
  `created_by_id` INT UNSIGNED,
  `approved_by_id` INT UNSIGNED,
  `approved_at` DATETIME,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_gb_centre` (`centre_id`),
  INDEX `idx_gb_date` (`batch_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `contributions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id` INT UNSIGNED NOT NULL,
  `centre_id` INT UNSIGNED,
  `fund_id` INT UNSIGNED,
  `member_id` INT UNSIGNED,
  `contributor_name` VARCHAR(150),
  `amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `currency` VARCHAR(3) DEFAULT 'KES',
  `method` VARCHAR(40) DEFAULT 'cash',
  `reference` VARCHAR(80),
  `transaction_date` DATE,
  `notes` VARCHAR(255),
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_cont_batch` (`batch_id`),
  INDEX `idx_cont_member` (`member_id`),
  INDEX `idx_cont_fund` (`fund_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `pledges` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `fund_id` INT UNSIGNED,
  `member_id` INT UNSIGNED,
  `pledger_name` VARCHAR(150),
  `phone` VARCHAR(20),
  `pledged_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `paid_amount` DECIMAL(14,2) DEFAULT 0,
  `pledge_date` DATE,
  `due_date` DATE,
  `frequency` VARCHAR(20) DEFAULT 'once',
  `status` VARCHAR(20) DEFAULT 'active',
  `notes` TEXT,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_pledges_centre` (`centre_id`),
  INDEX `idx_pledges_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `fund_transfers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `from_fund_id` INT UNSIGNED NOT NULL,
  `to_fund_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `transfer_date` DATE NOT NULL,
  `reference` VARCHAR(60),
  `reason` TEXT,
  `status` VARCHAR(20) DEFAULT 'pending',
  `approved_by_id` INT UNSIGNED,
  `approved_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_ft_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `calendar_events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `location` VARCHAR(255),
  `start_date` DATE NOT NULL,
  `end_date` DATE,
  `start_time` TIME,
  `end_time` TIME,
  `all_day` TINYINT(1) DEFAULT 0,
  `recurrence` VARCHAR(20),
  `category` VARCHAR(60),
  `color` VARCHAR(20),
  `is_public` TINYINT(1) DEFAULT 1,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_cal_centre_date` (`centre_id`, `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `member_moves` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` INT UNSIGNED NOT NULL,
  `from_centre_id` INT UNSIGNED,
  `to_centre_id` INT UNSIGNED,
  `move_date` DATE,
  `reason` VARCHAR(255),
  `approved_by_id` INT UNSIGNED,
  `notes` TEXT,
  `created_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_mm_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `audience` VARCHAR(40) DEFAULT 'all',
  `publish_date` DATE,
  `expiry_date` DATE,
  `is_pinned` TINYINT(1) DEFAULT 0,
  `is_sms` TINYINT(1) DEFAULT 0,
  `status` VARCHAR(20) DEFAULT 'draft',
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_ann_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `parent_id` INT UNSIGNED,
  `name` VARCHAR(150) NOT NULL,
  `code` VARCHAR(20),
  `description` TEXT,
  `head_user_id` INT UNSIGNED,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_dept_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `service_times` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `name` VARCHAR(100) NOT NULL,
  `day_of_week` TINYINT UNSIGNED,
  `start_time` TIME,
  `end_time` TIME,
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` SMALLINT DEFAULT 0,
  `created_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_st_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `report_schedules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `report_type` VARCHAR(60) NOT NULL,
  `params` TEXT,
  `frequency` VARCHAR(20) NOT NULL,
  `next_run` DATETIME,
  `recipients` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `last_run_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `forms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `slug` VARCHAR(100),
  `fields` LONGTEXT,
  `settings` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `require_login` TINYINT(1) DEFAULT 0,
  `submission_limit` INT DEFAULT 0,
  `submission_count` INT DEFAULT 0,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_form_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `form_submissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` INT UNSIGNED NOT NULL,
  `centre_id` INT UNSIGNED,
  `submitter_id` INT UNSIGNED,
  `data` LONGTEXT,
  `ip_address` VARCHAR(45),
  `created_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_fs_form` (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `sms_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `recipient_name` VARCHAR(150),
  `phone` VARCHAR(20) NOT NULL,
  `message` TEXT NOT NULL,
  `status` VARCHAR(20) DEFAULT 'pending',
  `provider_ref` VARCHAR(80),
  `error` VARCHAR(255),
  `cost` DECIMAL(8,4),
  `sent_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_sms_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `record_versions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `table_name` VARCHAR(60) NOT NULL,
  `record_id` INT UNSIGNED NOT NULL,
  `version` SMALLINT UNSIGNED DEFAULT 1,
  `data` LONGTEXT,
  `changed_by_id` INT UNSIGNED,
  `changed_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_rv_table_rec` (`table_name`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `record_locks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `table_name` VARCHAR(60) NOT NULL,
  `record_id` INT UNSIGNED NOT NULL,
  `locked_by_id` INT UNSIGNED,
  `locked_at` DATETIME,
  `expires_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lock` (`table_name`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `privacy_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `request_type` VARCHAR(40) NOT NULL,
  `subject_name` VARCHAR(191) NOT NULL,
  `subject_email` VARCHAR(191),
  `subject_phone` VARCHAR(20),
  `status` VARCHAR(20) DEFAULT 'pending',
  `notes` TEXT,
  `processed_by_id` INT UNSIGNED,
  `processed_at` DATETIME,
  `created_at` DATETIME,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `permissions` TEXT,
  `last_used_at` DATETIME,
  `expires_at` DATETIME,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token_hash` (`token_hash`),
  INDEX `idx_at_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `site_posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `centre_id` INT UNSIGNED,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(191),
  `content` LONGTEXT,
  `excerpt` TEXT,
  `post_type` VARCHAR(40) DEFAULT 'post',
  `status` VARCHAR(20) DEFAULT 'draft',
  `featured_image` VARCHAR(500),
  `published_at` DATETIME,
  `created_by_id` INT UNSIGNED,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `idx_sp_centre` (`centre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("
CREATE TABLE IF NOT EXISTS `help_articles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(60),
  `title` VARCHAR(255) NOT NULL,
  `body` LONGTEXT,
  `tags` VARCHAR(255),
  `sort_order` SMALLINT DEFAULT 0,
  `is_published` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(Schema $schema): void
    {
        $tables = [
            'help_articles','site_posts','api_tokens','privacy_requests','record_locks',
            'record_versions','sms_log','form_submissions','forms','report_schedules',
            'service_times','departments','announcements','member_moves','calendar_events',
            'fund_transfers','pledges','contributions','giving_batches','funds',
            'service_reports','follow_ups','member_clearances','group_members',
            'asset_files','asset_events','church_assets','business_forum','diaspora',
            'missions','partners','groups','households','visitors','new_believers',
            'leaders','members',
        ];
        foreach ($tables as $t) {
            $this->addSql("DROP TABLE IF EXISTS `$t`");
        }
    }
}
