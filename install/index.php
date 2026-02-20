<?php
declare(strict_types=1);

$appRoot = dirname(__DIR__);
$envPath = dirname($appRoot) . DIRECTORY_SEPARATOR . ".FINANS";
$lockPath = $appRoot . DIRECTORY_SEPARATOR . "install.lock";

function jsonOut(array $payload, int $code = 200): void
{
    http_response_code($code);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function readJsonInput(): array
{
    $raw = file_get_contents("php://input");
    $decoded = json_decode((string)$raw, true);
    return is_array($decoded) ? $decoded : [];
}

function randomAscii(int $len): string
{
    $bytes = random_bytes(max(16, $len));
    $str = rtrim(strtr(base64_encode($bytes), "+/", "-_"), "=");
    if (strlen($str) < $len) {
        $str .= bin2hex(random_bytes($len));
    }
    return substr($str, 0, $len);
}

function detectApiBaseUrl(): string
{
    $https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off");
    $scheme = $https ? "https" : "http";
    $host = $_SERVER["HTTP_HOST"] ?? "localhost";
    $scriptName = str_replace("\\", "/", (string)($_SERVER["SCRIPT_NAME"] ?? "/install/index.php"));
    $dir = str_replace("\\", "/", dirname($scriptName));
    $appPath = preg_replace("#/install$#", "", rtrim($dir, "/"));
    $appPath = ($appPath === "" || $appPath === ".") ? "" : $appPath;
    return $scheme . "://" . $host . $appPath . "/api";
}

function normalizeInstallInput(array $in): array
{
    return [
        "db_host" => trim((string)($in["db_host"] ?? "localhost")),
        "db_port" => trim((string)($in["db_port"] ?? "3306")),
        "db_name" => trim((string)($in["db_name"] ?? "finans")),
        "db_user" => trim((string)($in["db_user"] ?? "")),
        "db_pass" => (string)($in["db_pass"] ?? ""),
        "db_charset" => "utf8mb4",
        "admin_email" => trim((string)($in["admin_email"] ?? "")),
        "admin_password" => (string)($in["admin_password"] ?? ""),
    ];
}

function envValuesFromInput(array $in): array
{
    return [
        "DB_HOST" => $in["db_host"],
        "DB_PORT" => $in["db_port"],
        "DB_NAME" => $in["db_name"],
        "DB_CHARSET" => $in["db_charset"],
        "DB_USER" => $in["db_user"],
        "DB_PASS" => $in["db_pass"],
        "API_BASE_URL" => detectApiBaseUrl(),
        "API_BEARER_TOKEN" => randomAscii(48),
        "CRYPTO_KEY" => randomAscii(32),
        "CRYPTO_IV" => randomAscii(16),
        "DEBUG_HOOK_URL" => "https://finans.requestcatcher.com/test",
        "JWT_SECRET" => randomAscii(64),
        "JWT_ACCESS_TTL_SECONDS" => "300",
        "JWT_REFRESH_TTL_SECONDS" => "300",
        "JWT_RENEW_WINDOW_SECONDS" => "300",
        "JWT_ALLOW_LEGACY_BEARER" => "0",
        "TG_BOT_TOKEN" => "",
        "TG_ALLOWED_CHAT_ID" => "",
        "TG_API_EMAIL" => "",
        "TG_API_PASSWORD" => "",
    ];
}

function buildEnvContent(array $values): string
{
    $line = static function(string $k, string $v): string {
        $safe = str_replace(["\\", "\""], ["\\\\", "\\\""], $v);
        return $k . ' = "' . $safe . '"';
    };
    $order = [
        "DB_HOST",
        "DB_PORT",
        "DB_NAME",
        "DB_CHARSET",
        "DB_USER",
        "DB_PASS",
        "API_BASE_URL",
        "API_BEARER_TOKEN",
        "CRYPTO_KEY",
        "CRYPTO_IV",
        "DEBUG_HOOK_URL",
        "JWT_SECRET",
        "JWT_ACCESS_TTL_SECONDS",
        "JWT_REFRESH_TTL_SECONDS",
        "JWT_RENEW_WINDOW_SECONDS",
        "JWT_ALLOW_LEGACY_BEARER",
        "TG_BOT_TOKEN",
        "TG_ALLOWED_CHAT_ID",
        "TG_API_EMAIL",
        "TG_API_PASSWORD",
    ];
    $lines = [];
    foreach ($order as $k) {
        $lines[] = $line($k, (string)($values[$k] ?? ""));
    }
    return implode(PHP_EOL, $lines) . PHP_EOL;
}

function openDb(array $in): PDO
{
    $host = $in["db_host"] ?? "";
    $port = $in["db_port"] ?? "3306";
    $name = $in["db_name"] ?? "";
    $user = $in["db_user"] ?? "";
    $pass = $in["db_pass"] ?? "";
    $charset = $in["db_charset"] ?? "utf8mb4";
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function testDbConnection(array $in): array
{
    if (($in["db_host"] ?? "") === "" || ($in["db_name"] ?? "") === "" || ($in["db_user"] ?? "") === "") {
        return ["ok" => false, "message" => "DB host, DB name ve DB user zorunlu."];
    }
    try {
        $pdo = openDb($in);
        $pdo->query("SELECT 1");
        return ["ok" => true, "message" => "DB baglantisi basarili."];
    } catch (Throwable $e) {
        return ["ok" => false, "message" => "DB baglantisi basarisiz: " . $e->getMessage()];
    }
}

function dbHasAnyTable(PDO $pdo, string $dbName): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db");
    $stmt->execute([":db" => $dbName]);
    return ((int)$stmt->fetchColumn()) > 0;
}

function isEnvDbReady(string $envPath): bool
{
    if (!is_file($envPath)) { return false; }
    $env = parse_ini_file($envPath, false, INI_SCANNER_RAW);
    if (!is_array($env)) { return false; }
    $in = [
        "db_host" => trim((string)($env["DB_HOST"] ?? "")),
        "db_port" => trim((string)($env["DB_PORT"] ?? "3306")),
        "db_name" => trim((string)($env["DB_NAME"] ?? "")),
        "db_user" => trim((string)($env["DB_USER"] ?? "")),
        "db_pass" => (string)($env["DB_PASS"] ?? ""),
        "db_charset" => trim((string)($env["DB_CHARSET"] ?? "utf8mb4")),
    ];
    if ($in["db_host"] === "" || $in["db_name"] === "" || $in["db_user"] === "") { return false; }
    try {
        $pdo = openDb($in);
        return dbHasAnyTable($pdo, $in["db_name"]);
    } catch (Throwable $e) {
        return false;
    }
}

function createSchema(PDO $pdo): void
{
    $sqlList = [
        "CREATE TABLE IF NOT EXISTS `category` (
            `CategoryId` int(5) NOT NULL AUTO_INCREMENT,
            `CategoryName` varchar(255) NOT NULL,
            `Type` int(2) NOT NULL,
            PRIMARY KEY (`CategoryId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `user` (
            `UserId` int(5) NOT NULL AUTO_INCREMENT,
            `FirstName` varchar(255) NOT NULL,
            `LastName` varchar(255) NOT NULL,
            `Email` varchar(255) NOT NULL,
            `Password` varchar(255) NOT NULL,
            `Currency` varchar(255) NOT NULL,
            PRIMARY KEY (`UserId`),
            UNIQUE KEY `uq_user_email` (`Email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `assets` (
            `AssetsId` int(5) NOT NULL AUTO_INCREMENT,
            `Title` varchar(255) NOT NULL,
            `Date` datetime NOT NULL DEFAULT current_timestamp(),
            `CategoryId` int(5) NOT NULL,
            `Amount` decimal(10,2) NOT NULL,
            `Description` text DEFAULT NULL,
            PRIMARY KEY (`AssetsId`),
            KEY `idx_assets_category` (`CategoryId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `bills` (
            `BillsId` int(5) NOT NULL AUTO_INCREMENT,
            `Title` varchar(255) NOT NULL,
            `Date` datetime NOT NULL DEFAULT current_timestamp(),
            `CategoryId` int(5) NOT NULL,
            `Amount` decimal(10,2) NOT NULL,
            `Description` text DEFAULT NULL,
            PRIMARY KEY (`BillsId`),
            KEY `idx_bills_category` (`CategoryId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `asgari_ucret` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `baslangic_tarihi` varchar(10) DEFAULT NULL,
            `bitis_tarihi` varchar(10) DEFAULT NULL,
            `asgari_ucret` varchar(9) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `movement_files` (
            `PhotoId` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `MovementType` tinyint(3) unsigned NOT NULL,
            `MovementId` int(10) unsigned NOT NULL,
            `FilePath` varchar(255) NOT NULL,
            `OriginalName` varchar(255) DEFAULT NULL,
            `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`PhotoId`),
            KEY `idx_movement_files_type_movement` (`MovementType`, `MovementId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($sqlList as $sql) {
        $pdo->exec($sql);
    }
}

function seedAsgariUcret(PDO $pdo): void
{
    $rows = [
        [1, "2014-01-01", "2014-12-31", "0.846,00"],
        [2, "2015-01-01", "2015-12-31", "0.949,07"],
        [3, "2016-01-01", "2016-12-31", "1.300,99"],
        [4, "2017-01-01", "2017-12-31", "1.404,6"],
        [5, "2018-01-01", "2018-12-31", "1.603,12"],
        [6, "2019-01-01", "2019-12-31", "2.020,90"],
        [7, "2020-01-01", "2020-12-31", "2.324,71"],
        [8, "2021-01-01", "2021-12-31", "2.825,90"],
        [9, "2022-01-01", "2022-06-30", "4.253,40"],
        [10, "2022-07-01", "2022-12-31", "5.500,35"],
        [11, "2023-01-01", "2023-06-30", "8.506,80"],
        [12, "2023-07-01", "2023-12-31", "11.402,32"],
        [13, "2024-01-01", "2024-12-31", "17.002,00"],
        [14, "2025-01-01", "2025-12-31", "22.104,00"],
        [15, "2026-01-01", "2026-12-31", "28.075,50"],
    ];

    $stmt = $pdo->prepare("
        INSERT INTO asgari_ucret (id, baslangic_tarihi, bitis_tarihi, asgari_ucret)
        VALUES (:id, :baslangic_tarihi, :bitis_tarihi, :asgari_ucret)
        ON DUPLICATE KEY UPDATE
            baslangic_tarihi = VALUES(baslangic_tarihi),
            bitis_tarihi = VALUES(bitis_tarihi),
            asgari_ucret = VALUES(asgari_ucret)
    ");

    foreach ($rows as $r) {
        $stmt->execute([
            ":id" => $r[0],
            ":baslangic_tarihi" => $r[1],
            ":bitis_tarihi" => $r[2],
            ":asgari_ucret" => $r[3],
        ]);
    }
}

function createOrUpdateInitialUser(PDO $pdo, string $email, string $plainPassword): void
{
    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException("Gecerli bir e-posta girin.");
    }
    if ($plainPassword === "") {
        throw new RuntimeException("Sifre zorunlu.");
    }

    $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    if (!is_string($passwordHash) || $passwordHash === "") {
        throw new RuntimeException("Sifre hash olusturulamadi.");
    }

    $stmt = $pdo->prepare("SELECT UserId FROM user WHERE Email = :email LIMIT 1");
    $stmt->execute([":email" => $email]);
    $existingId = (int)($stmt->fetchColumn() ?: 0);
    if ($existingId > 0) {
        $upd = $pdo->prepare("UPDATE user SET Password = :p, FirstName = :fn, LastName = :ln, Currency = :cur WHERE UserId = :id");
        $upd->execute([
            ":p" => $passwordHash,
            ":fn" => "Admin",
            ":ln" => "User",
            ":cur" => "TRY",
            ":id" => $existingId,
        ]);
        return;
    }

    $ins = $pdo->prepare("INSERT INTO user (FirstName, LastName, Email, Password, Currency) VALUES (:fn, :ln, :em, :pw, :cur)");
    $ins->execute([
        ":fn" => "Admin",
        ":ln" => "User",
        ":em" => $email,
        ":pw" => $passwordHash,
        ":cur" => "TRY",
    ]);
}

$installClosed = isEnvDbReady($envPath);
if ($installClosed) {
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_GET["action"])) {
        jsonOut(["ok" => false, "message" => "Kurulum zaten tamamlanmis."], 403);
    }
    if (PHP_SAPI !== "cli") { header("Location: ../login.php"); }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_GET["action"])) {
    $action = (string)$_GET["action"];
    $input = normalizeInstallInput(readJsonInput());

    if ($action === "check-db") {
        $result = testDbConnection($input);
        jsonOut($result, $result["ok"] ? 200 : 400);
    }

    if ($action === "finalize") {
        $dbCheck = testDbConnection($input);
        if (!$dbCheck["ok"]) { jsonOut($dbCheck, 400); }

        if ($input["admin_email"] === "" || $input["admin_password"] === "") {
            jsonOut(["ok" => false, "message" => "Admin e-posta ve sifre zorunlu."], 400);
        }

        try {
            $pdo = openDb($input);
            createSchema($pdo);
            seedAsgariUcret($pdo);

            $envValues = envValuesFromInput($input);
            createOrUpdateInitialUser($pdo, $input["admin_email"], $input["admin_password"]);

            $envContent = buildEnvContent($envValues);
            if (@file_put_contents($envPath, $envContent) === false) {
                jsonOut([
                    "ok" => false,
                    "message" => ".FINANS yazilamadi. Yol: " . $envPath,
                ], 400);
            }

            $lockData = [
                "installed_at" => date("c"),
                "env_path" => $envPath,
                "host" => $_SERVER["HTTP_HOST"] ?? "unknown",
                "initial_user_email" => $input["admin_email"],
            ];
            @file_put_contents($lockPath, json_encode($lockData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            jsonOut([
                "ok" => true,
                "message" => "Kurulum tamamlandi. Tablolar olusturuldu, ilk kullanici eklendi, sistem kilitlendi."
            ]);
        } catch (Throwable $e) {
            jsonOut(["ok" => false, "message" => "Kurulum basarisiz: " . $e->getMessage()], 400);
        }
    }

    jsonOut(["ok" => false, "message" => "Gecersiz islem."], 404);
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ultra Finans Kurulum</title>
    <style>
        :root {
            --bg: #0b1220;
            --panel: #121c30;
            --panel-2: #0f172a;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --ok: #22c55e;
            --err: #ef4444;
            --line: #22314d;
            --btn: #0ea5e9;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Manrope, Segoe UI, Arial, sans-serif;
            background: radial-gradient(1200px 500px at 10% -20%, #1e293b, transparent 60%), var(--bg);
            color: var(--text);
        }
        .wrap { max-width: 860px; margin: 32px auto; padding: 0 16px; }
        .card { background: linear-gradient(180deg, var(--panel), var(--panel-2)); border: 1px solid var(--line); border-radius: 16px; padding: 18px; margin-bottom: 14px; }
        h1 { margin: 0 0 6px; font-size: 26px; }
        .muted { color: var(--muted); font-size: 13px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .grid3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        label { font-size: 12px; color: var(--muted); display: block; margin: 0 0 5px; }
        input {
            width: 100%; background: #0b1324; color: var(--text);
            border: 1px solid var(--line); border-radius: 10px; padding: 10px;
        }
        button {
            border: 0; border-radius: 10px; padding: 10px 14px; font-weight: 700;
            color: #001018; background: var(--btn); cursor: pointer;
        }
        button.secondary { background: #334155; color: #e2e8f0; }
        .row { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .status { margin-top: 8px; font-size: 13px; }
        .ok { color: var(--ok); }
        .err { color: var(--err); }
        @media (max-width: 840px) {
            .grid, .grid3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Ultra Finans Kurulum</h1>
    <div class="muted">Sadece DB bilgilerini ve admin hesabi gir. Sistem tabloyu olusturur, kullaniciyi ekler, .FINANS olusturur ve kurulumu kilitler.</div>

    <div class="card">
        <h3>Kurulum Bilgileri</h3>
        <div class="grid3">
            <div><label>DB Host</label><input id="db_host" value="localhost"></div>
            <div><label>DB Port</label><input id="db_port" value="3306"></div>
            <div><label>DB Name</label><input id="db_name" value="finans"></div>
        </div>
        <div class="grid">
            <div><label>DB User</label><input id="db_user" value=""></div>
            <div><label>DB Pass</label><input id="db_pass" type="password" value=""></div>
        </div>
        <div class="grid">
            <div><label>Admin E-Posta</label><input id="admin_email" type="email" value=""></div>
            <div><label>Admin Sifre</label><input id="admin_password" type="password" value=""></div>
        </div>

        <div class="row">
            <button id="btnDbTest" type="button">DB Baglantisini Test Et</button>
            <button id="btnFinalize" class="secondary" type="button">Kurulumu Tamamla</button>
        </div>
        <div id="status" class="status muted"></div>
    </div>
</div>

<script>
function readVal(id) { return document.getElementById(id).value || ""; }
function collect() {
    return {
        db_host: readVal("db_host"),
        db_port: readVal("db_port"),
        db_name: readVal("db_name"),
        db_user: readVal("db_user"),
        db_pass: readVal("db_pass"),
        admin_email: readVal("admin_email"),
        admin_password: readVal("admin_password")
    };
}
function setStatus(ok, msg) {
    var el = document.getElementById("status");
    el.className = "status " + (ok ? "ok" : "err");
    el.textContent = msg;
}
async function postJson(action, payload) {
    const r = await fetch("index.php?action=" + action, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload || {})
    });
    const contentType = r.headers.get("content-type") || "";
    if (contentType.includes("application/json")) {
        const j = await r.json();
        return { ok: r.ok, status: r.status, json: j };
    }
    const t = await r.text();
    return { ok: r.ok, status: r.status, text: t };
}

document.getElementById("btnDbTest").addEventListener("click", async function() {
    setStatus(true, "DB test ediliyor...");
    const out = await postJson("check-db", collect());
    if (out.ok && out.json && out.json.ok) {
        setStatus(true, out.json.message || "DB baglantisi basarili.");
    } else {
        setStatus(false, (out.json && out.json.message) ? out.json.message : "DB testi basarisiz.");
    }
});

document.getElementById("btnFinalize").addEventListener("click", async function() {
    setStatus(true, "Kurulum yapiliyor...");
    const out = await postJson("finalize", collect());
    if (out.ok && out.json && out.json.ok) {
        setStatus(true, out.json.message || "Kurulum tamamlandi.");
        setTimeout(function() { window.location.href = "../login.php"; }, 900);
    } else {
        setStatus(false, (out.json && out.json.message) ? out.json.message : "Kurulum basarisiz.");
    }
});
</script>
</body>
</html>
