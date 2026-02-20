<?php
$_GET['loginbypass'] = true;	
include("../fonksiyonlar.php");
$ENV = parse_ini_file(__DIR__ . "/../../.FINANS", false, INI_SCANNER_RAW) ?: [];
define("FIN_TG_BOT_TOKEN", $ENV["TG_BOT_TOKEN"] ?? "");
define("FIN_API_BEARER_TOKEN", $ENV["API_BEARER_TOKEN"] ?? "");
define("FIN_TG_ALLOWED_CHAT_ID", (int)($ENV["TG_ALLOWED_CHAT_ID"] ?? 0));
define("FIN_TG_API_EMAIL", $ENV["TG_API_EMAIL"] ?? "");
define("FIN_TG_API_PASSWORD", $ENV["TG_API_PASSWORD"] ?? "");
define("FIN_TG_ALLOW_LEGACY_BEARER", (string)($ENV["JWT_ALLOW_LEGACY_BEARER"] ?? "0") === "1");

$TOKEN = FIN_TG_BOT_TOKEN;
$API_URL = "https://api.telegram.org/bot$TOKEN/";
$finansToken = "";
$loginData = ["Email" => FIN_TG_API_EMAIL, "Password" => FIN_TG_API_PASSWORD];
if (FIN_TG_API_EMAIL !== "" && FIN_TG_API_PASSWORD !== "") {
	$loginResp = apiRequest('/login', 'POST', $loginData, NULL);
	$loginPayload = $loginResp["data"] ?? [];
	$finansToken = $loginPayload["AccessToken"] ?? "";
}
if ($finansToken === "" && FIN_TG_ALLOW_LEGACY_BEARER && FIN_API_BEARER_TOKEN !== "") {
	$finansToken = FIN_API_BEARER_TOKEN; // Transitional fallback.
}
$session_file = "sessions.json";

$sessions = file_exists($session_file) ? json_decode(file_get_contents($session_file), true) : [];

$update = json_decode(file_get_contents("php://input"), true);
$chat_id = $update["message"]["chat"]["id"] ?? null;
$text = $update["message"]["text"] ?? null;
$callback_data = $update["callback_query"]["data"] ?? null;
$callback_chat_id = $update["callback_query"]["message"]["chat"]["id"] ?? null;

if ($callback_data) {
    $chat_id = $callback_chat_id;
    $text = $callback_data;
}

if (!$chat_id || $chat_id != FIN_TG_ALLOWED_CHAT_ID) { sendMessage($chat_id, "İşlem yapma yetkiniz bulunmuyor."); exit; }

if ($finansToken === "") { sendMessage($chat_id, "API token alinamadi."); exit; }
if ($text == "Yeni Hareket") { $sessions[$chat_id] = ["step" => "Yeni Hareket"]; }

$step = $sessions[$chat_id]["step"];

if ($step === "Yeni Hareket") 
{
    sendMessage($chat_id, "Lütfen işlem türünü seçin:", [["Gelir", "Gider"]]);
    $sessions[$chat_id]["step"] = "select_type";
}

elseif ($step === "select_type") 
{
    $sessions[$chat_id]["type"] = $text;
	$CategoryType = ($sessions[$chat_id]["type"] == "Gelir") ? 1 : (($sessions[$chat_id]["type"] == "Gider") ? 2 : false);
	
	if($CategoryType) 
	{
		$kategoriler = apiRequest('/kategoriler', 'GET', ["Type" => $CategoryType, "orderkey" => "CategoryName", "ordertype" => "ASC"], $finansToken);
		$buttons = array_map(function($category) { return [['text' => $category['CategoryName'], 'callback_data' => $category['CategoryId']]]; }, $kategoriler['data']['list']);
		sendMessage($chat_id, "Lütfen kategori seçin:", $buttons, true);
		$sessions[$chat_id]["step"] = "select_category";
	}
	else 
	{
		sendMessage($chat_id, "❌❌❌\n Geçersiz işlem. Yeni hareket oluşturun. \n❌❌❌", [["Yeni Hareket"]]);
	}
    
}

elseif ($step === "select_category") 
{
	$CategoryType = ($sessions[$chat_id]["type"] == "Gelir") ? 1 : (($sessions[$chat_id]["type"] == "Gider") ? 2 : null);
	$kategoriSorgula = apiRequest('/kategoriler', 'GET', ["Type" => $CategoryType, "CategoryId" => $text], $finansToken);
	if($kategoriSorgula["code"] == 200) 
	{
		$sessions[$chat_id]["CategoryId"] = $text;
		sendMessage($chat_id, "Başlığı girin:");
		$sessions[$chat_id]["step"] = "enter_title";
	}
	else 
	{
		sendMessage($chat_id, "❌❌❌\n Geçersiz kategori. Yeni bir hareket oluşturun. \n❌❌❌", [["Yeni Hareket"]]);
		$sessions[$chat_id]["step"] = "select_category";
	}
}

elseif ($step === "enter_title") 
{
    $sessions[$chat_id]["Title"] = $text;
    sendMessage($chat_id, "Tutarı girin:");
    $sessions[$chat_id]["step"] = "enter_amount";
}

elseif ($step === "enter_amount") 
{
    if (!is_numeric($text)) { 
        sendMessage($chat_id, "Lütfen geçerli bir sayı girin:"); 
    } else {
        $sessions[$chat_id]["Amount"] = $text;
        sendMessage($chat_id, "Açıklama girin:");
        $sessions[$chat_id]["step"] = "enter_description";
    }
}

elseif ($step === "enter_description") {
    $sessions[$chat_id]["Description"] = $text;

    $data = [
        "CategoryId" => $sessions[$chat_id]["CategoryId"],
        "Title" => $sessions[$chat_id]["Title"],
        "Date" => date("Y-m-d H:i:s"),
        "Amount" => $sessions[$chat_id]["Amount"],
        "Description" => $sessions[$chat_id]["Description"]
    ];

	if($sessions[$chat_id]["type"] == "Gelir") { $hareketEkle = apiRequest('/gelirler', 'POST', $data, $finansToken); }
	elseif($sessions[$chat_id]["type"] == "Gider") { $hareketEkle = apiRequest('/giderler', 'POST', $data, $finansToken); }
	if($hareketEkle["code"] == 200) 
	{
		sendMessage($chat_id, "✅✅✅\n".$sessions[$chat_id]["type"]." hareketi başarıyla kaydedildi.\n".$sessions[$chat_id]["Title"]." : ".$sessions[$chat_id]["Amount"]."₺ \n✅✅✅", [["Yeni Hareket"]]);
		unset($sessions[$chat_id]);
	}
	else 
	{
		sendMessage($chat_id, "❌❌❌\n".$sessions[$chat_id]["type"]." hareketi kaydedilemedi:".$hareketEkle["message"]."₺ \n❌❌❌", [["Yeni Hareket"]]);
	}
	
}

file_put_contents($session_file, json_encode($sessions, JSON_PRETTY_PRINT));


function sendMessage($chat_id, $text, $buttons = NULL, $inline = false)
{
    global $API_URL;
    
    $data = ["chat_id" => $chat_id, "text" => $text, "parse_mode" => "HTML"];
    
    if ($buttons != NULL) {
        if ($inline) {
            $reply_markup = ["inline_keyboard" => $buttons];
        } else {
            $reply_markup = ["keyboard" => $buttons, "resize_keyboard" => true];
        }

        $data["reply_markup"] = json_encode($reply_markup, JSON_UNESCAPED_UNICODE);
    }
    
    file_get_contents($API_URL . "sendMessage?" . http_build_query($data));
}
?>
