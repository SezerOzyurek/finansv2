ALTER TABLE `assets` ADD INDEX `idx_assets_date` (`Date`);
ALTER TABLE `assets` ADD INDEX `idx_assets_cat_date` (`CategoryId`, `Date`);
ALTER TABLE `bills` ADD INDEX `idx_bills_date` (`Date`);
ALTER TABLE `bills` ADD INDEX `idx_bills_cat_date` (`CategoryId`, `Date`);

