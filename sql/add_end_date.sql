-- Add programme end date to clients
ALTER TABLE `clients`
  ADD COLUMN `end_date` DATE DEFAULT NULL AFTER `start_date`;
