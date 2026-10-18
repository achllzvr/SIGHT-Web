-- Hostinger phpMyAdmin: run if the columns are missing.
-- If a column already exists, that line will error — skip that line and continue.

ALTER TABLE `doctor_profile` ADD COLUMN `clinic_address` VARCHAR(500) NULL;
ALTER TABLE `doctor_profile` ADD COLUMN `operating_hours` VARCHAR(255) NULL;
ALTER TABLE `doctor_profile` ADD COLUMN `latitude` DECIMAL(10,7) NULL;
ALTER TABLE `doctor_profile` ADD COLUMN `longitude` DECIMAL(10,7) NULL;
ALTER TABLE `doctor_profile` ADD COLUMN `is_partnered_directory_visible` TINYINT(1) NOT NULL DEFAULT 1;
