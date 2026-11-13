CREATE TABLE IF NOT EXISTS `tcmb_fx_rates` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `rate_date` date NOT NULL,
    `usd_forex_buying` decimal(10,4) DEFAULT NULL,
    `usd_forex_selling` decimal(10,4) DEFAULT NULL,
    `eur_forex_buying` decimal(10,4) DEFAULT NULL,
    `eur_forex_selling` decimal(10,4) DEFAULT NULL,
    `source_url` varchar(255) DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tcmb_fx_rates_date` (`rate_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

