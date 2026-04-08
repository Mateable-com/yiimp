-- Add rent_address column to accounts table for optional BTC renter bonus payout
ALTER TABLE `accounts` ADD COLUMN `rent_address` VARCHAR(128) DEFAULT NULL;
