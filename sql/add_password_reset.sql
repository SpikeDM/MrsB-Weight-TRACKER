-- Add password reset token columns to clients
ALTER TABLE `clients`
  ADD COLUMN `reset_token` VARCHAR(64) DEFAULT NULL AFTER `client_password_hash`,
  ADD COLUMN `reset_expires` DATETIME DEFAULT NULL AFTER `reset_token`;
