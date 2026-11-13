<?php
header("Content-Type: application/json; charset=UTF-8");
session_start();
$ENV = parse_ini_file(__DIR__ . "/../../.FINANS", false, INI_SCANNER_RAW) ?: [];
if (empty($ENV) || empty($ENV["DB_HOST"]) || empty($ENV["DB_NAME"]) || !array_key_exists("DB_USER", $ENV)) {
	http_response_code(503);
	echo json_encode(["code" => 503, "message" => "Kurulum tamamlanmamış. /install dizinini çalıştırın."], JSON_UNESCAPED_UNICODE);
	exit;
}
define("FIN_DB_HOST", $ENV["DB_HOST"] ?? "localhost");
define("FIN_DB_PORT", $ENV["DB_PORT"] ?? "3306");
define("FIN_DB_NAME", $ENV["DB_NAME"] ?? "finans");
define("FIN_DB_CHARSET", $ENV["DB_CHARSET"] ?? "utf8mb4");
define("FIN_DB_USER", $ENV["DB_USER"] ?? "root");
define("FIN_DB_PASS", $ENV["DB_PASS"] ?? "");
define("FIN_API_BEARER_TOKEN", $ENV["API_BEARER_TOKEN"] ?? "");
define("FIN_CRYPTO_KEY", $ENV["CRYPTO_KEY"] ?? "");
define("FIN_CRYPTO_IV", $ENV["CRYPTO_IV"] ?? "");
define("FIN_JWT_SECRET", $ENV["JWT_SECRET"] ?? "change_this_jwt_secret");
define("FIN_JWT_ACCESS_TTL", max(60, (int)($ENV["JWT_ACCESS_TTL_SECONDS"] ?? 300)));
define("FIN_JWT_REFRESH_TTL", max(60, (int)($ENV["JWT_REFRESH_TTL_SECONDS"] ?? 300)));
define("FIN_JWT_RENEW_WINDOW", max(30, (int)($ENV["JWT_RENEW_WINDOW_SECONDS"] ?? 300)));
define("FIN_JWT_ALLOW_LEGACY_BEARER", (string)($ENV["JWT_ALLOW_LEGACY_BEARER"] ?? "0") === "1");

try {
	$dsn = "mysql:host=" . FIN_DB_HOST . ";port=" . FIN_DB_PORT . ";dbname=" . FIN_DB_NAME . ";charset=" . FIN_DB_CHARSET;
	$db = new PDO($dsn, FIN_DB_USER, FIN_DB_PASS);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$installCheck = $db->prepare("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = :db");
	$installCheck->execute([":db" => FIN_DB_NAME]);
	$tableCount = (int)($installCheck->fetchColumn() ?? 0);
	if ($tableCount <= 0) {
		http_response_code(503);
		echo json_encode(["code" => 503, "message" => "Kurulum tamamlanmamış. Veritabanı boş."], JSON_UNESCAPED_UNICODE);
		exit;
	}
} catch (PDOException $e) {
	http_response_code(503);
	echo json_encode(["code" => 503, "message" => "Kurulum tamamlanmamış. Veritabanı bulunamadı veya erişilemiyor."], JSON_UNESCAPED_UNICODE);
	exit;
}

$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? $_GET['path'] : '';
switch ($path) 
{
	case "info":
	
		responseCode(200, NULL, $_SESSION);	
	break;
	case 'login':
		if($requestMethod === 'POST') 
		{
			floodControl();
			$input = json_decode(file_get_contents("php://input"), true);
			$user_email = $input['Email'] ?? false;
			$user_password = $input['Password'] ?? false;
			if(!$user_email || !$user_password) { responseCode(400, "E-posta veya şifre yanlış."); }
			
			$sorgu = $db->prepare("SELECT * FROM user WHERE Email = :Email LIMIT 1");
			$sorgu->execute([":Email" => $user_email]);
			$giris = $sorgu->fetch(PDO::FETCH_ASSOC);
				
			if (!$giris) {
				responseCode(404, "Kullanıcı bilgileri hatalı.");
			}

			$storedPassword = (string)($giris["Password"] ?? "");
			if ($storedPassword === "" || !password_verify((string)$user_password, $storedPassword)) {
				responseCode(404, "Kullanıcı bilgileri hatalı.");
			}

			$tokens = issueJwtTokens([
				"UserId" => $giris["UserId"] ?? null,
				"FirstName" => $giris["FirstName"] ?? null,
				"LastName" => $giris["LastName"] ?? null,
				"Email" => $giris["Email"] ?? null,
			]);
			responseCode(200, NULL, [
				"UserId" => $giris["UserId"] ?? null,
				"FirstName" => $giris["FirstName"] ?? null,
				"LastName" => $giris["LastName"] ?? null,
				"Email" => $giris["Email"] ?? null,
				"Api_Token" => $tokens["AccessToken"],
				"AccessToken" => $tokens["AccessToken"],
				"RefreshToken" => $tokens["RefreshToken"],
				"ExpiresIn" => FIN_JWT_ACCESS_TTL,
			]);
		}
		else 
		{
			responseCode(405);
		}
		break;	

		case 'token-refresh':
			if($requestMethod === 'POST')
			{
				$input = json_decode(file_get_contents("php://input"), true);
				$refreshToken = $input['RefreshToken'] ?? false;
				if(!$refreshToken) { responseCode(400, "Refresh token eksik."); }

				$payload = verifyJwtToken($refreshToken, "refresh");
				if (!$payload) { responseCode(401, "Oturum süresi doldu."); }

				$tokens = issueJwtTokens([
					"UserId" => $payload["uid"] ?? null,
					"FirstName" => $payload["fn"] ?? null,
					"LastName" => $payload["ln"] ?? null,
					"Email" => $payload["email"] ?? null,
				]);

				responseCode(200, NULL, [
					"Api_Token" => $tokens["AccessToken"],
					"AccessToken" => $tokens["AccessToken"],
					"RefreshToken" => $tokens["RefreshToken"],
					"ExpiresIn" => FIN_JWT_ACCESS_TTL,
				]);
			}
			else
			{
				responseCode(405);
			}
		break;
	
		case 'gelirler':
		validateToken();
		if ($requestMethod === 'GET') 
		{
			$AssetsId = $_GET['AssetsId'] ?? false;
			$AccountId = $_GET['AccountId'] ?? false;
			$search = $_GET['search'] ?? false;
			$CategoryId = $_GET['CategoryId'] ?? false;
			$orderkey = $_GET['orderkey'] ?? false;
			$ordertype = $_GET['ordertype'] ?? false;
			$baslangictarihi = !empty($_GET['baslangictarihi']) ? $_GET['baslangictarihi'] : null;
			$bitistarihi = !empty($_GET['bitistarihi']) ? $_GET['bitistarihi'] : null;
			$limit = $_GET['limit'] ?? false; // legacy
			$start = isset($_GET['start']) ? (int)$_GET['start'] : null;
			$length = isset($_GET['length']) ? (int)$_GET['length'] : null;

			// Base FROM/WHERE (used for counts + sums).
			$fromSql = "
FROM assets
LEFT JOIN category ON assets.CategoryId = category.CategoryId
LEFT JOIN accounts ON assets.AccountId = accounts.AccountId
LEFT JOIN (
	SELECT MovementId, COUNT(*) AS cnt
	FROM movement_files
	WHERE MovementType = 1
	GROUP BY MovementId
) mp ON mp.MovementId = assets.AssetsId
LEFT JOIN asgari_ucret AS eski ON assets.Date BETWEEN eski.baslangic_tarihi AND eski.bitis_tarihi
LEFT JOIN asgari_ucret AS guncel ON CURDATE() BETWEEN guncel.baslangic_tarihi AND guncel.bitis_tarihi
WHERE 1=1";

			$params = [];

			if ($AssetsId) {
				$fromSql .= " AND assets.AssetsId = :AssetsId";
				$params[":AssetsId"] = $AssetsId;
			}
			if ($AccountId) {
				$fromSql .= " AND assets.AccountId = :AccountId";
				$params[":AccountId"] = (int)$AccountId;
			}
			if ($baslangictarihi && $bitistarihi) {
				$startDt = (strlen(trim((string)$baslangictarihi)) === 10) ? trim((string)$baslangictarihi) . " 00:00:00" : trim((string)$baslangictarihi);
				$endDt = (strlen(trim((string)$bitistarihi)) === 10) ? trim((string)$bitistarihi) . " 23:59:59" : trim((string)$bitistarihi);
				$fromSql .= " AND assets.Date >= :baslangictarihi AND assets.Date <= :bitistarihi";
				$params[":baslangictarihi"] = $startDt;
				$params[":bitistarihi"] = $endDt;
			}
			if ($CategoryId) {
				$fromSql .= " AND assets.CategoryId = :CategoryId";
				$params[":CategoryId"] = $CategoryId;
			}

			$fromNoSearch = $fromSql;
			$paramsNoSearch = $params;

			if ($search) {
				$fromSql .= " AND (assets.Title LIKE :search OR assets.Description LIKE :search)";
				$params[":search"] = '%'.$search.'%';
			}

			// Counts (DataTables-friendly).
			$stmt = $db->prepare("SELECT COUNT(*) AS cnt $fromNoSearch");
			$stmt->execute($paramsNoSearch);
			$recordsTotal = (int)($stmt->fetchColumn() ?? 0);

			$stmt = $db->prepare("SELECT COUNT(*) AS cnt $fromSql");
			$stmt->execute($params);
			$recordsFiltered = (int)($stmt->fetchColumn() ?? 0);

			// Sums across the filtered dataset WITHOUT pagination.
			$stmt = $db->prepare("
SELECT 
	COALESCE(SUM(assets.Amount), 0) AS Toplam,
	COALESCE(SUM((assets.Amount / CAST(REPLACE(REPLACE(eski.asgari_ucret, '.', ''), ',', '.') AS DECIMAL(12,2))) * CAST(REPLACE(REPLACE(guncel.asgari_ucret, '.', ''), ',', '.') AS DECIMAL(12,2))), 0) AS Enflasyon_Toplam
$fromSql
");
			$stmt->execute($params);
			$sumRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: ["Toplam" => 0, "Enflasyon_Toplam" => 0];

			// Ordering (whitelist to avoid SQL injection).
			$allowedOrder = [
				"Date" => "assets.Date",
				"Title" => "assets.Title",
				"Amount" => "assets.Amount",
				"CategoryName" => "category.CategoryName",
				"Enflasyon" => "Enflasyon",
				"AssetsId" => "assets.AssetsId",
			];
			$orderCol = $allowedOrder[$orderkey] ?? "assets.Date";
			$orderDir = (strtoupper((string)$ordertype) === "ASC") ? "ASC" : "DESC";

			// Pagination: prefer DataTables start/length; keep legacy limit for old screens.
			$offset = null;
			$rows = null;
			if ($length !== null) {
				if ($length > 0) {
					$offset = max(0, (int)($start ?? 0));
					$rows = min(500, (int)$length);
				}
			} elseif ($limit !== false) {
				$rows = min(500, max(1, (int)$limit));
				$offset = 0;
			}

			$listSql = "
SELECT 
	assets.*, 
	CASE WHEN assets.Date > NOW() THEN 1 ELSE 0 END AS Gerceklesmemis, 
	category.CategoryId AS category_id, 
	category.CategoryName,
	accounts.AccountId AS account_id,
	accounts.AccountName,
	accounts.AccountType,
	(assets.Amount / CAST(REPLACE(REPLACE(eski.asgari_ucret, '.', ''), ',', '.') AS DECIMAL(12,2))) * CAST(REPLACE(REPLACE(guncel.asgari_ucret, '.', ''), ',', '.') AS DECIMAL(12,2)) AS Enflasyon,
	COALESCE(mp.cnt, 0) AS PhotoCount
$fromSql
ORDER BY $orderCol $orderDir";

			if ($rows !== null) {
				$listSql .= " LIMIT :offset, :rows";
			}

			$stmt = $db->prepare($listSql);
			foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
			if ($rows !== null) {
				$stmt->bindValue(":offset", (int)$offset, PDO::PARAM_INT);
				$stmt->bindValue(":rows", (int)$rows, PDO::PARAM_INT);
			}
			$stmt->execute();
			$list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

			responseCode(200, NULL, [
				"count" => COUNT($list),
				"recordsTotal" => $recordsTotal,
				"recordsFiltered" => $recordsFiltered,
				"total" => $sumRow["Toplam"] ?? 0,
				"enflasyon_total" => $sumRow["Enflasyon_Toplam"] ?? 0,
				"list" => $list
			]);
		}

		
		elseif ($requestMethod === 'POST') 
		{
			try 
			{
				$input = json_decode(file_get_contents("php://input"), true);
				
				$Title = !empty($input['Title']) ? $input['Title'] : responseCode(400, "Başlık eksik");
				$Date = !empty($input['Date']) ? $input['Date'] : responseCode(400, "Tarih eksik");
				$CategoryId = !empty($input['CategoryId']) ? $input['CategoryId'] : responseCode(400, "Kategori ID'si eksik");
				$AccountId = !empty($input['AccountId']) ? (int)$input['AccountId'] : null;
				$Amount = !empty($input['Amount']) ? $input['Amount'] : responseCode(400, "Tutar eksik");
				$Description = !empty($input['Description']) ? $input['Description'] : NULL;


				$kategoriler = $db->prepare("INSERT INTO assets SET 
					Title = :Title,
					Date = :Date,
					CategoryId = :CategoryId, 
					AccountId = :AccountId,
					Amount = :Amount, 
					Description = :Description
				"); 

				$kategoriler->execute(array(
					"Title" => $Title, 
					"Date" => $Date, 
					"CategoryId" => $CategoryId, 
					"AccountId" => $AccountId,
					"Amount" => $Amount, 
					"Description" => $Description
				));

				if ($kategoriler) { responseCode(200, NULL, ["AssetsId" => $db->lastInsertId()]); } 
				else { responseCode(400, "Yeni gelir eklenirken hata oluştu"); }
			}
			catch(Exception $e) 
			{
				responseCode(400, $e->getMessage());
			}
		}
		elseif ($requestMethod === 'PATCH') 
		{
			try 
			{
				$input = json_decode(file_get_contents("php://input"), true);
				
				$AssetsId = $input['AssetsId'] ?? responseCode(400, "Gelir ID'si eksik");
				$Title = $input['Title'] ?? null;
				$CategoryId = $input['CategoryId'] ?? null;
				$hasAccountId = array_key_exists('AccountId', $input);
				$AccountId = $hasAccountId ? (!empty($input['AccountId']) ? (int)$input['AccountId'] : null) : false;
				$Date = $input['Date'] ?? null;
				$Amount = $input['Amount'] ?? null;
				$Description = $input['Description'] ?? null;

				if (!$Title && !$CategoryId && !$Date && !$Amount && !$Description && !$hasAccountId) { 
					responseCode(400, "Güncelleme için en az bir alan sağlamalısınız.");
				}
					
				$fields = [];
				$plainAssetsId = decrypt($AssetsId);
				if ($plainAssetsId === false || $plainAssetsId === "") { $plainAssetsId = $AssetsId; }
				$params = [":AssetsId" => (int)$plainAssetsId];

				if ($Title) { $fields[] = "Title = :Title"; $params[":Title"] = $Title; }
				if ($CategoryId) { $fields[] = "CategoryId = :CategoryId"; $params[":CategoryId"] = $CategoryId; }
				if ($hasAccountId) { $fields[] = "AccountId = :AccountId"; $params[":AccountId"] = $AccountId; }
				if ($Date) { $fields[] = "Date = :Date"; $params[":Date"] = $Date; }
				if ($Amount) { $fields[] = "Amount = :Amount"; $params[":Amount"] = $Amount; }
				if ($Description !== null) { $fields[] = "Description = :Description"; $params[":Description"] = $Description; }

				$assetsGuncelle = "UPDATE assets SET " . implode(", ", $fields) . " WHERE AssetsId = :AssetsId";
				$assetsGuncelle = $db->prepare($assetsGuncelle);

				if ($assetsGuncelle->execute($params)) { 
					responseCode(200, NULL, ["message" => "Gelir başarıyla güncellendi."]); 
				} 
				else { 
					responseCode(400, "Gelir güncellenemedi.");
				}
			}
			catch (Exception $e) {
				responseCode(500, "Sunucu hatası: " . $e->getMessage());
			}
		}
		elseif ($requestMethod === 'DELETE') 
		{
			try 
			{
				$input = json_decode(file_get_contents("php://input"), true);
				
				$AssetsId = $input['AssetsId'] ?? responseCode(400, "Gelir ID'si eksik");
				$AssetsId = decrypt($AssetsId);

				$sorgu = $db->prepare("DELETE FROM assets WHERE AssetsId = :AssetsId");
				$sorgu->execute([":AssetsId" => $AssetsId]);

				if ($sorgu->rowCount()) { 
					responseCode(200, "Gelir başarıyla silindi."); 
				} 
				else { 
					responseCode(404, "Belirtilen ID'ye sahip bir gelir bulunamadı.");
				}
			}
			catch (Exception $e) {
				responseCode(500, "Sunucu hatası: " . $e->getMessage());
			}
		}
		else {
			responseCode(405);
		}
    break;	
	
	case 'giderler':
		validateToken();
		if ($requestMethod === 'GET')
		{
			$BillsId = $_GET['BillsId'] ?? false;
			$AccountId = $_GET['AccountId'] ?? false;
			$search = $_GET['search'] ?? false;
			$CategoryId = $_GET['CategoryId'] ?? false;
			$orderkey = $_GET['orderkey'] ?? false;
			$ordertype = $_GET['ordertype'] ?? false;
			$baslangictarihi = !empty($_GET['baslangictarihi']) ? $_GET['baslangictarihi'] : null;
			$bitistarihi = !empty($_GET['bitistarihi']) ? $_GET['bitistarihi'] : null;
			$limit = $_GET['limit'] ?? false; // legacy
			$start = isset($_GET['start']) ? (int)$_GET['start'] : null;
			$length = isset($_GET['length']) ? (int)$_GET['length'] : null;

	$fromSql = "
FROM bills
LEFT JOIN category ON bills.CategoryId = category.CategoryId
LEFT JOIN accounts ON bills.AccountId = accounts.AccountId
LEFT JOIN (
	SELECT MovementId, COUNT(*) AS cnt
	FROM movement_files
	WHERE MovementType = 2
	GROUP BY MovementId
) mp ON mp.MovementId = bills.BillsId
LEFT JOIN asgari_ucret AS eski 
    ON bills.Date >= eski.baslangic_tarihi
   AND bills.Date <  DATE_ADD(eski.bitis_tarihi, INTERVAL 1 DAY)
LEFT JOIN asgari_ucret AS guncel 
    ON CURDATE() >= guncel.baslangic_tarihi
   AND CURDATE() <= guncel.bitis_tarihi
WHERE 1=1";

			$params = [];

			if ($BillsId) {
				$fromSql .= " AND bills.BillsId = :BillsId";
				$params[":BillsId"] = $BillsId;
			}
			if ($AccountId) {
				$fromSql .= " AND bills.AccountId = :AccountId";
				$params[":AccountId"] = (int)$AccountId;
			}
			if ($baslangictarihi && $bitistarihi) {
				$startDt = (strlen(trim((string)$baslangictarihi)) === 10) ? trim((string)$baslangictarihi) . " 00:00:00" : trim((string)$baslangictarihi);
				$endDt = (strlen(trim((string)$bitistarihi)) === 10) ? trim((string)$bitistarihi) . " 23:59:59" : trim((string)$bitistarihi);
				$fromSql .= " AND bills.Date >= :baslangictarihi AND bills.Date <= :bitistarihi";
				$params[":baslangictarihi"] = $startDt;
				$params[":bitistarihi"] = $endDt;
			}
			if ($CategoryId) {
				$fromSql .= " AND bills.CategoryId = :CategoryId";
				$params[":CategoryId"] = $CategoryId;
			}

			$fromNoSearch = $fromSql;
			$paramsNoSearch = $params;

			if ($search) {
				$fromSql .= " AND (bills.Title LIKE :search OR bills.Description LIKE :search)";
				$params[":search"] = '%'.$search.'%';
			}

			$stmt = $db->prepare("SELECT COUNT(*) AS cnt $fromNoSearch");
			$stmt->execute($paramsNoSearch);
			$recordsTotal = (int)($stmt->fetchColumn() ?? 0);

			$stmt = $db->prepare("SELECT COUNT(*) AS cnt $fromSql");
			$stmt->execute($params);
			$recordsFiltered = (int)($stmt->fetchColumn() ?? 0);

			$stmt = $db->prepare("
SELECT 
	COALESCE(SUM(bills.Amount), 0) AS Toplam,
	COALESCE(SUM((bills.Amount / CAST(REPLACE(REPLACE(eski.asgari_ucret, '.', ''), ',', '.') AS DECIMAL(12,2))) * CAST(REPLACE(REPLACE(guncel.asgari_ucret, '.', ''), ',', '.') AS DECIMAL(12,2))), 0) AS Enflasyon_Toplam
$fromSql
");
			$stmt->execute($params);
			$sumRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: ["Toplam" => 0, "Enflasyon_Toplam" => 0];

			$allowedOrder = [
				"Date" => "bills.Date",
				"Title" => "bills.Title",
				"Amount" => "bills.Amount",
				"CategoryName" => "category.CategoryName",
				"Enflasyon" => "Enflasyon",
				"BillsId" => "bills.BillsId",
			];
			$orderCol = $allowedOrder[$orderkey] ?? "bills.Date";
			$orderDir = (strtoupper((string)$ordertype) === "ASC") ? "ASC" : "DESC";

			$offset = null;
			$rows = null;
			if ($length !== null) {
				if ($length > 0) {
					$offset = max(0, (int)($start ?? 0));
					$rows = min(500, (int)$length);
				}
			} elseif ($limit !== false) {
				$rows = min(500, max(1, (int)$limit));
				$offset = 0;
			}

	$listSql = "
SELECT 
	bills.*, 
	CASE WHEN bills.Date > NOW() THEN 1 ELSE 0 END AS Gerceklesmemis, 
	category.CategoryId AS category_id, 
	category.CategoryName,
	accounts.AccountId AS account_id,
	accounts.AccountName,
	accounts.AccountType,
	(bills.Amount / CAST(REPLACE(REPLACE(eski.asgari_ucret, '.', ''), ',', '.') AS DECIMAL(12,2))) * CAST(REPLACE(REPLACE(guncel.asgari_ucret, '.', ''), ',', '.') AS DECIMAL(12,2)) AS Enflasyon,
	COALESCE(mp.cnt, 0) AS PhotoCount
$fromSql
ORDER BY $orderCol $orderDir";

			if ($rows !== null) {
				$listSql .= " LIMIT :offset, :rows";
			}

			$stmt = $db->prepare($listSql);
			foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
			if ($rows !== null) {
				$stmt->bindValue(":offset", (int)$offset, PDO::PARAM_INT);
				$stmt->bindValue(":rows", (int)$rows, PDO::PARAM_INT);
			}
			$stmt->execute();
			$list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

			responseCode(200, NULL, [
				"count" => COUNT($list),
				"recordsTotal" => $recordsTotal,
				"recordsFiltered" => $recordsFiltered,
				"total" => $sumRow["Toplam"] ?? 0,
				"enflasyon_total" => $sumRow["Enflasyon_Toplam"] ?? 0,
				"list" => $list
			]);
		}
		
		elseif ($requestMethod === 'POST') 
		{
			try 
			{
				$input = json_decode(file_get_contents("php://input"), true);
				
				$Title = !empty($input['Title']) ? $input['Title'] : responseCode(400, "Başlık eksik");
				$Date = !empty($input['Date']) ? $input['Date'] : responseCode(400, "Tarih eksik");
				$CategoryId = !empty($input['CategoryId']) ? $input['CategoryId'] : responseCode(400, "Kategori ID'si eksik");
				$AccountId = !empty($input['AccountId']) ? (int)$input['AccountId'] : null;
				$Amount = !empty($input['Amount']) ? $input['Amount'] : responseCode(400, "Tutar eksik");
				$Description = !empty($input['Description']) ? $input['Description'] : NULL;


				$kategoriler = $db->prepare("INSERT INTO bills SET 
					Title = :Title,
					Date = :Date,
					CategoryId = :CategoryId, 
					AccountId = :AccountId,
					Amount = :Amount, 
					Description = :Description
				"); 

				$kategoriler->execute(array(
					"Title" => $Title, 
					"Date" => $Date, 
					"CategoryId" => $CategoryId, 
					"AccountId" => $AccountId,
					"Amount" => $Amount, 
					"Description" => $Description
				));

				if ($kategoriler) { responseCode(200, NULL, ["BillsId" => $db->lastInsertId()]); } 
				else { responseCode(400, "Yeni gelir eklenirken hata oluştu"); }
			}
			catch(Exception $e) 
			{
				responseCode(400, $e->getMessage());
			}
		}
		
		elseif ($requestMethod === 'PATCH') 
		{
			try 
			{
				$input = json_decode(file_get_contents("php://input"), true);
				
				$BillsId = $input['BillsId'] ?? responseCode(400, "Gider ID'si eksik");
				$Title = $input['Title'] ?? null;
				$CategoryId = $input['CategoryId'] ?? null;
				$hasAccountId = array_key_exists('AccountId', $input);
				$AccountId = $hasAccountId ? (!empty($input['AccountId']) ? (int)$input['AccountId'] : null) : false;
				$Date = $input['Date'] ?? null;
				$Amount = $input['Amount'] ?? null;
				$Description = $input['Description'] ?? null;

				if (!$Title && !$CategoryId && !$Date && !$Amount && !$Description && !$hasAccountId) { 
					responseCode(400, "Güncelleme için en az bir alan sağlamalısınız.");
				}

				$fields = [];
				$plainBillsId = decrypt($BillsId);
				if ($plainBillsId === false || $plainBillsId === "") { $plainBillsId = $BillsId; }
				$params = [":BillsId" => (int)$plainBillsId];

				if ($Title) { $fields[] = "Title = :Title"; $params[":Title"] = $Title; }
				if ($CategoryId) { $fields[] = "CategoryId = :CategoryId"; $params[":CategoryId"] = $CategoryId; }
				if ($hasAccountId) { $fields[] = "AccountId = :AccountId"; $params[":AccountId"] = $AccountId; }
				if ($Date) { $fields[] = "Date = :Date"; $params[":Date"] = $Date; }
				if ($Amount) { $fields[] = "Amount = :Amount"; $params[":Amount"] = $Amount; }
				if ($Description !== null) { $fields[] = "Description = :Description"; $params[":Description"] = $Description; }

				$updateQuery = "UPDATE bills SET " . implode(", ", $fields) . " WHERE BillsId = :BillsId";
				$stmt = $db->prepare($updateQuery);

				if ($stmt->execute($params)) { 
					responseCode(200, NULL, ["message" => "Gider başarıyla güncellendi."]); 
				} 
				else { 
					responseCode(400, "Gider güncellenemedi.");
				}
			}
			catch (Exception $e) {
				responseCode(500, "Sunucu hatası: " . $e->getMessage());
			}
		}
		elseif ($requestMethod === 'DELETE') 
		{
			try 
			{
				$input = json_decode(file_get_contents("php://input"), true);
				
				$BillsId = $input['BillsId'] ?? responseCode(400, "Gider ID'si eksik");
				$BillsId = decrypt($BillsId);

				$sorgu = $db->prepare("DELETE FROM bills WHERE BillsId = :BillsId");
				$sorgu->execute([":BillsId" => $BillsId]);

				if ($sorgu->rowCount()) { 
					responseCode(200, "Gider başarıyla silindi."); 
				} 
				else { 
					responseCode(404, "Belirtilen ID'ye sahip bir gider bulunamadı.");
				}
			}
			catch (Exception $e) {
				responseCode(500, "Sunucu hatası: " . $e->getMessage());
			}
		}
		else 
		{
			responseCode(405);
		}
    break;
	
	case 'kategoriler':
		validateToken();
		if ($requestMethod === 'GET') 
		{
			$CategoryId  = $_GET['CategoryId'] ?? false;
			$Type = $_GET['Type'] ?? false;
			$orderkey = $_GET['orderkey'] ?? false;
			$ordertype = $_GET['ordertype'] ?? false;

			$kategoriler = "
				SELECT 
					c.CategoryId,
					c.CategoryName,
					c.Type,
					CASE 
						WHEN c.Type = 1 THEN (SELECT COUNT(*) FROM assets a WHERE a.CategoryId = c.CategoryId)
						WHEN c.Type = 2 THEN (SELECT COUNT(*) FROM bills b WHERE b.CategoryId = c.CategoryId)
						ELSE 0
					END AS Total_Count,
					CASE 
						WHEN c.Type = 1 THEN (SELECT COALESCE(SUM(a.Amount), 0) FROM assets a WHERE a.CategoryId = c.CategoryId)
						WHEN c.Type = 2 THEN (SELECT COALESCE(SUM(b.Amount), 0) FROM bills b WHERE b.CategoryId = c.CategoryId)
						ELSE 0
					END AS Total_Amount
				FROM category c
				WHERE 1=1
			";
			
			$parametre = [];

			if ($CategoryId) { $kategoriler .= " AND c.CategoryId = :CategoryId"; $parametre[":CategoryId"] = $CategoryId; }        
			if ($Type) { $kategoriler .= " AND c.Type = :Type"; $parametre[":Type"] = $Type; }

			$allowedOrderCols = [
				"CategoryId" => "c.CategoryId",
				"CategoryName" => "c.CategoryName",
				"Type" => "c.Type",
				"Total_Count" => "Total_Count",
				"Total_Amount" => "Total_Amount",
			];
			if ($orderkey && isset($allowedOrderCols[$orderkey])) {
				$orderCol = $allowedOrderCols[$orderkey];
				$orderDir = (strtoupper((string)$ordertype) === "DESC") ? "DESC" : "ASC";
				$kategoriler .= " ORDER BY $orderCol $orderDir";
			}

			$listeSorgusu = $db->prepare($kategoriler);
			$listeSorgusu->execute($parametre);
			$listeSonucu = $listeSorgusu->fetchAll(PDO::FETCH_ASSOC);

			if (!$listeSonucu) { $listeSonucu = []; }
			responseCode(200, NULL, [
				"count" => COUNT($listeSonucu),
				"list" => $listeSonucu
			]);
		}


		
		elseif ($requestMethod === 'POST') 
		{
			try 
			{
				$input = json_decode(file_get_contents("php://input"), true);
				
				$CategoryName = !empty($input['CategoryName']) ? $input['CategoryName'] : responseCode(400, "Kategori adı eksik");
				$Type = $input['Type'] ?? responseCode(400, "Kategori türü seçin.");
				
				$kategoriler = $db->prepare("INSERT INTO category SET CategoryName = :CategoryName, Type = :Type");
				$kategoriler->execute(array("CategoryName" => $CategoryName, "Type" => $Type));

				if ($kategoriler) 
				{
					 responseCode(200, NULL, ["CategoryId" => $db->lastInsertId()]);
				} else {
					responseCode(400, "Yeni kategori eklenirken hata oluştu");
				}
			}
			catch(Exception $e) 
			{
				responseCode(400, $e->getMessage());
			}
		}
		
		elseif ($requestMethod === 'PATCH') 
		{
			try 
			{
				$input = json_decode(file_get_contents("php://input"), true);
				
				$CategoryId = $input['CategoryId'] ?? responseCode(400, "Kategori ID'si eksik");
				$CategoryName = $input['CategoryName'] ?? null;
				$Type = $input['Type'] ?? null;

				if (!$CategoryName && !$Type) { responseCode(400, "Güncelleme için en az bir alan sağlamalısınız."); }

				$fields = [];
				$params = [":CategoryId" => decrypt($CategoryId)];

				if ($CategoryName) { $fields[] = "CategoryName = :CategoryName"; $params[":CategoryName"] = $CategoryName; }
				if ($Type) { $fields[] = "Type = :Type"; $params[":Type"] = $Type; }

				$kategoriGuncelle = "UPDATE category SET " . implode(", ", $fields) . " WHERE CategoryId = :CategoryId";
				$kategoriGuncelle = $db->prepare($kategoriGuncelle);

				if($kategoriGuncelle->execute($params)) { responseCode(200, NULL, ["message" => "Kategori başarıyla güncellendi."]); } 
				else { responseCode(400, "Kategori güncellenemedi."); }
			}
			catch (Exception $e) {
				responseCode(500, "Sunucu hatası: " . $e->getMessage());
			}
		}
		
		else 
		{
			responseCode(405);
		}
    break;
	
	case 'rapor':
		validateToken();
		if ($requestMethod === 'GET') 
		{
			$baslangictarihi = $_GET['baslangictarihi'] ?? false;
			$bitistarihi = $_GET['bitistarihi'] ?? false;
			$parametre = [];

			$tarihKosuluGelir = "1=1";
			$tarihKosuluGider = "1=1";
			if ($baslangictarihi && $bitistarihi) {
				$startDt = (strlen(trim((string)$baslangictarihi)) === 10) ? trim((string)$baslangictarihi) . " 00:00:00" : trim((string)$baslangictarihi);
				$endDt = (strlen(trim((string)$bitistarihi)) === 10) ? trim((string)$bitistarihi) . " 23:59:59" : trim((string)$bitistarihi);
				$tarihKosuluGelir = "assets.Date >= :baslangictarihi AND assets.Date <= :bitistarihi";
				$tarihKosuluGider = "bills.Date >= :baslangictarihi AND bills.Date <= :bitistarihi";
				$parametre[":baslangictarihi"] = $startDt;
				$parametre[":bitistarihi"] = $endDt;
			}

			$gelirSorgu = $db->prepare("SELECT SUM(Amount) AS Gelir FROM assets WHERE $tarihKosuluGelir");
			$gelirSorgu->execute($parametre);
			$genelGelirSonucu = $gelirSorgu->fetch(PDO::FETCH_ASSOC);

			$giderSorgu = $db->prepare("SELECT SUM(Amount) AS Gider FROM bills WHERE $tarihKosuluGider");
			$giderSorgu->execute($parametre);
			$genelGiderSonucu = $giderSorgu->fetch(PDO::FETCH_ASSOC);

			// Kategori Bazlı Gider Sorgusu
			$kategoriBazliSorguGiderler = $db->prepare("SELECT bills.CategoryId, SUM(bills.Amount) AS ToplamGider, category.CategoryName as CategoryName FROM bills INNER JOIN category ON bills.CategoryId = category.CategoryId WHERE $tarihKosuluGider GROUP BY bills.CategoryId ORDER BY CategoryName ASC");
			$kategoriBazliSorguGiderler->execute($parametre);
			$giderKategorileri = $kategoriBazliSorguGiderler->fetchAll(PDO::FETCH_ASSOC);

			// Kategori Bazlı Gelir Sorgusu
			$kategoriBazliSorguGelirler = $db->prepare("SELECT assets.CategoryId, SUM(assets.Amount) AS ToplamGelir, category.CategoryName as CategoryName FROM assets INNER JOIN category ON assets.CategoryId = category.CategoryId WHERE $tarihKosuluGelir GROUP BY assets.CategoryId ORDER BY CategoryName ASC");
			$kategoriBazliSorguGelirler->execute($parametre);
			$gelirKategorileri = $kategoriBazliSorguGelirler->fetchAll(PDO::FETCH_ASSOC);

			$genelGelir = $genelGelirSonucu['Gelir'] ?? 0;
			$genelGider = $genelGiderSonucu['Gider'] ?? 0;
			$mevcutPara = $genelGelir - $genelGider;

			responseCode(200, NULL, [
				"toplam_gelir" => $genelGelir,
				"toplam_gider" => $genelGider,
				"mevcut_durum" => $mevcutPara,
				"gider_kategorileri" => $giderKategorileri,
				"gelir_kategorileri" => $gelirKategorileri
			]);
		}
		else 
		{
			responseCode(405);
		}
    break;

	case 'para-detay':
		validateToken();
		if ($requestMethod === 'GET')
		{
			$amount = $_GET['amount'] ?? null;
			$date = !empty($_GET['date']) ? $_GET['date'] : null;
			$context = $_GET['context'] ?? 'generic';

			if (!is_numeric($amount)) {
				responseCode(400, "Tutar eksik veya gecersiz.");
			}

			$amount = (float)$amount;

			$parseMoneyValue = function($rawValue) {
				$value = trim((string)$rawValue);
				if ($value === '') {
					return null;
				}

				$normalized = str_replace('.', '', $value);
				$normalized = str_replace(',', '.', $normalized);

				return is_numeric($normalized) ? (float)$normalized : null;
			};

			$getAsgariByDate = function(string $targetDate) use ($db, $parseMoneyValue) {
				$stmt = $db->prepare("
					SELECT asgari_ucret, baslangic_tarihi, bitis_tarihi
					FROM asgari_ucret
					WHERE :targetDate BETWEEN baslangic_tarihi AND bitis_tarihi
					ORDER BY id DESC
					LIMIT 1
				");
				$stmt->execute([":targetDate" => $targetDate]);
				$row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
				$parsedAmount = $row ? $parseMoneyValue($row["asgari_ucret"] ?? null) : null;
				if (!$row || $parsedAmount === null) {
					return null;
				}
				return [
					"amount" => $parsedAmount,
					"start" => (string)($row["baslangic_tarihi"] ?? ""),
					"end" => (string)($row["bitis_tarihi"] ?? ""),
				];
			};

			$getRatesByDate = function(string $targetDate) use ($db) {
				$stmt = $db->prepare("
					SELECT usd_forex_buying, eur_forex_buying
					FROM tcmb_fx_rates
					WHERE rate_date <= :targetDate
					ORDER BY rate_date DESC
					LIMIT 1
				");
				$stmt->execute([":targetDate" => $targetDate]);
				return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
			};

			$isMovement = ($context === 'movement' && !empty($date));
			$currentAsgari = $getAsgariByDate(date('Y-m-d'));
			$asgariValue = "-";

			if ($isMovement) {
				$historicalAsgari = $getAsgariByDate($date);
				if ($historicalAsgari && !empty($historicalAsgari["amount"]) && $currentAsgari && !empty($currentAsgari["amount"])) {
					$currentEquivalent = ($amount / (float)$historicalAsgari["amount"]) * (float)$currentAsgari["amount"];
					$asgariValue = number_format($currentEquivalent, 2, ',', '.') . " TL";
				}
			}

			$usdValue = "-";
			$eurValue = "-";

			$currentRates = $getRatesByDate(date('Y-m-d'));
			if ($currentRates) {
				$usdToday = (float)($currentRates["usd_forex_buying"] ?? 0);
				$eurToday = (float)($currentRates["eur_forex_buying"] ?? 0);

				if ($isMovement) {
					$historicalRates = $getRatesByDate($date);
					if ($historicalRates) {
						$usdHist = (float)($historicalRates["usd_forex_buying"] ?? 0);
						$eurHist = (float)($historicalRates["eur_forex_buying"] ?? 0);

						if ($usdHist > 0 && $usdToday > 0) {
							$usdAmount = $amount / $usdHist;
							$usdTodayEquivalent = $usdAmount * $usdToday;
							$usdValue = number_format($usdTodayEquivalent, 2, ',', '.') . " TL";
						}
						if ($eurHist > 0 && $eurToday > 0) {
							$eurAmount = $amount / $eurHist;
							$eurTodayEquivalent = $eurAmount * $eurToday;
							$eurValue = number_format($eurTodayEquivalent, 2, ',', '.') . " TL";
						}
					}
				} else {
					if ($usdToday > 0) {
						$usdAmount = $amount / $usdToday;
						$usdValue = number_format($usdAmount, 2, ',', '.') . " USD";
					}
					if ($eurToday > 0) {
						$eurAmount = $amount / $eurToday;
						$eurValue = number_format($eurAmount, 2, ',', '.') . " EUR";
					}
				}
			}

			$items = [
				[
					"label" => "Tutar",
					"value" => number_format($amount, 2, ',', '.') . " TL",
				],
			];

			if ($isMovement) {
				$items[] = [
					"label" => "Asgari Ücret",
					"value" => $asgariValue,
				];
			}

			$items[] = [
				"label" => "EUR",
				"value" => $eurValue,
			];
			$items[] = [
				"label" => "USD",
				"value" => $usdValue,
			];

			$payload = [
				"title" => "Karşılıklar",
				"items" => $items,
				"footer" => "",
			];

			responseCode(200, null, $payload);
		}
		else
		{
			responseCode(405);
		}
	break;

	case 'istatistikler':
		validateToken();
		if ($requestMethod === 'GET')
		{
			$baslangictarihi = !empty($_GET['baslangictarihi']) ? $_GET['baslangictarihi'] : null;
			$bitistarihi = !empty($_GET['bitistarihi']) ? $_GET['bitistarihi'] : null;

			$params = [];
			$whereAssets = "1=1";
			$whereBills = "1=1";
			if ($baslangictarihi && $bitistarihi) {
				$startDt = (strlen(trim((string)$baslangictarihi)) === 10) ? trim((string)$baslangictarihi) . " 00:00:00" : trim((string)$baslangictarihi);
				$endDt = (strlen(trim((string)$bitistarihi)) === 10) ? trim((string)$bitistarihi) . " 23:59:59" : trim((string)$bitistarihi);
				$whereAssets = "assets.Date >= :baslangictarihi AND assets.Date <= :bitistarihi";
				$whereBills = "bills.Date >= :baslangictarihi AND bills.Date <= :bitistarihi";
				$params[":baslangictarihi"] = $startDt;
				$params[":bitistarihi"] = $endDt;
			}

			// Largest single income
			$stmt = $db->prepare("
				SELECT assets.AssetsId, assets.Title, assets.Amount, assets.Date, category.CategoryId, category.CategoryName
FROM assets
LEFT JOIN category ON assets.CategoryId = category.CategoryId
				WHERE $whereAssets
				ORDER BY assets.Amount DESC
				LIMIT 1
			");
			$stmt->execute($params);
			$maxIncome = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

			// Largest single expense
			$stmt = $db->prepare("
				SELECT bills.BillsId, bills.Title, bills.Amount, bills.Date, category.CategoryId, category.CategoryName
FROM bills
LEFT JOIN category ON bills.CategoryId = category.CategoryId
				WHERE $whereBills
				ORDER BY bills.Amount DESC
				LIMIT 1
			");
			$stmt->execute($params);
			$maxExpense = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

			// Totals
			$stmt = $db->prepare("SELECT COALESCE(SUM(assets.Amount),0) AS total FROM assets WHERE $whereAssets");
			$stmt->execute($params);
			$totalIncome = (float)($stmt->fetchColumn() ?? 0);

			$stmt = $db->prepare("SELECT COALESCE(SUM(bills.Amount),0) AS total FROM bills WHERE $whereBills");
			$stmt->execute($params);
			$totalExpense = (float)($stmt->fetchColumn() ?? 0);

			$net = $totalIncome - $totalExpense;
			$savingsRate = ($totalIncome > 0) ? ($net / $totalIncome) : 0;

			// Top categories by amount
			$stmt = $db->prepare("
				SELECT category.CategoryId, category.CategoryName, SUM(assets.Amount) AS Total
				FROM assets
				INNER JOIN category ON assets.CategoryId = category.CategoryId
				WHERE $whereAssets
				GROUP BY category.CategoryId
				ORDER BY Total DESC
				LIMIT 12
			");
			$stmt->execute($params);
			$topIncomeCats = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

			$stmt = $db->prepare("
				SELECT category.CategoryId, category.CategoryName, SUM(bills.Amount) AS Total
				FROM bills
				INNER JOIN category ON bills.CategoryId = category.CategoryId
				WHERE $whereBills
				GROUP BY category.CategoryId
				ORDER BY Total DESC
				LIMIT 12
			");
			$stmt->execute($params);
			$topExpenseCats = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

			// Expenses by weekday (1=Sunday..7=Saturday)
			$stmt = $db->prepare("
				SELECT DAYOFWEEK(bills.Date) AS dow, COALESCE(SUM(bills.Amount),0) AS Total
				FROM bills
				WHERE $whereBills
				GROUP BY dow
				ORDER BY Total DESC
			");
			$stmt->execute($params);
			$weekdayExpense = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

			// Monthly net (last 12 months within selected window)
			$stmt = $db->prepare("
				SELECT ym, SUM(income) AS income, SUM(expense) AS expense
				FROM (
					SELECT DATE_FORMAT(assets.Date, '%Y-%m') AS ym, SUM(assets.Amount) AS income, 0 AS expense
					FROM assets
					WHERE $whereAssets
					GROUP BY ym
					UNION ALL
					SELECT DATE_FORMAT(bills.Date, '%Y-%m') AS ym, 0 AS income, SUM(bills.Amount) AS expense
					FROM bills
					WHERE $whereBills
					GROUP BY ym
				) t
				GROUP BY ym
				ORDER BY ym DESC
				LIMIT 12
			");
			$stmt->execute($params);
			$monthly = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

			// Daily expense pulse (last 30 days, ending at selected end date or today)
			$pulseEnd = $bitistarihi ?: date('Y-m-d');
			$pulseStart = date('Y-m-d', strtotime($pulseEnd . ' -29 days'));
			$pstartDt = (strlen(trim((string)$pulseStart)) === 10) ? trim((string)$pulseStart) . " 00:00:00" : trim((string)$pulseStart);
			$pendDt = (strlen(trim((string)$pulseEnd)) === 10) ? trim((string)$pulseEnd) . " 23:59:59" : trim((string)$pulseEnd);
			$stmt = $db->prepare("
				SELECT DATE(bills.Date) AS d, COALESCE(SUM(bills.Amount),0) AS Total
				FROM bills
				WHERE bills.Date >= :pstart AND bills.Date <= :pend
				GROUP BY d
				ORDER BY d ASC
			");
			$stmt->execute([":pstart" => $pstartDt, ":pend" => $pendDt]);
			$dailyPulse = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

			// Hourly expense (0-23)
			$stmt = $db->prepare("
				SELECT HOUR(bills.Date) AS h, COALESCE(SUM(bills.Amount),0) AS Total
				FROM bills
				WHERE $whereBills
				GROUP BY h
				ORDER BY Total DESC
			");
			$stmt->execute($params);
			$hourlyExpense = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

			// Black day / Zen day (by daily sum)
			$stmt = $db->prepare("
				SELECT DATE(bills.Date) AS d, COALESCE(SUM(bills.Amount),0) AS Total
				FROM bills
				WHERE $whereBills
				GROUP BY d
				ORDER BY Total DESC
				LIMIT 1
			");
			$stmt->execute($params);
			$blackDay = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

			$stmt = $db->prepare("
				SELECT DATE(bills.Date) AS d, COALESCE(SUM(bills.Amount),0) AS Total
				FROM bills
				WHERE $whereBills
				GROUP BY d
				HAVING Total > 0
				ORDER BY Total ASC
				LIMIT 1
			");
			$stmt->execute($params);
			$zenDay = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

			// "Bu neydi şimdi?" - big expenses with missing/short description
			$stmt = $db->prepare("
				SELECT bills.BillsId, bills.Title, bills.Amount, bills.Date, category.CategoryId, category.CategoryName
				FROM bills
				INNER JOIN category ON bills.CategoryId = category.CategoryId
				WHERE $whereBills
				  AND (bills.Description IS NULL OR bills.Description = '' OR CHAR_LENGTH(bills.Description) < 6)
				ORDER BY bills.Amount DESC
				LIMIT 10
			");
			$stmt->execute($params);
			$weirdExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

			// \"Ani karar\" - late night purchases (22:00-04:59)
			$stmt = $db->prepare("
				SELECT bills.BillsId, bills.Title, bills.Amount, bills.Date, category.CategoryId, category.CategoryName
				FROM bills
				INNER JOIN category ON bills.CategoryId = category.CategoryId
				WHERE $whereBills
				  AND (HOUR(bills.Date) >= 22 OR HOUR(bills.Date) <= 4)
				ORDER BY bills.Amount DESC
				LIMIT 10
			");
			$stmt->execute($params);
			$impulseExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

			// "Sessiz düşman" - frequent category
			$stmt = $db->prepare("
				SELECT category.CategoryId, category.CategoryName, COUNT(*) AS Cnt, COALESCE(SUM(bills.Amount),0) AS Total
				FROM bills
				INNER JOIN category ON bills.CategoryId = category.CategoryId
				WHERE $whereBills
				GROUP BY category.CategoryId
				ORDER BY Cnt DESC, Total DESC
				LIMIT 1
			");
			$stmt->execute($params);
			$silentEnemy = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

			// Salary durability: time to \"burn\" the latest income with expenses after it
			$stmt = $db->prepare("
				SELECT assets.AssetsId, assets.Title, assets.Amount, assets.Date
				FROM assets
				ORDER BY assets.Date DESC
				LIMIT 1
			");
			$stmt->execute();
			$lastIncome = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

			$salaryDurability = null;
			if ($lastIncome && !empty($lastIncome["Date"]) && (float)($lastIncome["Amount"] ?? 0) > 0) {
				$incomeDate = (string)$lastIncome["Date"];
				$incomeAmount = (float)$lastIncome["Amount"];

				$stmt = $db->prepare("
					SELECT bills.Date, bills.Amount
					FROM bills
					WHERE bills.Date >= :d
					ORDER BY bills.Date ASC
					LIMIT 5000
				");
				$stmt->execute([":d" => $incomeDate]);
				$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

				$sum = 0.0;
				$burnDate = null;
				foreach ($rows as $r) {
					$sum += (float)($r["Amount"] ?? 0);
					if ($sum >= $incomeAmount) { $burnDate = (string)($r["Date"] ?? null); break; }
				}

				$days = null;
				if ($burnDate) {
					$days = (int)floor((strtotime($burnDate) - strtotime($incomeDate)) / 86400);
				}
				$salaryDurability = [
					"income_date" => $incomeDate,
					"income_amount" => $incomeAmount,
					"burn_date" => $burnDate,
					"days" => $days,
					"spent_since" => $sum,
				];
			}

			// Character & psychology (simple, explainable heuristics)
			$topExpenseShare = 0.0;
			if ($totalExpense > 0 && !empty($topExpenseCats[0]["Total"])) {
				$topExpenseShare = (float)$topExpenseCats[0]["Total"] / $totalExpense;
			}
			$character = "Dengeli";
			$psych = "Sağlıklı";
			if ($savingsRate >= 0.25) { $character = "Disiplinli"; }
			elseif ($savingsRate <= 0.05) { $character = "Dağınık"; }
			if ($topExpenseShare >= 0.55) { $psych = "Takıntılı"; }
			elseif ($topExpenseShare >= 0.35) { $psych = "Odaklı"; }

			responseCode(200, NULL, [
				"range" => ["baslangic" => $baslangictarihi, "bitis" => $bitistarihi],
				"total_income" => $totalIncome,
				"total_expense" => $totalExpense,
				"net" => $net,
				"savings_rate" => $savingsRate,
				"character" => ["label" => $character, "psychology" => $psych, "top_expense_share" => $topExpenseShare],
				"max_income" => $maxIncome,
				"max_expense" => $maxExpense,
				"black_day" => $blackDay,
				"zen_day" => $zenDay,
				"daily_pulse" => ["start" => $pulseStart, "end" => $pulseEnd, "list" => $dailyPulse],
				"hourly_expense" => $hourlyExpense,
				"weird_expenses" => $weirdExpenses,
				"impulse_expenses" => $impulseExpenses,
				"silent_enemy" => $silentEnemy,
				"salary_durability" => $salaryDurability,
				"top_income_categories" => $topIncomeCats,
				"top_expense_categories" => $topExpenseCats,
				"weekday_expense" => $weekdayExpense,
				"monthly" => $monthly,
			]);
		}
		else
		{
			responseCode(405);
		}
	break;

	case 'fotolar':
		validateToken();
		if ($requestMethod === 'GET')
		{
			$type = isset($_GET['Type']) ? (int)$_GET['Type'] : 0; // 1=gelir(assets), 2=gider(bills)
			$movementId = isset($_GET['MovementId']) ? (int)$_GET['MovementId'] : 0;
			if (!in_array($type, [1, 2], true) || $movementId <= 0) { responseCode(400, "Eksik parametre"); }

			try
			{
				$stmt = $db->prepare("
					SELECT PhotoId, MovementType, MovementId, FilePath, OriginalName, CreatedAt
					FROM movement_files
					WHERE MovementType = :t AND MovementId = :id
					ORDER BY PhotoId DESC
				");
				$stmt->execute([":t" => $type, ":id" => $movementId]);
				$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

				$base = apiBaseUrl();
				$list = [];
				foreach ($rows as $r) {
					$path = (string)($r["FilePath"] ?? "");
					$path = ltrim($path, "/");
					$ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
					$isImage = in_array($ext, ["jpg","jpeg","png","webp","gif","bmp","svg"], true);
					$isPdf = ($ext === "pdf");
					$list[] = [
						"PhotoId" => encrypt((string)($r["PhotoId"] ?? "")),
						"OriginalName" => $r["OriginalName"] ?? null,
						"CreatedAt" => $r["CreatedAt"] ?? null,
						"url" => $base . "/" . $path,
						"path" => $path,
						"ext" => $ext,
						"is_image" => $isImage,
						"is_pdf" => $isPdf,
					];
				}
				responseCode(200, NULL, ["count" => count($list), "list" => $list]);
			}
			catch (Exception $e)
			{
				responseCode(500, "Sunucu hatası: " . $e->getMessage());
			}
		}
		elseif ($requestMethod === 'POST')
		{
			$type = isset($_POST['Type']) ? (int)$_POST['Type'] : 0;
			$movementId = isset($_POST['MovementId']) ? (int)$_POST['MovementId'] : 0;
			if (!in_array($type, [1, 2], true) || $movementId <= 0) { responseCode(400, "Eksik parametre"); }

			if (empty($_FILES)) { responseCode(400, "Dosya yok"); }

			try
			{
				// Count existing photos
				$stmt = $db->prepare("SELECT COUNT(*) FROM movement_files WHERE MovementType = :t AND MovementId = :id");
				$stmt->execute([":t" => $type, ":id" => $movementId]);
				$existing = (int)($stmt->fetchColumn() ?? 0);

				$maxTotal = 5;
				$canAdd = max(0, $maxTotal - $existing);
				if ($canAdd <= 0) { responseCode(400, "Bu harekete en fazla 5 fotoğraf eklenebilir."); }

				// Normalize files from Photos[] (UI) or Photo (single)
				$files = [];
				if (!empty($_FILES['Photos']) && is_array($_FILES['Photos']['name'])) {
					$cnt = count($_FILES['Photos']['name']);
					for ($i = 0; $i < $cnt; $i++) {
						$files[] = [
							"name" => $_FILES['Photos']['name'][$i],
							"type" => $_FILES['Photos']['type'][$i],
							"tmp_name" => $_FILES['Photos']['tmp_name'][$i],
							"error" => $_FILES['Photos']['error'][$i],
							"size" => $_FILES['Photos']['size'][$i],
						];
					}
				} elseif (!empty($_FILES['Photo'])) {
					$files[] = $_FILES['Photo'];
				} else {
					// Any first file field
					foreach ($_FILES as $f) { $files[] = $f; break; }
				}

				$baseDir = apiUploadsDir();
				$relDir = "uploads/movements/" . date("Y") . "/" . date("m") . "/";
				$absDir = rtrim($baseDir, "\\/") . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relDir);

				// User requested "düz" uploads: just ensure the folder exists and is writable.
				// On Linux hosting this also prevents Apache/PHP user mismatch issues.
				if (!is_dir($absDir)) { @mkdir($absDir, 0777, true); }
				@chmod($absDir, 0777);

				$uploaded = [];
				$processed = 0;
				foreach ($files as $f) {
					if ($processed >= $canAdd) break;
					if (!is_array($f)) continue;
					if (($f["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
					if (!is_uploaded_file($f["tmp_name"] ?? "")) continue;

					// Güvenli dosya uzantısı ve MIME kontrolü
					$origName = (string)($f["name"] ?? "");
					$ext = strtolower((string)pathinfo($origName, PATHINFO_EXTENSION));
					$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'];
					if (!in_array($ext, $allowedExtensions, true)) {
						continue;
					}

					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$mime = $finfo ? finfo_file($finfo, $f["tmp_name"]) : "";
					if ($finfo) { finfo_close($finfo); }
					$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
					if (!in_array($mime, $allowedMimes, true)) {
						continue;
					}

					$safeRandom = bin2hex(random_bytes(8));
					$fileName = $type . "_" . $movementId . "_" . date("Ymd_His") . "_" . $safeRandom . "." . $ext;
					$absPath = $absDir . $fileName;
					$relPath = $relDir . $fileName;

					if (!move_uploaded_file($f["tmp_name"], $absPath)) { continue; }
					@chmod($absPath, 0644);

					$stmt = $db->prepare("
						INSERT INTO movement_files (MovementType, MovementId, FilePath, OriginalName, CreatedAt)
						VALUES (:t, :id, :p, :o, NOW())
					");
					$stmt->execute([
						":t" => $type,
						":id" => $movementId,
						":p" => $relPath,
						":o" => $origName,
					]);

					$uploaded[] = [
						"PhotoId" => encrypt((string)$db->lastInsertId()),
						"path" => ltrim($relPath, "/"),
						"url" => apiBaseUrl() . "/" . ltrim($relPath, "/"),
						"ext" => strtolower((string)pathinfo($relPath, PATHINFO_EXTENSION)),
						"is_image" => in_array(strtolower((string)pathinfo($relPath, PATHINFO_EXTENSION)), ["jpg","jpeg","png","webp","gif","bmp","svg"], true),
						"is_pdf" => (strtolower((string)pathinfo($relPath, PATHINFO_EXTENSION)) === "pdf"),
					];
					$processed++;
				}

				responseCode(200, NULL, ["uploaded" => $uploaded, "uploaded_count" => count($uploaded)]);
			}
			catch (Exception $e)
			{
				responseCode(500, "Sunucu hatası: " . $e->getMessage());
			}
		}
		elseif ($requestMethod === 'DELETE')
		{
			try
			{
				$input = json_decode(file_get_contents("php://input"), true);
				$PhotoId = $input['PhotoId'] ?? null;
				if (!$PhotoId) { responseCode(400, "Fotoğraf ID'si eksik"); }
				$photoIdInt = (int)decrypt($PhotoId);
				if ($photoIdInt <= 0) { responseCode(400, "Geçersiz fotoğraf ID"); }

				$stmt = $db->prepare("SELECT PhotoId, FilePath FROM movement_files WHERE PhotoId = :pid LIMIT 1");
				$stmt->execute([":pid" => $photoIdInt]);
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				if (!$row) { responseCode(404, "Fotoğraf bulunamadı"); }

				$path = (string)($row["FilePath"] ?? "");
				$abs = rtrim(apiUploadsDir(), "\\/") . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, ltrim($path, "/"));
				if (is_file($abs)) { @unlink($abs); }

				$stmt = $db->prepare("DELETE FROM movement_files WHERE PhotoId = :pid LIMIT 1");
				$stmt->execute([":pid" => $photoIdInt]);

				responseCode(200, "Fotoğraf silindi.");
			}
			catch (Exception $e)
			{
				responseCode(500, "Sunucu hatası: " . $e->getMessage());
			}
		}
		else
		{
			responseCode(405);
		}
	break;

	case "zaman_akisi":
		validateToken();
		if($requestMethod === "GET") 
		{
			$baslangicTarihi = $_GET['baslangictarihi'] ?? '2000-01-01'; // Varsayılan bir tarih
			$bitisTarihi = $_GET['bitistarihi'] ?? date('Y-m-d'); // Varsayılan olarak bugünün tarihi

			$startDt = (strlen(trim((string)$baslangicTarihi)) === 10) ? trim((string)$baslangicTarihi) . " 00:00:00" : trim((string)$baslangicTarihi);
			$endDt = (strlen(trim((string)$bitisTarihi)) === 10) ? trim((string)$bitisTarihi) . " 23:59:59" : trim((string)$bitisTarihi);

			// Belirlenen başlangıç tarihinden önceki toplam bakiye
			$sqlBakiyeOnce = "
				SELECT 
					(COALESCE((SELECT SUM(Amount) FROM assets WHERE Date < :baslangic), 0) 
					- COALESCE((SELECT SUM(Amount) FROM bills WHERE Date < :baslangic), 0)) AS onceki_bakiye
			";
			$stmt = $db->prepare($sqlBakiyeOnce);
			$stmt->execute([':baslangic' => $startDt]);
			$oncekiBakiye = (float)($stmt->fetchColumn() ?: 0); // Başlangıç tarihine kadar olan bakiye

			// Seçilen tarih aralığında gelir ve giderleri getir
			$sql = "
				SELECT tarih, 
					   COALESCE(gelir, 0) AS gelir, 
					   COALESCE(gider, 0) AS gider 
				FROM (
					SELECT DISTINCT Date AS tarih FROM assets WHERE Date >= :baslangic AND Date <= :bitis
					UNION 
					SELECT DISTINCT Date FROM bills WHERE Date >= :baslangic AND Date <= :bitis
				) AS tarih_tablosu
				LEFT JOIN (
					SELECT Date, SUM(Amount) AS gelir FROM assets WHERE Date >= :baslangic AND Date <= :bitis GROUP BY Date
				) AS gelirler ON tarih_tablosu.tarih = gelirler.Date
				LEFT JOIN (
					SELECT Date, SUM(Amount) AS gider FROM bills WHERE Date >= :baslangic AND Date <= :bitis GROUP BY Date
				) AS giderler ON tarih_tablosu.tarih = giderler.Date
				ORDER BY tarih ASC;
			";

			$stmt = $db->prepare($sql);
			$stmt->execute([':baslangic' => $startDt, ':bitis' => $endDt]);
			$rows = $stmt->fetchAll();

			$toplam_bakiye = $oncekiBakiye; // Önceki bakiyeyi hesaba kat
			$bakiye_listesi = [];

			foreach ($rows as $row) {
				$toplam_bakiye += $row['gelir'];
				$toplam_bakiye -= $row['gider'];

				// Sonucu diziye ekleyelim
				$bakiye_listesi[] = [
					'tarih' => $row['tarih'],
					'gelir' => $row['gelir'],
					'gider' => $row['gider'],
					'bakiye' => $toplam_bakiye
				];
			}
			responseCode(200, NULL, $bakiye_listesi);
		}

	break;
	
	case 'hesaplar':
		validateToken();
		if ($requestMethod === 'GET') 
		{
			$AccountId = $_GET['AccountId'] ?? false;
			$search = $_GET['search'] ?? false;
			$orderkey = $_GET['orderkey'] ?? false;
			$ordertype = $_GET['ordertype'] ?? false;

			$sql = "
				SELECT 
					a.AccountId,
					a.AccountName,
					a.AccountType,
					a.Description,
					a.IsDefault,
					a.CreatedAt,
					COALESCE(inc.cnt, 0) AS IncomeCount,
					COALESCE(inc.total, 0) AS TotalIncome,
					COALESCE(exp.cnt, 0) AS ExpenseCount,
					COALESCE(exp.total, 0) AS TotalExpense
				FROM accounts a
				LEFT JOIN (
					SELECT AccountId, COUNT(*) AS cnt, SUM(Amount) AS total FROM assets WHERE AccountId IS NOT NULL GROUP BY AccountId
				) inc ON inc.AccountId = a.AccountId
				LEFT JOIN (
					SELECT AccountId, COUNT(*) AS cnt, SUM(Amount) AS total FROM bills WHERE AccountId IS NOT NULL GROUP BY AccountId
				) exp ON exp.AccountId = a.AccountId
				WHERE 1=1
			";

			$params = [];
			if ($AccountId) {
				$sql .= " AND a.AccountId = :AccountId";
				$params[":AccountId"] = (int)$AccountId;
			}
			if ($search) {
				$sql .= " AND (a.AccountName LIKE :search OR a.Description LIKE :search OR a.AccountType LIKE :search)";
				$params[":search"] = '%' . $search . '%';
			}

			$allowedOrder = [
				"AccountId" => "a.AccountId",
				"AccountName" => "a.AccountName",
				"AccountType" => "a.AccountType",
				"IsDefault" => "a.IsDefault",
				"CreatedAt" => "a.CreatedAt",
			];
			if ($orderkey && isset($allowedOrder[$orderkey])) {
				$orderCol = $allowedOrder[$orderkey];
				$orderDir = (strtoupper((string)$ordertype) === "DESC") ? "DESC" : "ASC";
				$sql .= " ORDER BY $orderCol $orderDir";
			} else {
				$sql .= " ORDER BY a.IsDefault DESC, a.AccountName ASC";
			}

			$stmt = $db->prepare($sql);
			$stmt->execute($params);
			$list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

			$defaultAccount = null;
			foreach ($list as $acc) {
				if ((int)($acc["IsDefault"] ?? -1) === 1) {
					$defaultAccount = $acc;
					break;
				}
			}

			responseCode(200, NULL, [
				"count" => count($list),
				"default_account" => $defaultAccount,
				"list" => $list
			]);
		}
		elseif ($requestMethod === 'POST') 
		{
			try 
			{
				$input = json_decode(file_get_contents("php://input"), true);
				$AccountName = trim((string)($input['AccountName'] ?? ''));
				if ($AccountName === '') {
					responseCode(400, "Hesap / Kart adı zorunludur.");
				}
				$AccountType = trim((string)($input['AccountType'] ?? 'Kredi Kartı'));
				if ($AccountType === '') { $AccountType = 'Kredi Kartı'; }
				$Description = !empty($input['Description']) ? trim((string)$input['Description']) : null;
				$IsDefault = (isset($input['IsDefault']) && ((int)$input['IsDefault'] === 1 || $input['IsDefault'] === true || $input['IsDefault'] === "1")) ? 1 : -1;

				if ($IsDefault === 1) {
					$db->exec("UPDATE accounts SET IsDefault = -1");
				}

				$stmt = $db->prepare("INSERT INTO accounts (AccountName, AccountType, Description, IsDefault, CreatedAt) VALUES (:name, :type, :desc, :def, NOW())");
				$stmt->execute([
					":name" => $AccountName,
					":type" => $AccountType,
					":desc" => $Description,
					":def" => $IsDefault,
				]);

				responseCode(200, NULL, [
					"AccountId" => (int)$db->lastInsertId(),
					"message" => "Hesap başarıyla eklendi."
				]);
			}
			catch (Exception $e) 
			{
				responseCode(500, "Sunucu hatası: " . $e->getMessage());
			}
		}
		elseif ($requestMethod === 'PATCH') 
		{
			try 
			{
				$input = json_decode(file_get_contents("php://input"), true);
				$AccountId = $input['AccountId'] ?? responseCode(400, "Hesap ID'si eksik");
				$plainId = decrypt($AccountId);
				if ($plainId === false || $plainId === "") { $plainId = $AccountId; }
				$plainId = (int)$plainId;
				if ($plainId <= 0) { responseCode(400, "Geçersiz hesap ID'si."); }

				$AccountName = isset($input['AccountName']) ? trim((string)$input['AccountName']) : null;
				$AccountType = isset($input['AccountType']) ? trim((string)$input['AccountType']) : null;
				$Description = array_key_exists('Description', $input) ? (!empty($input['Description']) ? trim((string)$input['Description']) : null) : false;
				$hasDefault = array_key_exists('IsDefault', $input);
				$IsDefault = $hasDefault ? (((int)$input['IsDefault'] === 1 || $input['IsDefault'] === true || $input['IsDefault'] === "1") ? 1 : -1) : null;

				if ($AccountName === null && $AccountType === null && $Description === false && !$hasDefault) {
					responseCode(400, "Güncelleme için en az bir alan belirtilmelidir.");
				}

				if ($IsDefault === 1) {
					$db->exec("UPDATE accounts SET IsDefault = -1");
				}

				$fields = [];
				$params = [":id" => $plainId];

				if ($AccountName !== null && $AccountName !== "") { $fields[] = "AccountName = :name"; $params[":name"] = $AccountName; }
				if ($AccountType !== null && $AccountType !== "") { $fields[] = "AccountType = :type"; $params[":type"] = $AccountType; }
				if ($Description !== false) { $fields[] = "Description = :desc"; $params[":desc"] = $Description; }
				if ($hasDefault) { $fields[] = "IsDefault = :def"; $params[":def"] = $IsDefault; }

				$sql = "UPDATE accounts SET " . implode(", ", $fields) . " WHERE AccountId = :id";
				$stmt = $db->prepare($sql);
				$stmt->execute($params);

				responseCode(200, NULL, ["message" => "Hesap başarıyla güncellendi."]);
			}
			catch (Exception $e) 
			{
				responseCode(500, "Sunucu hatası: " . $e->getMessage());
			}
		}
		elseif ($requestMethod === 'DELETE') 
		{
			try 
			{
				$input = json_decode(file_get_contents("php://input"), true);
				$AccountId = $input['AccountId'] ?? responseCode(400, "Hesap ID'si eksik");
				$plainId = decrypt($AccountId);
				if ($plainId === false || $plainId === "") { $plainId = $AccountId; }
				$plainId = (int)$plainId;
				if ($plainId <= 0) { responseCode(400, "Geçersiz hesap ID'si."); }

				// Hareketlerdeki referansları güvenle NULL yap
				$db->prepare("UPDATE assets SET AccountId = NULL WHERE AccountId = :id")->execute([":id" => $plainId]);
				$db->prepare("UPDATE bills SET AccountId = NULL WHERE AccountId = :id")->execute([":id" => $plainId]);

				$stmt = $db->prepare("DELETE FROM accounts WHERE AccountId = :id LIMIT 1");
				$stmt->execute([":id" => $plainId]);

				responseCode(200, "Hesap başarıyla silindi.");
			}
			catch (Exception $e) 
			{
				responseCode(500, "Sunucu hatası: " . $e->getMessage());
			}
		}
		else 
		{
			responseCode(405);
		}
	break;
	
	case 'profil':
		validateToken();
		$token = getBearerToken();
		$payload = verifyJwtToken((string)$token, "access");
		$userId = (int)($payload["uid"] ?? 0);
		if ($userId <= 0) {
			$u = $db->query("SELECT UserId FROM `user` ORDER BY UserId ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
			$userId = (int)($u["UserId"] ?? 0);
		}
		if ($userId <= 0) {
			responseCode(404, "Kullanıcı bulunamadı.");
		}

		if ($requestMethod === 'GET') 
		{
			$stmt = $db->prepare("SELECT UserId, FirstName, LastName, Email, Currency, TelegramBotUsername, TelegramChatId, TelegramUpdatedAt FROM `user` WHERE UserId = :uid LIMIT 1");
			$stmt->execute([":uid" => $userId]);
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$user) { responseCode(404, "Kullanıcı bulunamadı."); }
			responseCode(200, NULL, $user);
		}
		elseif ($requestMethod === 'POST' || $requestMethod === 'PATCH') 
		{
			$input = json_decode(file_get_contents("php://input"), true);
			if (!is_array($input) || empty($input)) {
				$input = $_POST;
			}

			$action = $input['action'] ?? 'profil';

			if ($action === 'sifre') 
			{
				$currentPassword = (string)($input['current_password'] ?? '');
				$newPassword = (string)($input['new_password'] ?? '');
				$confirmPassword = (string)($input['confirm_password'] ?? '');

				if ($currentPassword === '') {
					responseCode(400, "Mevcut şifrenizi girmelisiniz.");
				}
				if ($newPassword === '') {
					responseCode(400, "Yeni şifre boş olamaz.");
				}
				if (mb_strlen($newPassword) < 6) {
					responseCode(400, "Yeni şifre en az 6 karakter olmalıdır.");
				}
				if ($newPassword !== $confirmPassword) {
					responseCode(400, "Yeni şifre ve şifre tekrarı birbiriyle uyuşmuyor.");
				}

				$stmt = $db->prepare("SELECT Password FROM `user` WHERE UserId = :uid LIMIT 1");
				$stmt->execute([":uid" => $userId]);
				$storedHash = (string)($stmt->fetchColumn() ?? '');

				if ($storedHash === '' || !password_verify($currentPassword, $storedHash)) {
					responseCode(400, "Mevcut şifreniz hatalı.");
				}

				if (password_verify($newPassword, $storedHash)) {
					responseCode(400, "Yeni şifreniz mevcut şifrenizle aynı olamaz.");
				}

				$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
				$up = $db->prepare("UPDATE `user` SET Password = :pw WHERE UserId = :uid LIMIT 1");
				$up->execute([
					":pw" => $newHash,
					":uid" => $userId,
				]);

				responseCode(200, "Şifreniz başarıyla güncellendi.");
			}
			else 
			{
				$firstName = trim((string)($input['FirstName'] ?? ''));
				$lastName = trim((string)($input['LastName'] ?? ''));
				$email = trim((string)($input['Email'] ?? ''));
				$currency = trim((string)($input['Currency'] ?? ''));

				if ($firstName === '' || $lastName === '') {
					responseCode(400, "Ad ve Soyad alanları zorunludur.");
				}
				if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
					responseCode(400, "Geçerli bir e-posta adresi giriniz.");
				}

				$check = $db->prepare("SELECT COUNT(*) FROM `user` WHERE Email = :em AND UserId != :uid");
				$check->execute([":em" => $email, ":uid" => $userId]);
				if ((int)$check->fetchColumn() > 0) {
					responseCode(400, "Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.");
				}

				$fields = ["FirstName = :fn", "LastName = :ln", "Email = :em"];
				$params = [
					":fn" => $firstName,
					":ln" => $lastName,
					":em" => $email,
					":uid" => $userId,
				];

				if ($currency !== '') {
					$fields[] = "Currency = :cur";
					$params[":cur"] = $currency;
				}

				$sql = "UPDATE `user` SET " . implode(", ", $fields) . " WHERE UserId = :uid LIMIT 1";
				$up = $db->prepare($sql);
				$up->execute($params);

				responseCode(200, "Profil bilgileriniz başarıyla güncellendi.", [
					"UserId" => $userId,
					"FirstName" => $firstName,
					"LastName" => $lastName,
					"Email" => $email,
					"Currency" => $currency !== '' ? $currency : 'TRY',
				]);
			}
		}
		else 
		{
			responseCode(405);
		}
	break;
	
	
	case "gorunurluk":
		if($requestMethod === "POST") 
		{
			if (!isset($_POST['tur'])) { responseCode(400); }
			$tur = (string)$_POST['tur'];
			if ($tur !== "rakamlar") { responseCode(400); }

			$mevcutDeger = isset($_SESSION[$tur]) ? intval($_SESSION[$tur]) : 1;
			$_SESSION[$tur] = ($mevcutDeger === 1) ? 0 : 1;
			responseCode(200, "Güncelleme başarılı.");
		}
		else 
		{
			responseCode(400);
		}
	break;
	
    default:
        responseCode(404, "Hatalı uç nokta.");
    break;
}


function base64UrlEncode(string $data): string
{
	return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode(string $data): string|false
{
	$pad = strlen($data) % 4;
	if ($pad > 0) { $data .= str_repeat('=', 4 - $pad); }
	return base64_decode(strtr($data, '-_', '+/'), true);
}

function jwtEncode(array $payload): string
{
	$header = ["alg" => "HS256", "typ" => "JWT"];
	$headerPart = base64UrlEncode(json_encode($header, JSON_UNESCAPED_UNICODE));
	$payloadPart = base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
	$signature = hash_hmac("sha256", $headerPart . "." . $payloadPart, FIN_JWT_SECRET, true);
	return $headerPart . "." . $payloadPart . "." . base64UrlEncode($signature);
}

function verifyJwtToken(string $jwt, string $requiredType = "access")
{
	$parts = explode('.', $jwt);
	if (count($parts) !== 3) { return false; }

	[$headerPart, $payloadPart, $signaturePart] = $parts;
	$rawSig = base64UrlDecode($signaturePart);
	$expectedSig = hash_hmac("sha256", $headerPart . "." . $payloadPart, FIN_JWT_SECRET, true);
	if (!is_string($rawSig) || !hash_equals($expectedSig, $rawSig)) { return false; }

	$payloadJson = base64UrlDecode($payloadPart);
	if (!is_string($payloadJson)) { return false; }
	$payload = json_decode($payloadJson, true);
	if (!is_array($payload)) { return false; }

	$now = time();
	if (($payload["exp"] ?? 0) < $now) { return false; }
	if (($payload["type"] ?? "") !== $requiredType) { return false; }

	return $payload;
}

function issueJwtTokens(array $user): array
{
	$now = time();
	$base = [
		"uid" => $user["UserId"] ?? null,
		"fn" => $user["FirstName"] ?? null,
		"ln" => $user["LastName"] ?? null,
		"email" => $user["Email"] ?? null,
		"iat" => $now,
	];

	$accessPayload = $base;
	$accessPayload["type"] = "access";
	$accessPayload["exp"] = $now + FIN_JWT_ACCESS_TTL;
	$accessPayload["jti"] = bin2hex(random_bytes(16));

	$refreshPayload = $base;
	$refreshPayload["type"] = "refresh";
	$refreshPayload["exp"] = $now + FIN_JWT_REFRESH_TTL;
	$refreshPayload["jti"] = bin2hex(random_bytes(16));

	return [
		"AccessToken" => jwtEncode($accessPayload),
		"RefreshToken" => jwtEncode($refreshPayload),
	];
}

function getBearerToken(): ?string
{
	$headers = function_exists('getallheaders') ? getallheaders() : [];
	if (!is_array($headers) || empty($headers)) {
		$headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
	}
	$authHeader = null;
	foreach ($headers as $k => $v) {
		if (strtolower((string)$k) === 'x-authorization') { $authHeader = $v; break; }
	}
	if (!$authHeader || !is_string($authHeader)) { return null; }
	$parts = explode(" ", trim($authHeader), 2);
	if (count($parts) !== 2 || strtolower($parts[0]) !== "bearer") { return null; }
	return trim($parts[1]);
}

function responseCode($code, $message = null, $data = null) 
{
    $messages = [
        200 => "OK",
        400 => "Bad Request",
        401 => "Unauthorized",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        429 => "Too Many Requests",
        500 => "Internal Server Error",
    ];

    $message = $message ?? ($messages[$code] ?? "Unexpected error occurred");
    http_response_code($code);
	
    $response = [
        "code" => $code,
        "message" => $message
    ];
    if (!is_null($data)) {
        $response["data"] = $data;
    }
	if ($code === 200 && !empty($GLOBALS["FIN_ROTATED_TOKENS"]) && is_array($GLOBALS["FIN_ROTATED_TOKENS"])) {
		$response["tokens"] = $GLOBALS["FIN_ROTATED_TOKENS"];
	}
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

function floodControl($limit = 5, $timeWindow = 10) {
    $userIp = $_SERVER['REMOTE_ADDR'];

    if (isset($_SESSION['request_times'][$userIp])) {
        $requestTimes = $_SESSION['request_times'][$userIp];

        $requestTimes = array_filter($requestTimes, function($timestamp) use ($timeWindow) {
            return $timestamp > (time() - $timeWindow);
        });

        if (count($requestTimes) >= $limit) {
            responseCode(429);
        }

        $requestTimes[] = time();
        $_SESSION['request_times'][$userIp] = $requestTimes;
    } else {
        $_SESSION['request_times'][$userIp] = [time()];
    }

    return true;
}

function encrypt($data) 
{
	$key = FIN_CRYPTO_KEY; 
	$iv = FIN_CRYPTO_IV;
    return base64_encode(openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv));
}

function decrypt($data) 
{
	$key = FIN_CRYPTO_KEY; 
	$iv = FIN_CRYPTO_IV;
    return openssl_decrypt(base64_decode($data), 'AES-256-CBC', $key, 0, $iv);
}

function validateToken() 
{
	$token = getBearerToken();
	if (!$token) { responseCode(401); }

	$payload = verifyJwtToken($token, "access");
	if (!$payload && FIN_JWT_ALLOW_LEGACY_BEARER && $token === FIN_API_BEARER_TOKEN) { return true; }
	if (!$payload) { responseCode(401); }

	$remaining = (int)(($payload["exp"] ?? 0) - time());
	if ($remaining <= FIN_JWT_RENEW_WINDOW) {
		$GLOBALS["FIN_ROTATED_TOKENS"] = issueJwtTokens([
			"UserId" => $payload["uid"] ?? null,
			"FirstName" => $payload["fn"] ?? null,
			"LastName" => $payload["ln"] ?? null,
			"Email" => $payload["email"] ?? null,
		]);
	}
	return true;
}

////////// HATA AYIKLAMA FONKSİYONU //////////
function hataAyikla($metin)
{
	error_log("[Finans API Debug] " . (is_scalar($metin) ? (string)$metin : json_encode($metin, JSON_UNESCAPED_UNICODE)));
}
////////// HATA AYIKLAMA FONKSİYONU //////////

function apiBaseUrl(): string
{
	$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
	$scheme = $https ? 'https' : 'http';
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	return $scheme . '://' . $host;
}

// Returns absolute directory for project root (one level above /api).
function apiUploadsDir(): string
{
	return dirname(__DIR__);
}
?>

