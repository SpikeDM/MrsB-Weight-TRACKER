-- Add client authentication columns
ALTER TABLE `clients`
  ADD COLUMN `client_password_hash` VARCHAR(255) DEFAULT NULL AFTER `token`,
  ADD COLUMN `password_set`         TINYINT(1)   NOT NULL DEFAULT 0 AFTER `client_password_hash`;
