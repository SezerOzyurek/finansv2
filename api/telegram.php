<?php
header("Content-Type: application/json; charset=UTF-8");

$ENV = parse_ini_file(__DIR__ . "/../../.FINANS", false, INI_SCANNER_RAW) ?: [];
$dbHost = (string)($ENV["DB_HOST"] ?? "");
$dbPort = (string)($ENV["DB_PORT"] ?? "3306");
$dbName = (string)($ENV["DB_NAME"] ?? "");
$dbUser = (string)($ENV["DB_USER"] ?? "");
$dbPass = (string)($ENV["DB_PASS"] ?? "");
$dbCharset = (string)($ENV["DB_CHARSET"] ?? "utf8mb4");
$apiBaseUrl = rtrim((string)($ENV["API_BASE_URL"] ?? ""), "/");
$jwtSecret = (string)($ENV["JWT_SECRET"] ?? "");
$jwtAccessTtl = max(60, (int)($ENV["JWT_ACCESS_TTL_SECONDS"] ?? 300));

if ($dbHost === "" || $dbName === "" || $dbUser === "" || $apiBaseUrl === "" || $jwtSecret === "") {
    http_response_code(503);
    echo json_encode(["code" => 503, "message" => "Telegram ayarları eksik."], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}";
    $db = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(["code" => 503, "message" => "Veritabanı bağlantısı kurulamadı."], JSON_UNESCAPED_UNICODE);
    exit;
}

ensureTelegramColumns($db);

$secretToken = getHeaderValue("X-Telegram-Bot-Api-Secret-Token");
if ($secretToken === "") {
    echo json_encode(["code" => 200, "message" => "OK"], JSON_UNESCAPED_UNICODE);
    exit;
}

$userStmt = $db->prepare("SELECT UserId, FirstName, LastName, Email, TelegramBotToken, TelegramChatId, TelegramLinkCode, TelegramWebhookSecret FROM `user` WHERE TelegramWebhookSecret = :s LIMIT 1");
$userStmt->execute([":s" => $secretToken]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
if (!$user || empty($user["TelegramBotToken"])) {
    echo json_encode(["code" => 200, "message" => "OK"], JSON_UNESCAPED_UNICODE);
    exit;
}

$botToken = (string)$user["TelegramBotToken"];
$telegramApiUrl = "https://api.telegram.org/bot" . $botToken . "/";
$recentChatsFile = __DIR__ . "/telegram_recent_chats.json";

$update = json_decode((string)file_get_contents("php://input"), true);
if (!is_array($update)) {
    echo json_encode(["code" => 200, "message" => "OK"], JSON_UNESCAPED_UNICODE);
    exit;
}

$chatId = null;
$text = "";
$callbackQueryId = null;

if (!empty($update["callback_query"])) {
    $chatId = $update["callback_query"]["message"]["chat"]["id"] ?? null;
    $text = trim((string)($update["callback_query"]["data"] ?? ""));
    $callbackQueryId = (string)($update["callback_query"]["id"] ?? "");
} else {
    $chatId = $update["message"]["chat"]["id"] ?? null;
    $text = trim((string)($update["message"]["text"] ?? ""));
}

if ($callbackQueryId !== null && $callbackQueryId !== "") {
    telegramCall($telegramApiUrl, "answerCallbackQuery", ["callback_query_id" => $callbackQueryId]);
}

if ($chatId === null) {
    echo json_encode(["code" => 200, "message" => "OK"], JSON_UNESCAPED_UNICODE);
    exit;
}

appendRecentChat($recentChatsFile, extractChatMeta($update, $user, $chatId, $text));

$storedChatId = trim((string)($user["TelegramChatId"] ?? ""));
$linkCode = trim((string)($user["TelegramLinkCode"] ?? ""));

if (strpos($text, "/start") === 0) {
    $payload = trim((string)substr($text, 6));
    if ($payload !== "" && $linkCode !== "" && hash_equals($linkCode, $payload)) {
        $upd = $db->prepare("UPDATE `user` SET TelegramChatId = :cid, TelegramUpdatedAt = NOW() WHERE UserId = :uid LIMIT 1");
        $upd->execute([":cid" => (string)$chatId, ":uid" => (int)$user["UserId"]]);
        sendMessage($telegramApiUrl, $chatId, "Telegram bağlantısı tamamlandı. Artık bu hesaptan işlemler yapabilirsiniz.", [["Yeni Hareket"]]);
        saveUserFlow($db, (int)$user["UserId"], ["step" => "idle"]);
        echo json_encode(["code" => 200, "message" => "OK"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($storedChatId !== "" && (string)$chatId === $storedChatId) {
        sendMessage($telegramApiUrl, $chatId, "Hoş geldiniz. Yeni hareket oluşturabilirsiniz.", [["Yeni Hareket"]]);
    } else {
        sendMessage($telegramApiUrl, $chatId, "Bu hesap panelden bağlanmamış. Önce panelde Telegram'a giriş yapın.");
    }
    echo json_encode(["code" => 200, "message" => "OK"], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($storedChatId === "") {
    sendMessage($telegramApiUrl, $chatId, "Bu hesap henüz bağlı değil. Paneldeki Telegram'a giriş yap adımını tamamlayın.");
    echo json_encode(["code" => 200, "message" => "OK"], JSON_UNESCAPED_UNICODE);
    exit;
}

if ((string)$chatId !== $storedChatId) {
    sendMessage($telegramApiUrl, $chatId, "İşlem yapma yetkiniz bulunmuyor.");
    echo json_encode(["code" => 200, "message" => "OK"], JSON_UNESCAPED_UNICODE);
    exit;
}

$ctx = loadUserFlow($db, (int)$user["UserId"]);
if (!isset($ctx["step"]) || !is_string($ctx["step"])) {
    $ctx = ["step" => "idle"];
}

if ($text === "Akışı Sıfırla" || $text === "/reset") {
    $ctx = ["step" => "idle"];
    sendMessage($telegramApiUrl, $chatId, "Akış sıfırlandı.", [["Yeni Hareket"]]);
    saveUserFlow($db, (int)$user["UserId"], $ctx);
    echo json_encode(["code" => 200, "message" => "OK"], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($text === "Yeni Hareket") {
    $ctx = ["step" => "select_type"];
    sendMessage($telegramApiUrl, $chatId, "Lütfen işlem türünü seçin:", [["Gelir", "Gider"], ["Akışı Sıfırla"]]);
    saveUserFlow($db, (int)$user["UserId"], $ctx);
    echo json_encode(["code" => 200, "message" => "OK"], JSON_UNESCAPED_UNICODE);
    exit;
}

$step = (string)($ctx["step"] ?? "idle");

if ($step === "select_type") {
    $ctx["type"] = $text;
    $categoryType = ($text === "Gelir") ? 1 : (($text === "Gider") ? 2 : 0);

    if ($categoryType <= 0) {
        sendMessage($telegramApiUrl, $chatId, "Geçersiz işlem türü.", [["Yeni Hareket"]]);
        $ctx = ["step" => "idle"];
    } else {
        $token = issueAccessToken($user, $jwtSecret, $jwtAccessTtl);
        $apiResp = financeApiRequest($apiBaseUrl, "/kategoriler", "GET", [
            "Type" => $categoryType,
            "orderkey" => "CategoryName",
            "ordertype" => "ASC",
        ], $token);

        if ((int)($apiResp["code"] ?? 0) !== 200) {
            sendMessage($telegramApiUrl, $chatId, "Kategori listesi alınamadı.", [["Yeni Hareket"]]);
            $ctx = ["step" => "idle"];
        } else {
            $list = is_array($apiResp["data"]["list"] ?? null) ? $apiResp["data"]["list"] : [];
            if (count($list) === 0) {
                sendMessage($telegramApiUrl, $chatId, "Bu tür için kategori bulunamadı.", [["Yeni Hareket"]]);
                $ctx = ["step" => "idle"];
            } else {
                $buttons = [];
                foreach ($list as $cat) {
                    $catId = (int)($cat["CategoryId"] ?? 0);
                    $catName = (string)($cat["CategoryName"] ?? "Kategori");
                    if ($catId > 0) {
                        $buttons[] = [["text" => $catName, "callback_data" => "cat:" . $catId]];
                    }
                }
                sendMessage($telegramApiUrl, $chatId, "Lütfen kategori seçin:", $buttons, true);
                $ctx["step"] = "select_category";
            }
        }
    }
}
elseif ($step === "select_category") {
    $categoryId = 0;
    if (strpos($text, "cat:") === 0) {
        $categoryId = (int)substr($text, 4);
    } elseif (is_numeric($text)) {
        $categoryId = (int)$text;
    }

    if ($categoryId <= 0) {
        sendMessage($telegramApiUrl, $chatId, "Geçersiz kategori seçimi. Tekrar seçin.");
    } else {
        $ctx["CategoryId"] = $categoryId;
        $ctx["step"] = "enter_title";
        sendMessage($telegramApiUrl, $chatId, "Başlığı girin:");
    }
}
elseif ($step === "enter_title") {
    if ($text === "") {
        sendMessage($telegramApiUrl, $chatId, "Başlık boş olamaz. Tekrar girin:");
    } else {
        $ctx["Title"] = $text;
        $ctx["step"] = "enter_amount";
        sendMessage($telegramApiUrl, $chatId, "Tutarı girin:");
    }
}
elseif ($step === "enter_amount") {
    $normalized = str_replace([",", " "], [".", ""], $text);
    if (!is_numeric($normalized)) {
        sendMessage($telegramApiUrl, $chatId, "Lütfen geçerli bir sayı girin:");
    } else {
        $ctx["Amount"] = (string)$normalized;
        $ctx["step"] = "enter_description";
        sendMessage($telegramApiUrl, $chatId, "Açıklama girin (boş geçebilirsiniz):");
    }
}
elseif ($step === "enter_description") {
    $ctx["Description"] = $text;

    $data = [
        "CategoryId" => (int)($ctx["CategoryId"] ?? 0),
        "Title" => (string)($ctx["Title"] ?? ""),
        "Date" => date("Y-m-d H:i:s"),
        "Amount" => (string)($ctx["Amount"] ?? "0"),
        "Description" => (string)($ctx["Description"] ?? ""),
    ];

    $endpoint = ((string)($ctx["type"] ?? "") === "Gelir") ? "/gelirler" : "/giderler";
    $token = issueAccessToken($user, $jwtSecret, $jwtAccessTtl);
    $saveResp = financeApiRequest($apiBaseUrl, $endpoint, "POST", $data, $token);

    if ((int)($saveResp["code"] ?? 0) === 200) {
        $msg = ((string)($ctx["type"] ?? "Hareket")) . " hareketi kaydedildi.\n" . (string)$data["Title"] . " : " . (string)$data["Amount"] . " TL";
        sendMessage($telegramApiUrl, $chatId, $msg, [["Yeni Hareket"]]);
    } else {
        $msg = (string)($saveResp["message"] ?? "Bilinmeyen hata");
        sendMessage($telegramApiUrl, $chatId, "Hareket kaydedilemedi: " . $msg, [["Yeni Hareket"]]);
    }

    $ctx = ["step" => "idle"];
}
else {
    sendMessage($telegramApiUrl, $chatId, "Komut anlaşılamadı.", [["Yeni Hareket"]]);
    $ctx = ["step" => "idle"];
}

saveUserFlow($db, (int)$user["UserId"], $ctx);

echo json_encode(["code" => 200, "message" => "OK"], JSON_UNESCAPED_UNICODE);
exit;

function ensureTelegramColumns(PDO $db): void
{
    $defs = [
        "TelegramBotToken" => "ALTER TABLE `user` ADD COLUMN TelegramBotToken VARCHAR(255) NULL",
        "TelegramBotUsername" => "ALTER TABLE `user` ADD COLUMN TelegramBotUsername VARCHAR(190) NULL",
        "TelegramChatId" => "ALTER TABLE `user` ADD COLUMN TelegramChatId VARCHAR(64) NULL",
        "TelegramLinkCode" => "ALTER TABLE `user` ADD COLUMN TelegramLinkCode VARCHAR(80) NULL",
        "TelegramWebhookSecret" => "ALTER TABLE `user` ADD COLUMN TelegramWebhookSecret VARCHAR(120) NULL",
        "TelegramFlowState" => "ALTER TABLE `user` ADD COLUMN TelegramFlowState TEXT NULL",
        "TelegramUpdatedAt" => "ALTER TABLE `user` ADD COLUMN TelegramUpdatedAt DATETIME NULL",
    ];

    foreach ($defs as $col => $sql) {
        $st = $db->prepare("SHOW COLUMNS FROM `user` LIKE :c");
        $st->execute([":c" => $col]);
        if (!$st->fetch(PDO::FETCH_ASSOC)) {
            $db->exec($sql);
        }
    }
}

function getHeaderValue(string $name): string
{
    $target = strtolower($name);
    $headers = [];
    if (function_exists("getallheaders")) {
        $headers = getallheaders();
    } elseif (function_exists("apache_request_headers")) {
        $headers = apache_request_headers();
    }

    if (is_array($headers)) {
        foreach ($headers as $k => $v) {
            if (strtolower((string)$k) === $target) {
                return trim((string)$v);
            }
        }
    }

    if ($target === "x-telegram-bot-api-secret-token") {
        return trim((string)($_SERVER["HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN"] ?? ""));
    }

    return "";
}

function loadUserFlow(PDO $db, int $userId): array
{
    $st = $db->prepare("SELECT TelegramFlowState FROM `user` WHERE UserId = :id LIMIT 1");
    $st->execute([":id" => $userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $arr = json_decode((string)($row["TelegramFlowState"] ?? ""), true);
    return is_array($arr) ? $arr : [];
}

function saveUserFlow(PDO $db, int $userId, array $ctx): void
{
    $json = json_encode($ctx, JSON_UNESCAPED_UNICODE);
    if (!is_string($json)) {
        $json = '{"step":"idle"}';
    }
    $st = $db->prepare("UPDATE `user` SET TelegramFlowState = :s WHERE UserId = :id LIMIT 1");
    $st->execute([
        ":s" => $json,
        ":id" => $userId,
    ]);
}

function extractChatMeta(array $update, array $user, $chatId, string $text): array
{
    $chat = [];
    $from = [];
    if (!empty($update["callback_query"])) {
        $chat = is_array($update["callback_query"]["message"]["chat"] ?? null) ? $update["callback_query"]["message"]["chat"] : [];
        $from = is_array($update["callback_query"]["from"] ?? null) ? $update["callback_query"]["from"] : [];
    } else {
        $chat = is_array($update["message"]["chat"] ?? null) ? $update["message"]["chat"] : [];
        $from = is_array($update["message"]["from"] ?? null) ? $update["message"]["from"] : [];
    }

    return [
        "user_id" => (int)($user["UserId"] ?? 0),
        "chat_id" => (string)$chatId,
        "chat_type" => (string)($chat["type"] ?? ""),
        "chat_title" => (string)($chat["title"] ?? ""),
        "username" => (string)($from["username"] ?? ""),
        "first_name" => (string)($from["first_name"] ?? ""),
        "last_name" => (string)($from["last_name"] ?? ""),
        "text" => substr($text, 0, 120),
        "at" => date("c"),
    ];
}

function appendRecentChat(string $path, array $row): void
{
    if (empty($row["chat_id"])) { return; }
    $list = [];
    if (is_file($path)) {
        $raw = @file_get_contents($path);
        $decoded = json_decode((string)$raw, true);
        if (is_array($decoded)) { $list = $decoded; }
    }

    $uid = (int)($row["user_id"] ?? 0);
    $cid = (string)($row["chat_id"] ?? "");
    $list = array_values(array_filter($list, function ($item) use ($uid, $cid) {
        return !((int)($item["user_id"] ?? 0) === $uid && (string)($item["chat_id"] ?? "") === $cid);
    }));

    array_unshift($list, $row);
    $list = array_slice($list, 0, 80);
    @file_put_contents($path, json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function sendMessage(string $telegramApiUrl, $chatId, string $text, ?array $buttons = null, bool $inline = false): void
{
    $payload = [
        "chat_id" => $chatId,
        "text" => $text,
        "parse_mode" => "HTML",
    ];

    if (is_array($buttons) && count($buttons) > 0) {
        if ($inline) {
            $payload["reply_markup"] = json_encode(["inline_keyboard" => $buttons], JSON_UNESCAPED_UNICODE);
        } else {
            $payload["reply_markup"] = json_encode(["keyboard" => $buttons, "resize_keyboard" => true], JSON_UNESCAPED_UNICODE);
        }
    }

    telegramCall($telegramApiUrl, "sendMessage", $payload);
}

function telegramCall(string $telegramApiUrl, string $method, array $payload): array
{
    $ch = curl_init($telegramApiUrl . $method);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) { return ["ok" => false, "description" => $err]; }
    $decoded = json_decode((string)$raw, true);
    return is_array($decoded) ? $decoded : ["ok" => false];
}

function financeApiRequest(string $apiBaseUrl, string $endpoint, string $method = "GET", array $data = [], ?string $accessToken = null): array
{
    $url = $apiBaseUrl . $endpoint;
    if ($method === "GET" && !empty($data)) {
        $url .= "?" . http_build_query($data);
    }

    $headers = [];
    if ($accessToken !== null && $accessToken !== "") {
        $headers[] = "X-Authorization: Bearer " . $accessToken;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    if ($method === "POST" || $method === "PATCH" || $method === "DELETE") {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = "Content-Type: application/json";
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $raw = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ["code" => 500, "message" => "API erişim hatası: " . $err];
    }

    $decoded = json_decode((string)$raw, true);
    return is_array($decoded) ? $decoded : ["code" => 500, "message" => "API yanıtı geçersiz."];
}

function issueAccessToken(array $user, string $jwtSecret, int $ttl): string
{
    $now = time();
    $payload = [
        "uid" => isset($user["UserId"]) ? (int)$user["UserId"] : null,
        "fn" => (string)($user["FirstName"] ?? ""),
        "ln" => (string)($user["LastName"] ?? ""),
        "email" => (string)($user["Email"] ?? ""),
        "iat" => $now,
        "type" => "access",
        "exp" => $now + $ttl,
        "jti" => bin2hex(random_bytes(16)),
    ];

    return jwtEncode($payload, $jwtSecret);
}

function jwtEncode(array $payload, string $secret): string
{
    $header = ["alg" => "HS256", "typ" => "JWT"];
    $headerPart = base64UrlEncode((string)json_encode($header, JSON_UNESCAPED_UNICODE));
    $payloadPart = base64UrlEncode((string)json_encode($payload, JSON_UNESCAPED_UNICODE));
    $signature = hash_hmac("sha256", $headerPart . "." . $payloadPart, $secret, true);
    return $headerPart . "." . $payloadPart . "." . base64UrlEncode($signature);
}

function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), "+/", "-_"), "=");
}
?>

