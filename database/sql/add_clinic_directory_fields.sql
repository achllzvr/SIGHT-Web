-- Run once on Hostinger (phpMyAdmin → SQL) if migrate was not executed.
-- Adds Partnered Professionals / OSM pin columns to doctor_profile.
-- Safe to re-run: skips columns that already exist.

SET @db := DATABASE();

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='doctor_profile' AND COLUMN_NAME='clinic_address'),
    'SELECT 1',
    'ALTER TABLE `doctor_profile` ADD COLUMN `clinic_address` VARCHAR(500) NULL'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='doctor_profile' AND COLUMN_NAME='operating_hours'),
    'SELECT 1',
    'ALTER TABLE `doctor_profile` ADD COLUMN `operating_hours` VARCHAR(255) NULL'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='doctor_profile' AND COLUMN_NAME='latitude'),
    'SELECT 1',
    'ALTER TABLE `doctor_profile` ADD COLUMN `latitude` DECIMAL(10,7) NULL'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='doctor_profile' AND COLUMN_NAME='longitude'),
    'SELECT 1',
    'ALTER TABLE `doctor_profile` ADD COLUMN `longitude` DECIMAL(10,7) NULL'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='doctor_profile' AND COLUMN_NAME='is_partnered_directory_visible'),
    'SELECT 1',
    'ALTER TABLE `doctor_profile` ADD COLUMN `is_partnered_directory_visible` TINYINT(1) NOT NULL DEFAULT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
