<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

if (!isset($_GET['file'])) {
    echo json_encode(["error" => "Missing file param"]);
    exit;
}

$file = __DIR__ . "/storage/converted/" . basename($_GET['file']);
if (!file_exists($file)) {
    echo json_encode(["status" => "not_found"]);
    exit;
}

$status = trim(file_get_contents($file));
echo json_encode(["status" => $status]);
