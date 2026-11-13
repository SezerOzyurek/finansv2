<?php
declare(strict_types=1);

/**
 * TCMB Günlük Döviz Kurları Eşitleme Görevi (Cron / CLI)
 * 
 * Kullanım (CLI):
 *   php cron/tcmb_sync.php
 *   php cron/tcmb_sync.php --days=30
 *   php cron/tcmb_sync.php --all
 */

date_default_timezone_set("Europe/Istanbul");

$appRoot = dirname(__DIR__);
$envPath = dirname($appRoot) . DIRECTORY_SEPARATOR . ".FINANS";
if (!is_file($envPath)) {
    $envPath = $appRoot . DIRECTORY_SEPARATOR . ".FINANS";
}

if (!is_file($envPath)) {
    fwrite(STDERR, "[HATA] .FINANS yapılandırma dosyası bulunamadı.\n");
    exit(1);
}

$env = parse_ini_file($envPath, false, INI_SCANNER_RAW) ?: [];
$dbHost = (string)($env["DB_HOST"] ?? "localhost");
$dbPort = (string)($env["DB_PORT"] ?? "3306");
$dbName = (string)($env["DB_NAME"] ?? "");
$dbUser = (string)($env["DB_USER"] ?? "");
$dbPass = (string)($env["DB_PASS"] ?? "");
$dbCharset = (string)($env["DB_CHARSET"] ?? "utf8mb4");

if ($dbName === "" || $dbUser === "") {
    fwrite(STDERR, "[HATA] Veritabanı bilgileri eksik.\n");
    exit(1);
}

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "[HATA] DB bağlantı hatası: " . $e->getMessage() . "\n");
    exit(1);
}

require_once $appRoot . DIRECTORY_SEPARATOR . "tcmb.php";

// CLI parametrelerini işle
$options = getopt("", ["days::", "all"]);
$today = new DateTime("today");
$yesterday = new DateTime("yesterday");

if (isset($options["all"])) {
    $startDate = new DateTime("2014-01-01");
    $endDate = $yesterday;
} elseif (isset($options["days"])) {
    $days = max(1, (int)$options["days"]);
    $startDate = (clone $yesterday)->modify("-{$days} days");
    $endDate = $yesterday;
} else {
    // Otomatik: DB'deki en son tarihten dün/bugüne kadar olan eksikleri tamamla
    $stmt = $pdo->query("SELECT MAX(rate_date) FROM tcmb_fx_rates");
    $lastDateStr = $stmt->fetchColumn();

    if (!$lastDateStr) {
        $startDate = (clone $yesterday)->modify("-30 days");
    } else {
        $startDate = (new DateTime($lastDateStr))->modify("+1 day");
    }
    $endDate = $yesterday;
}

$startStr = $startDate->format("Y-m-d");
$endStr = $endDate->format("Y-m-d");

echo "[" . date("Y-m-d H:i:s") . "] TCMB Kur Eşitleme Başlatıldı: {$startStr} -> {$endStr}\n";

if ($startDate > $endDate) {
    echo "[" . date("Y-m-d H:i:s") . "] Kurlar zaten güncel. İşlem yapılmadı.\n";
    exit(0);
}

$res = tcmb_pull_rates($pdo, $startDate, $endDate);

echo "[" . date("Y-m-d H:i:s") . "] Tamamlandı:\n";
echo "  - Eklenen: " . ($res["inserted"] ?? 0) . "\n";
echo "  - Atlanan (hafta sonu / mevcut): " . ($res["skipped"] ?? 0) . "\n";
echo "  - Hata: " . ($res["errors"] ?? 0) . "\n";
exit(0);

