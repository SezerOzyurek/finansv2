<?php 
session_start();
$ENV = parse_ini_file(__DIR__ . "/../.FINANS", false, INI_SCANNER_RAW) ?: [];
if (empty($ENV) || empty($ENV["API_BASE_URL"])) {
    if (PHP_SAPI !== "cli") { header("Location: install/"); }
    exit;
}

$dbHost = (string)($ENV["DB_HOST"] ?? "");
$dbPort = (string)($ENV["DB_PORT"] ?? "3306");
$dbName = (string)($ENV["DB_NAME"] ?? "");
$dbUser = (string)($ENV["DB_USER"] ?? "");
$dbPass = (string)($ENV["DB_PASS"] ?? "");
$dbCharset = (string)($ENV["DB_CHARSET"] ?? "utf8mb4");

$dbReady = false;
if ($dbHost !== "" && $dbName !== "" && $dbUser !== "") {
    try {
        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = :db");
        $stmt->execute([":db" => $dbName]);
        $tableCount = (int)($stmt->fetchColumn() ?? 0);
        $dbReady = ($tableCount > 0);
    } catch (Throwable $e) {
        $dbReady = false;
    }
}

if (!$dbReady) {
    if (PHP_SAPI !== "cli") { header("Location: install/"); }
    exit;
}
if (!defined("APP_API_BASE_URL")) { define("APP_API_BASE_URL", rtrim((string)($ENV["API_BASE_URL"] ?? ""), "/")); }
if (!defined("APP_SITE_URL")) { define("APP_SITE_URL", preg_replace('#/api$#', '', APP_API_BASE_URL)); }
if (!defined("APP_CRYPTO_KEY")) { define("APP_CRYPTO_KEY", (string)($ENV["CRYPTO_KEY"] ?? "")); }
if (!defined("APP_CRYPTO_IV")) { define("APP_CRYPTO_IV", (string)($ENV["CRYPTO_IV"] ?? "")); }
if (!defined("APP_DEBUG_HOOK_URL")) { define("APP_DEBUG_HOOK_URL", (string)($ENV["DEBUG_HOOK_URL"] ?? "https://finans.requestcatcher.com/test")); }

function loginmi() { if(isset($_SESSION['Api_Token']) && $_SESSION['Api_Token'] != "") { return true; } else { return false; } }

if(!isset($_GET['loginbypass']) && !loginmi()) { header("Location: " . APP_SITE_URL . "/login.php"); exit; } 

function apiRequest($endpoint, $method = 'GET', $data = [], $token = NULL) 
{
    $requestFn = function($url, $method, $data, $token) {
        $ch = curl_init($url);
        $headers = [];

        if ($method === 'POST' || $method === 'PATCH' || $method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (is_array($data) && array_filter($data, fn($value) => $value instanceof CURLFile)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                $headers[] = 'Content-Type: application/json';
            }
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if ($token) { $headers[] = 'X-Authorization: Bearer ' . $token; }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $raw = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) { return ["error" => "cURL error: " . $error]; }
        $decoded = json_decode((string)$raw, true);
        return is_array($decoded) ? $decoded : ["code" => 500, "message" => "API yaniti gecersiz."];
    };

    $applyTokens = function(array $resp): void {
        if (!empty($resp["tokens"]) && is_array($resp["tokens"])) {
            $newAccess = $resp["tokens"]["AccessToken"] ?? null;
            $newRefresh = $resp["tokens"]["RefreshToken"] ?? null;
            if (!empty($newAccess)) { $_SESSION["Api_Token"] = $newAccess; }
            if (!empty($newRefresh)) { $_SESSION["Refresh_Token"] = $newRefresh; }
        }
    };

    $forceLogoutRedirect = function() use ($endpoint): void {
        unset($_SESSION["Api_Token"], $_SESSION["Refresh_Token"]);
        if (PHP_SAPI !== "cli" && !isset($_GET["loginbypass"]) && $endpoint !== "/login" && $endpoint !== "/token-refresh") {
            if (!headers_sent()) { header("Location: " . APP_SITE_URL . "/login.php"); }
            exit;
        }
    };

    $url = APP_API_BASE_URL . $endpoint;
    if ($method === 'GET' && !empty($data)) { $url .= '?' . http_build_query($data); }

    $response = $requestFn($url, $method, $data, $token);
    if (isset($response["error"])) { return $response; }
    $applyTokens($response);

    // Access token expired: try once with refresh token.
    $shouldTryRefresh = (($response["code"] ?? 0) === 401)
        && !empty($_SESSION["Refresh_Token"])
        && $endpoint !== "/login"
        && $endpoint !== "/token-refresh"
        && !empty($token);

    if ($shouldTryRefresh) {
        $refreshResp = $requestFn(APP_API_BASE_URL . "/token-refresh", "POST", [
            "RefreshToken" => $_SESSION["Refresh_Token"]
        ], NULL);

        if (($refreshResp["code"] ?? 0) === 200) {
            $refreshData = $refreshResp["data"] ?? [];
            if (!empty($refreshData["AccessToken"])) { $_SESSION["Api_Token"] = $refreshData["AccessToken"]; }
            if (!empty($refreshData["RefreshToken"])) { $_SESSION["Refresh_Token"] = $refreshData["RefreshToken"]; }
            $retryToken = $_SESSION["Api_Token"] ?? NULL;
            $response = $requestFn($url, $method, $data, $retryToken);
            if (isset($response["error"])) { return $response; }
            $applyTokens($response);
            if (($response["code"] ?? 0) === 401) { $forceLogoutRedirect(); }
            return $response;
        }

        $forceLogoutRedirect();
    }

    if (($response["code"] ?? 0) === 401 && $endpoint !== "/login" && $endpoint !== "/token-refresh") {
        $forceLogoutRedirect();
    }

    return $response;
}


function encrypt($data) 
{
	$key = APP_CRYPTO_KEY; 
	$iv = APP_CRYPTO_IV;
    return base64_encode(openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv));
}

function decrypt($data) 
{
    $key = APP_CRYPTO_KEY; 
    $iv = APP_CRYPTO_IV;
    $decrypted = openssl_decrypt(base64_decode($data), 'AES-256-CBC', $key, 0, $iv);
    return $decrypted !== false ? $decrypted : false;
}

////////// HATA AYIKLAMA FONKSÄ°YONU //////////
function hataAyikla($metin)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, APP_DEBUG_HOOK_URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $metin);

	$headers = array();
	$headers[] = 'Content-Type: application/x-www-form-urlencoded';
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		echo 'Error:' . curl_error($ch);
	}
	curl_close($ch);

}
////////// HATA AYIKLAMA FONKSÄ°YONU //////////

function rakamlarGizli()
{
    return ((int)($_SESSION['rakamlar'] ?? 1) === 1);
}

function para($miktar, $birim = "TL", $ondalik = 2)
{
    if (!is_numeric($miktar)) { return "Gecersiz miktar"; }

    if (rakamlarGizli())
    {
        $miktarStr = (string)floor((float)$miktar);
        $yildizSayisi = max(1, strlen(str_replace("-", "", $miktarStr)));
        $yildizli = str_repeat("*", $yildizSayisi);
        $yildizliFormatli = implode('.', str_split(strrev($yildizli), 3));
        return strrev($yildizliFormatli);
    }

    return number_format($miktar, $ondalik, ',', '.');
}


?>
