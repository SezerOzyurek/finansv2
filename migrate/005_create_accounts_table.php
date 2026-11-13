<?php
return function(PDO $pdo): void {
    // 1. accounts tablosu oluştur
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `accounts` (
            `AccountId` INT(11) NOT NULL AUTO_INCREMENT,
            `AccountName` VARCHAR(255) NOT NULL,
            `AccountType` VARCHAR(50) NOT NULL DEFAULT 'Kredi Kartı',
            `Description` TEXT NULL,
            `IsDefault` TINYINT(2) NOT NULL DEFAULT -1,
            `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`AccountId`),
            KEY `idx_accounts_default` (`IsDefault`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 2. assets tablosuna AccountId ekle
    $st = $pdo->prepare("SHOW COLUMNS FROM `assets` LIKE 'AccountId'");
    $st->execute();
    if (!$st->fetch()) {
        $pdo->exec("ALTER TABLE `assets` ADD COLUMN `AccountId` INT(11) NULL DEFAULT NULL AFTER `CategoryId`");
        $pdo->exec("ALTER TABLE `assets` ADD INDEX `idx_assets_account` (`AccountId`)");
    }

    // 3. bills tablosuna AccountId ekle
    $st = $pdo->prepare("SHOW COLUMNS FROM `bills` LIKE 'AccountId'");
    $st->execute();
    if (!$st->fetch()) {
        $pdo->exec("ALTER TABLE `bills` ADD COLUMN `AccountId` INT(11) NULL DEFAULT NULL AFTER `CategoryId`");
        $pdo->exec("ALTER TABLE `bills` ADD INDEX `idx_bills_account` (`AccountId`)");
    }
};

