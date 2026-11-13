<?php
return function(PDO $pdo): void {
    $cols = [
        "TelegramBotToken" => "VARCHAR(255) NULL",
        "TelegramBotUsername" => "VARCHAR(190) NULL",
        "TelegramChatId" => "VARCHAR(64) NULL",
        "TelegramLinkCode" => "VARCHAR(80) NULL",
        "TelegramWebhookSecret" => "VARCHAR(120) NULL",
        "TelegramFlowState" => "TEXT NULL",
        "TelegramUpdatedAt" => "DATETIME NULL",
    ];

    foreach ($cols as $col => $def) {
        $st = $pdo->prepare("SHOW COLUMNS FROM `user` LIKE :col");
        $st->execute([":col" => $col]);
        if (!$st->fetch()) {
            $pdo->exec("ALTER TABLE `user` ADD COLUMN `{$col}` {$def}");
        }
    }
};

