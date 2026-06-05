<?php
include("fonksiyonlar.php");

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["code" => 405, "message" => "Method Not Allowed"], JSON_UNESCAPED_UNICODE);
    exit;
}

$params = [];

if (isset($_GET["amount"])) {
    $params["amount"] = $_GET["amount"];
}
if (!empty($_GET["date"])) {
    $params["date"] = $_GET["date"];
}
if (!empty($_GET["context"])) {
    $params["context"] = $_GET["context"];
}

$resp = apiRequest('/para-detay', 'GET', $params, $_SESSION['Api_Token']);
echo json_encode($resp, JSON_UNESCAPED_UNICODE);
