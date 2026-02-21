<?php
include("fonksiyonlar.php");

$envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . ".FINANS";
$recentPath = __DIR__ . DIRECTORY_SEPARATOR . "api" . DIRECTORY_SEPARATOR . "telegram_recent_chats.json";

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

function readEnv(string $path): array
{
    if (!is_file($path)) { return []; }
    $env = parse_ini_file($path, false, INI_SCANNER_RAW);
    return is_array($env) ? $env : [];
}

function dbFromEnv(array $env): PDO
{
    $host = (string)($env["DB_HOST"] ?? "");
    $port = (string)($env["DB_PORT"] ?? "3306");
    $name = (string)($env["DB_NAME"] ?? "");
    $user = (string)($env["DB_USER"] ?? "");
    $pass = (string)($env["DB_PASS"] ?? "");
    $charset = (string)($env["DB_CHARSET"] ?? "utf8mb4");

    if ($host === "" || $name === "" || $user === "") {
        throw new RuntimeException("Veritabanı ayarları eksik.");
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function ensureTelegramColumns(PDO $db): void
{
    $defs = [
        "TelegramBotToken" => "ALTER TABLE `user` ADD COLUMN TelegramBotToken VARCHAR(255) NULL",
        "TelegramBotUsername" => "ALTER TABLE `user` ADD COLUMN TelegramBotUsername VARCHAR(190) NULL",
        "TelegramChatId" => "ALTER TABLE `user` ADD COLUMN TelegramChatId VARCHAR(64) NULL",
        "TelegramLinkCode" => "ALTER TABLE `user` ADD COLUMN TelegramLinkCode VARCHAR(80) NULL",
        "TelegramWebhookSecret" => "ALTER TABLE `user` ADD COLUMN TelegramWebhookSecret VARCHAR(120) NULL",
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

function currentUserId(): int
{
    if (isset($_SESSION["UserId"]) && is_numeric($_SESSION["UserId"])) {
        return (int)$_SESSION["UserId"];
    }

    $token = (string)($_SESSION["Api_Token"] ?? "");
    if ($token === "") { return 0; }
    $parts = explode(".", $token);
    if (count($parts) !== 3) { return 0; }

    $payload = strtr($parts[1], "-_", "+/");
    $pad = strlen($payload) % 4;
    if ($pad > 0) { $payload .= str_repeat("=", 4 - $pad); }

    $decoded = base64_decode($payload, true);
    if (!is_string($decoded)) { return 0; }
    $arr = json_decode($decoded, true);
    if (!is_array($arr)) { return 0; }

    return isset($arr["uid"]) ? (int)$arr["uid"] : 0;
}

function randStr(int $len): string
{
    $raw = bin2hex(random_bytes(max(16, (int)ceil($len / 2))));
    return substr($raw, 0, $len);
}

function tgCall(string $token, string $method, array $params = []): array
{
    if ($token === "") {
        return ["ok" => false, "description" => "Bot token boş."];
    }

    $url = "https://api.telegram.org/bot" . $token . "/" . $method;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    if (!empty($params)) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    }

    $raw = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ["ok" => false, "description" => "Telegram erişim hatası: " . $err];
    }

    $decoded = json_decode((string)$raw, true);
    return is_array($decoded) ? $decoded : ["ok" => false, "description" => "Telegram yanıtı geçersiz."];
}

function loadRecentByUser(string $path, int $userId): array
{
    if (!is_file($path)) { return []; }
    $raw = @file_get_contents($path);
    $arr = json_decode((string)$raw, true);
    if (!is_array($arr)) { return []; }

    $rows = array_values(array_filter($arr, function ($r) use ($userId) {
        return is_array($r) && (int)($r["user_id"] ?? 0) === $userId;
    }));
    return array_slice($rows, 0, 30);
}

$okMsg = "";
$errMsg = "";
$infoMsg = "";

$env = readEnv($envPath);
$userId = currentUserId();
if ($userId <= 0) {
    echo "Kullanıcı bulunamadı.";
    exit;
}

try {
    $db = dbFromEnv($env);
    ensureTelegramColumns($db);
} catch (Throwable $e) {
    echo "Veritabanı bağlantısı kurulurken hata oluştu.";
    exit;
}

$fetchUser = function () use ($db, $userId): array {
    $st = $db->prepare("SELECT UserId, FirstName, LastName, Email, TelegramBotToken, TelegramBotUsername, TelegramChatId, TelegramLinkCode, TelegramWebhookSecret, TelegramUpdatedAt FROM `user` WHERE UserId = :id LIMIT 1");
    $st->execute([":id" => $userId]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: [];
};

$user = $fetchUser();
if (!$user) {
    echo "Kullanıcı kaydı bulunamadı.";
    exit;
}

$apiBaseUrl = rtrim((string)($env["API_BASE_URL"] ?? APP_API_BASE_URL), "/");
$webhookUrl = $apiBaseUrl . "/telegram.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = (string)($_POST["action"] ?? "");

    if ($action === "save_token") {
        $token = trim((string)($_POST["telegram_bot_token"] ?? ""));
        if ($token === "") {
            $errMsg = "Bot token zorunlu.";
        } else {
            $me = tgCall($token, "getMe");
            if (empty($me["ok"])) {
                $errMsg = "Geçersiz bot token: " . (string)($me["description"] ?? "Bilinmeyen hata");
            } else {
                $result = is_array($me["result"] ?? null) ? $me["result"] : [];
                $botUsername = (string)($result["username"] ?? "");

                $oldToken = (string)($user["TelegramBotToken"] ?? "");
                $tokenChanged = ($oldToken !== "" && $oldToken !== $token);

                $linkCode = (string)($user["TelegramLinkCode"] ?? "");
                $secret = (string)($user["TelegramWebhookSecret"] ?? "");
                if ($linkCode === "" || $tokenChanged) { $linkCode = randStr(40); }
                if ($secret === "" || $tokenChanged) { $secret = randStr(48); }

                $chatId = $tokenChanged ? null : ((string)($user["TelegramChatId"] ?? "") ?: null);

                $up = $db->prepare("UPDATE `user` SET TelegramBotToken = :t, TelegramBotUsername = :u, TelegramChatId = :c, TelegramLinkCode = :l, TelegramWebhookSecret = :s, TelegramUpdatedAt = NOW() WHERE UserId = :id LIMIT 1");
                $up->execute([
                    ":t" => $token,
                    ":u" => $botUsername,
                    ":c" => $chatId,
                    ":l" => $linkCode,
                    ":s" => $secret,
                    ":id" => $userId,
                ]);

                $okMsg = "Bot token kaydedildi ve doğrulandı.";
                if ($tokenChanged) {
                    $infoMsg = "Token değiştiği için önceki Telegram bağlantısı sıfırlandı.";
                }
            }
        }
    }

    if ($action === "set_webhook") {
        $token = (string)($user["TelegramBotToken"] ?? "");
        $secret = (string)($user["TelegramWebhookSecret"] ?? "");
        if ($token === "" || $secret === "") {
            $errMsg = "Önce bot token kaydedin.";
        } else {
            $resp = tgCall($token, "setWebhook", [
                "url" => $webhookUrl,
                "secret_token" => $secret,
                "allowed_updates" => json_encode(["message", "callback_query"]),
            ]);
            if (!empty($resp["ok"])) {
                $okMsg = "Webhook başarıyla ayarlandı.";
            } else {
                $errMsg = "Webhook ayarlanamadı: " . (string)($resp["description"] ?? "Bilinmeyen hata");
            }
        }
    }

    if ($action === "webhook_info") {
        $token = (string)($user["TelegramBotToken"] ?? "");
        if ($token === "") {
            $errMsg = "Önce bot token kaydedin.";
        } else {
            $resp = tgCall($token, "getWebhookInfo");
            if (!empty($resp["ok"])) {
                $r = is_array($resp["result"] ?? null) ? $resp["result"] : [];
                $infoMsg = "Mevcut webhook: " . (string)($r["url"] ?? "(boş)");
                if (!empty($r["last_error_message"])) {
                    $infoMsg .= " | Son hata: " . (string)$r["last_error_message"];
                }
            } else {
                $errMsg = "Webhook bilgisi alınamadı: " . (string)($resp["description"] ?? "Bilinmeyen hata");
            }
        }
    }

    if ($action === "regenerate_link") {
        $newCode = randStr(40);
        $up = $db->prepare("UPDATE `user` SET TelegramLinkCode = :l, TelegramChatId = NULL, TelegramUpdatedAt = NOW() WHERE UserId = :id LIMIT 1");
        $up->execute([":l" => $newCode, ":id" => $userId]);
        $okMsg = "Telegram giriş kodu yenilendi. Tekrar Telegram'a Giriş Yap adımını uygulayın.";
    }

    if ($action === "manual_bind") {
        $cid = trim((string)($_POST["bind_chat_id"] ?? ""));
        if ($cid === "") {
            $errMsg = "Bağlanacak Chat ID boş olamaz.";
        } else {
            $up = $db->prepare("UPDATE `user` SET TelegramChatId = :c, TelegramUpdatedAt = NOW() WHERE UserId = :id LIMIT 1");
            $up->execute([":c" => $cid, ":id" => $userId]);
            $okMsg = "Chat ID kullanıcıya bağlandı.";
        }
    }

    if ($action === "unlink") {
        $up = $db->prepare("UPDATE `user` SET TelegramChatId = NULL, TelegramUpdatedAt = NOW() WHERE UserId = :id LIMIT 1");
        $up->execute([":id" => $userId]);
        $okMsg = "Telegram bağlantısı kaldırıldı.";
    }

    $user = $fetchUser();
}

$botToken = (string)($user["TelegramBotToken"] ?? "");
$botUsername = (string)($user["TelegramBotUsername"] ?? "");
$linkCode = (string)($user["TelegramLinkCode"] ?? "");
$chatIdBound = (string)($user["TelegramChatId"] ?? "");
$secret = (string)($user["TelegramWebhookSecret"] ?? "");

$botValid = false;
$botName = "";
if ($botToken !== "") {
    $me = tgCall($botToken, "getMe");
    if (!empty($me["ok"])) {
        $botValid = true;
        $r = is_array($me["result"] ?? null) ? $me["result"] : [];
        $botName = (string)($r["username"] ?? "");
        if ($botName !== "" && $botUsername !== $botName) {
            $botUsername = $botName;
        }
    }
}

$loginUrl = "";
if ($botValid && $botUsername !== "" && $linkCode !== "") {
    $loginUrl = "https://t.me/" . $botUsername . "?start=" . urlencode($linkCode);
}

$recentChats = loadRecentByUser($recentPath, $userId);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Telegram Ayarları</title>
    <link href="https://fonts.googleapis.com/css?family=Manrope:200,300,400,600,700,800,900" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { fontFamily: { sans: ["Manrope", "ui-sans-serif", "system-ui", "Segoe UI", "Arial"] } } } };
    </script>
</head>
<body class="bg-slate-50 text-slate-900" id="top">
    <div class="min-h-screen lg:pl-64 flex flex-col">
        <?php include("menu.php"); ?>
        <?php include("ustmenu.php"); ?>

        <main class="w-full flex-1 px-4 py-6 lg:px-8">
            <section class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Entegrasyon</div>
                        <h1 class="mt-1 text-2xl font-extrabold tracking-tight">Telegram Ayarları</h1>
                        <p class="mt-1 text-sm text-slate-600">Bot token kaydet, webhook ayarla, sonra Telegram'a giriş yaparak Chat ID'ni kullanıcıya bağla.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                        <div>Webhook URL: <span class="font-semibold text-slate-900"><?php echo h($webhookUrl); ?></span></div>
                        <button type="button" id="openWebhookHelp" class="mt-2 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Nasıl yapacağım?</button>
                    </div>
                </div>

                <?php if ($okMsg !== "") { ?>
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?php echo h($okMsg); ?></div>
                <?php } ?>
                <?php if ($errMsg !== "") { ?>
                    <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?php echo h($errMsg); ?></div>
                <?php } ?>
                <?php if ($infoMsg !== "") { ?>
                    <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700"><?php echo h($infoMsg); ?></div>
                <?php } ?>

                <form method="post" class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <input type="hidden" name="action" value="save_token">
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-widest text-slate-500">Telegram Bot Token</label>
                        <input type="text" name="telegram_bot_token" value="<?php echo h($botToken); ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-500" placeholder="123456:ABCDEF...">
                    </div>
                    <div class="md:col-span-2 flex flex-wrap gap-2">
                        <button type="submit" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Bot Token Kaydet ve Doğrula</button>
                    </div>
                </form>

                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                        <div class="text-xs text-slate-500">Bot Durumu</div>
                        <div class="mt-1 font-semibold <?php echo $botValid ? 'text-emerald-700' : 'text-rose-700'; ?>"><?php echo $botValid ? 'Geçerli' : 'Geçersiz veya kayıtlı değil'; ?></div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                        <div class="text-xs text-slate-500">Bot Kullanıcı Adi</div>
                        <div class="mt-1 font-semibold text-slate-900"><?php echo $botUsername !== "" ? '@'.h($botUsername) : '-'; ?></div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                        <div class="text-xs text-slate-500">Bağlı Telegram ID</div>
                        <div class="mt-1 font-semibold text-slate-900"><?php echo $chatIdBound !== "" ? h($chatIdBound) : 'Bağlı değil'; ?></div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <form method="post">
                        <input type="hidden" name="action" value="set_webhook">
                        <button type="submit" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Webhook'u Otomatik Ayarla</button>
                    </form>
                    <form method="post">
                        <input type="hidden" name="action" value="webhook_info">
                        <button type="submit" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Webhook Bilgisi</button>
                    </form>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <?php if ($loginUrl !== "") { ?>
                        <a href="<?php echo h($loginUrl); ?>" target="_blank" rel="noopener" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Telegram'a Giriş Yap</a>
                        <form method="post">
                            <input type="hidden" name="action" value="regenerate_link">
                            <button type="submit" class="inline-flex items-center rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100">Giriş Kodunu Yenile</button>
                        </form>
                    <?php } else { ?>
                        <span class="inline-flex items-center rounded-xl border border-slate-300 bg-slate-50 px-4 py-2 text-sm text-slate-600">Telegram'a giriş butonu için önce geçerli bot token kaydetmelisiniz.</span>
                    <?php } ?>

                    <?php if ($chatIdBound !== "") { ?>
                        <form method="post">
                            <input type="hidden" name="action" value="unlink">
                            <button type="submit" class="inline-flex items-center rounded-xl border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">Bağlantıyı Kaldır</button>
                        </form>
                    <?php } ?>
                </div>
            </section>

            <section class="mt-6 rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Son Mesajlar</div>
                        <h2 class="mt-1 text-lg font-extrabold tracking-tight">Tespit Edilen Chat ID'ler</h2>
                        <p class="mt-1 text-sm text-slate-600">Telegram'a giriş adımıyla bağlama otomatik olur. Gerekirse buradan elle de bağlayabilirsiniz.</p>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Chat ID</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Kullanıcı</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Ad Soyad</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Son Mesaj</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Zaman</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                        <?php if (count($recentChats) === 0) { ?>
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-slate-500">Kayıt yok. Önce botunuza bir mesaj gönderin.</td>
                            </tr>
                        <?php } ?>
                        <?php foreach ($recentChats as $row) { ?>
                            <?php $cid = (string)($row["chat_id"] ?? ""); ?>
                            <tr>
                                <td class="px-3 py-2 font-semibold text-slate-900"><?php echo h($cid); ?></td>
                                <td class="px-3 py-2 text-slate-700"><?php echo !empty($row["username"]) ? '@'.h($row["username"]) : '-'; ?></td>
                                <td class="px-3 py-2 text-slate-700"><?php echo h(trim((string)($row["first_name"] ?? "") . ' ' . (string)($row["last_name"] ?? ""))); ?></td>
                                <td class="px-3 py-2 text-slate-600"><?php echo h((string)($row["text"] ?? "")); ?></td>
                                <td class="px-3 py-2 text-slate-600"><?php echo h((string)($row["at"] ?? "")); ?></td>
                                <td class="px-3 py-2 text-right">
                                    <form method="post" class="inline">
                                        <input type="hidden" name="action" value="manual_bind">
                                        <input type="hidden" name="bind_chat_id" value="<?php echo h($cid); ?>">
                                        <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Bu ID'yi Bağla</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <?php include("footer.php"); ?>
    </div>

    <div id="webhookHelpModal" class="fixed inset-0 z-[90] hidden items-center justify-center bg-slate-950/60 p-4">
        <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Webhook nasıl eklenir?</h3>
                    <p class="mt-1 text-sm text-slate-600">Aşağıdaki adımları uygulayın. Otomatik buton çalışmazsa manuel yöntemi kullanın.</p>
                </div>
                <button type="button" id="closeWebhookHelp" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700">Kapat</button>
            </div>
            <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm text-slate-700">
                <li>Bot token kaydet ve doğrula.</li>
                <li><strong>Webhook'u Otomatik Ayarla</strong> butonuna bas.</li>
                <li>Sonra <strong>Telegram'a Giriş Yap</strong> butonuna basıp botta <code>/start</code> ile bağlantıyı tamamla.</li>
            </ol>
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
                <div class="font-semibold text-slate-900">Manuel setWebhook komutu</div>
                <pre class="mt-2 overflow-x-auto whitespace-pre-wrap break-all"><?php echo h("https://api.telegram.org/bot" . $botToken . "/setWebhook?url=" . urlencode($webhookUrl) . "&secret_token=" . urlencode($secret)); ?></pre>
                <div class="mt-2 text-slate-500">Not: Bu URL sadece bilgilendirme içindir. Bot token'ini açık ortamlarda paylaşmayın.</div>
            </div>
        </div>
    </div>

    <?php include("scripts.php"); ?>
    <script>
    (function () {
        var modal = document.getElementById("webhookHelpModal");
        var openBtn = document.getElementById("openWebhookHelp");
        var closeBtn = document.getElementById("closeWebhookHelp");

        if (openBtn && modal) {
            openBtn.addEventListener("click", function () {
                modal.classList.remove("hidden");
                modal.classList.add("flex");
            });
        }

        function closeModal() {
            if (!modal) { return; }
            modal.classList.remove("flex");
            modal.classList.add("hidden");
        }

        if (closeBtn) {
            closeBtn.addEventListener("click", closeModal);
        }

        if (modal) {
            modal.addEventListener("click", function (e) {
                if (e.target === modal) { closeModal(); }
            });
        }
    })();
    </script>
</body>
</html>

