<?php
declare(strict_types=1);

/**
 * Ultra Finans - Otomatik Migration Yöneticisi
 * Hem CLI hem de Web üzerinden çalışabilir.
 */

if (!function_exists('get_migration_pdo')) {
    function get_migration_pdo(?PDO $existingPdo = null): ?PDO {
        if ($existingPdo instanceof PDO) {
            return $existingPdo;
        }

        $envPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . ".FINANS";
        if (!is_file($envPath)) {
            $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . ".FINANS";
        }
        if (!is_file($envPath)) {
            return null;
        }

        $env = parse_ini_file($envPath, false, INI_SCANNER_RAW) ?: [];
        $host = (string)($env["DB_HOST"] ?? "localhost");
        $port = (string)($env["DB_PORT"] ?? "3306");
        $name = (string)($env["DB_NAME"] ?? "");
        $user = (string)($env["DB_USER"] ?? "");
        $pass = (string)($env["DB_PASS"] ?? "");
        $charset = (string)($env["DB_CHARSET"] ?? "utf8mb4");

        if ($name === "" || $user === "") {
            return null;
        }

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('run_migrations')) {
    function run_migrations(?PDO $pdo = null): array {
        $pdo = get_migration_pdo($pdo);
        if (!$pdo) {
            return ["ok" => false, "message" => "Veritabanı bağlantısı kurulamadı.", "applied" => []];
        }

        // 1. Migrations tablosunu oluştur
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL UNIQUE,
                `batch` INT NOT NULL,
                `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 2. Mevcut migration'ları getir
        $applied = $pdo->query("SELECT migration FROM `migrations`")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $appliedSet = array_flip($applied);

        // Batch numarasını belirle
        $batchStmt = $pdo->query("SELECT MAX(batch) FROM `migrations`");
        $currentBatch = (int)($batchStmt->fetchColumn() ?: 0) + 1;

        // 3. migrate klasöründeki dosyaları tara
        $migrateDir = __DIR__;
        $files = scandir($migrateDir);
        $candidates = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === 'migrate.php' || str_starts_with($file, '.')) {
                continue;
            }
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($ext === 'sql' || $ext === 'php') {
                $candidates[] = $file;
            }
        }
        sort($candidates, SORT_NATURAL);

        $executed = [];
        foreach ($candidates as $file) {
            if (isset($appliedSet[$file])) {
                continue;
            }

            $filePath = $migrateDir . DIRECTORY_SEPARATOR . $file;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            try {
                if ($ext === 'sql') {
                    $sql = file_get_contents($filePath);
                    if ($sql !== false && trim($sql) !== '') {
                        // Sorguları noktalı virgüllere göre bölüp sırayla çalıştır
                        $queries = array_filter(array_map('trim', explode(';', $sql)));
                        foreach ($queries as $q) {
                            if ($q === '') continue;
                            try {
                                $pdo->exec($q);
                            } catch (PDOException $pe) {
                                // İndeks veya sütun zaten varsa (Duplicate key / Duplicate column) yoksay
                                $errCode = (int)$pe->errorInfo[1] ?? 0;
                                if ($errCode !== 1060 && $errCode !== 1061) {
                                    throw $pe;
                                }
                            }
                        }
                    }
                } elseif ($ext === 'php') {
                    $result = require $filePath;
                    if (is_callable($result)) {
                        $result($pdo);
                    }
                }

                $stmt = $pdo->prepare("INSERT INTO `migrations` (`migration`, `batch`, `applied_at`) VALUES (:m, :b, NOW())");
                $stmt->execute([":m" => $file, ":b" => $currentBatch]);
                $executed[] = $file;
            } catch (Throwable $e) {
                return [
                    "ok" => false,
                    "message" => "Migration hatası [{$file}]: " . $e->getMessage(),
                    "applied" => $executed,
                ];
            }
        }

        // Başarılı çalışma işaretini güncelle
        @touch($migrateDir . DIRECTORY_SEPARATOR . ".last_run");

        return [
            "ok" => true,
            "message" => count($executed) > 0 ? (count($executed) . " adet yeni migration uygulandı.") : "Sistem güncel. Yeni migration yok.",
            "applied" => $executed,
        ];
    }
}

if (!function_exists('auto_run_migrations_if_needed')) {
    function auto_run_migrations_if_needed(?PDO $pdo = null): void {
        $flagFile = __DIR__ . DIRECTORY_SEPARATOR . ".last_run";
        $dirTime = @filemtime(__DIR__);
        $flagTime = is_file($flagFile) ? @filemtime($flagFile) : 0;

        // migrate dizinine yeni dosya eklenmişse ya da hiç çalışmamışsa çalıştır
        if (!$flagTime || $dirTime > $flagTime) {
            run_migrations($pdo);
        }
    }
}

// CLI'den doğrudan çağrıldığında
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath(__FILE__) === realpath($argv[0])) {
    echo "=== Ultra Finans Migration Runner ===\n";
    $result = run_migrations();
    echo ($result["ok"] ? "[OK] " : "[HATA] ") . $result["message"] . "\n";
    if (!empty($result["applied"])) {
        foreach ($result["applied"] as $m) {
            echo "  - Uygulandı: {$m}\n";
        }
    }
    exit($result["ok"] ? 0 : 1);
}

