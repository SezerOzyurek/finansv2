<?php

date_default_timezone_set("Europe/Istanbul");

// ==== DB (parse from .FINANS) ====
$ENV = parse_ini_file(__DIR__ . "/../.FINANS", false, INI_SCANNER_RAW) ?: [];
$DB_HOST = $ENV["DB_HOST"] ?? "localhost";
$DB_NAME = $ENV["DB_NAME"] ?? "finans";
$DB_USER = $ENV["DB_USER"] ?? "root";
$DB_PASS = $ENV["DB_PASS"] ?? "";

if (!isset($pdo)) {
  try {
    $pdo = new PDO(
      "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
      $DB_USER,
      $DB_PASS,
      [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]
    );
  } catch (Throwable $e) {
    if (PHP_SAPI !== 'cli') {
      http_response_code(500);
    }
    echo json_encode(["ok" => false, "error" => "db_connection_failed", "detail" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

/**
 * TCMB USD/EUR rates fetcher and database storer.
 * Skips weekends and already existing rates in the DB.
 */
function tcmb_pull_rates(PDO $pdo, DateTime $startDate, DateTime $endDate): array {
    set_time_limit(0);

    $yesterday = new DateTime("yesterday");
    if ($endDate > $yesterday) {
        $endDate = $yesterday;
    }

    $inserted = 0;
    $updated  = 0;
    $skipped  = 0;
    $errors   = 0;

    if ($endDate < $startDate) {
        return ["inserted" => 0, "updated" => 0, "skipped" => 0, "errors" => 0];
    }

    $stExists = $pdo->prepare("SELECT id FROM tcmb_fx_rates WHERE rate_date = ? LIMIT 1");
    $stUpsert = $pdo->prepare("
      INSERT INTO tcmb_fx_rates
        (rate_date, usd_forex_buying, usd_forex_selling, eur_forex_buying, eur_forex_selling, source_url)
      VALUES
        (:rate_date, :usd_buy, :usd_sell, :eur_buy, :eur_sell, :source_url)
      ON DUPLICATE KEY UPDATE
        usd_forex_buying  = VALUES(usd_forex_buying),
        usd_forex_selling = VALUES(usd_forex_selling),
        eur_forex_buying  = VALUES(eur_forex_buying),
        eur_forex_selling = VALUES(eur_forex_selling),
        source_url        = VALUES(source_url)
    ");

    $cur = clone $startDate;
    while ($cur <= $endDate) {
      $rate_date = $cur->format("Y-m-d");

      // Hafta sonu ise pas geç (TCMB hafta sonu kur yayınlamaz)
      $w = (int)$cur->format("w");
      if ($w === 0 || $w === 6) {
        $skipped++;
        $cur->modify("+1 day");
        continue;
      }

      // Veritabanında zaten varsa çekme (performans & kota dostu)
      $stExists->execute([$rate_date]);
      if ($stExists->fetch()) {
        $skipped++;
        $cur->modify("+1 day");
        continue;
      }

      $yyyy = $cur->format("Y");
      $mm   = $cur->format("m");
      $ddmmyyyy = $cur->format("dmY");
      $url = "https://tcmb.gov.tr/kurlar/{$yyyy}{$mm}/{$ddmmyyyy}.xml";

      $ch = curl_init();
      curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => "Mozilla/5.0 (TCMB FX Collector)",
      ]);
      $body = curl_exec($ch);
      $http = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
      curl_close($ch);

      if ($body === false || $http !== 200) {
        // Yayınlanmamış gün / tatil
        $skipped++;
        $cur->modify("+1 day");
        continue;
      }

      libxml_use_internal_errors(true);
      $xml = simplexml_load_string($body);
      if ($xml === false) {
        $errors++;
        $cur->modify("+1 day");
        continue;
      }

      $usd_buy = null; $usd_sell = null;
      $eur_buy = null; $eur_sell = null;

      if (isset($xml->Currency)) {
        foreach ($xml->Currency as $c) {
          $code = (string)($c["CurrencyCode"] ?? "");
          if ($code === "USD") {
            $usd_buy  = strlen((string)$c->ForexBuying)  ? (string)$c->ForexBuying  : null;
            $usd_sell = strlen((string)$c->ForexSelling) ? (string)$c->ForexSelling : null;
          } elseif ($code === "EUR") {
            $eur_buy  = strlen((string)$c->ForexBuying)  ? (string)$c->ForexBuying  : null;
            $eur_sell = strlen((string)$c->ForexSelling) ? (string)$c->ForexSelling : null;
          }
        }
      }

      if ($usd_buy === null && $usd_sell === null && $eur_buy === null && $eur_sell === null) {
        $skipped++;
        $cur->modify("+1 day");
        continue;
      }

      try {
        $stUpsert->execute([
          ":rate_date"   => $rate_date,
          ":usd_buy"     => $usd_buy,
          ":usd_sell"    => $usd_sell,
          ":eur_buy"     => $eur_buy,
          ":eur_sell"    => $eur_sell,
          ":source_url"  => $url,
        ]);
        $inserted++;
      } catch (Throwable $e) {
        $errors++;
      }

      // Sunucuyu yormamak için çok ufak bir bekleme (opsiyonel)
      usleep(10000); // 10ms

      $cur->modify("+1 day");
    }

    return [
      "inserted" => $inserted,
      "updated"  => $updated,
      "skipped"  => $skipped,
      "errors"   => $errors
    ];
}

// ==== CLI ARGUMENTS PARSING ====
if (PHP_SAPI === 'cli') {
    foreach ($argv as $arg) {
        $parts = explode('=', $arg);
        if (count($parts) === 2) {
            $_GET[$parts[0]] = $parts[1];
        }
    }
}

// ==== ROUTE ROUTER ====
// Sadece dosya doğrudan çalıştırıldığında tetiklenir (include edildiğinde çalışmaz)
$isEntryPoint = false;
if (PHP_SAPI === 'cli') {
    if (isset($argv[0]) && realpath(__FILE__) === realpath($argv[0])) {
        $isEntryPoint = true;
    }
} else {
    if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
        $isEntryPoint = true;
    }
}

if ($isEntryPoint) {
    header("Content-Type: application/json; charset=utf-8");

    $case = $_GET["case"] ?? "";

    switch ($case) {
      case "fx/pull_history": {
        $year  = (int)($_GET["year"] ?? 0);
        $month = (int)($_GET["month"] ?? 0);

        if ($year < 2014 || $year > (int)date("Y") || $month < 1 || $month > 12) {
          http_response_code(400);
          echo json_encode(["ok" => false, "error" => "invalid_year_month"], JSON_UNESCAPED_UNICODE);
          exit;
        }

        $startDate = new DateTime("2014-01-01");
        $endDate   = new DateTime(sprintf("%04d-%02d-01", $year, $month));
        $endDate->modify("last day of this month");

        $res = tcmb_pull_rates($pdo, $startDate, $endDate);

        echo json_encode(array_merge([
          "ok" => true,
          "mode" => "history",
          "from" => $startDate->format("Y-m-d"),
          "to"   => $endDate->format("Y-m-d")
        ], $res), JSON_UNESCAPED_UNICODE);
        exit;
      }

      case "fx/pull_today": {
        $today = new DateTime("today");
        $yesterday = new DateTime("yesterday");

        // Bugün ve dün için kur çekmeyi dene
        $res = tcmb_pull_rates($pdo, $yesterday, $today);

        // Son eklenen kuru getirip çıktı verelim (uyumluluk için)
        $st = $pdo->query("SELECT * FROM tcmb_fx_rates ORDER BY rate_date DESC LIMIT 1");
        $lastRow = $st->fetch();

        if (!$lastRow) {
          http_response_code(404);
          echo json_encode(["ok" => false, "error" => "no_rate_found_for_today_or_yesterday"], JSON_UNESCAPED_UNICODE);
          exit;
        }

        echo json_encode([
          "ok" => true,
          "mode" => "daily",
          "saved" => [
            "rate_date" => $lastRow["rate_date"],
            "source_url" => $lastRow["source_url"],
            "usd_buy" => $lastRow["usd_forex_buying"],
            "usd_sell" => $lastRow["usd_forex_selling"],
            "eur_buy" => $lastRow["eur_forex_buying"],
            "eur_sell" => $lastRow["eur_forex_selling"]
          ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
      }

      default:
        http_response_code(404);
        echo json_encode([
          "ok" => false,
          "error" => "unknown_case",
          "available" => ["fx/pull_history", "fx/pull_today"]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
