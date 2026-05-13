-- Add email and reset token columns to admin_users
ALTER TABLE `admin_users`
  ADD COLUMN `email` VARCHAR(150) DEFAULT NULL AFTER `username`,
  ADD COLUMN `reset_token` VARCHAR(64) DEFAULT NULL AFTER `password_hash`,
  ADD COLUMN `reset_expires` DATETIME DEFAULT NULL AFTER `reset_token`;

-- Set the admin email (update this to the correct address)
UPDATE `admin_users` SET `email` = 'hello@mrsbfitness.co.uk' WHERE `username` = 'admin';
