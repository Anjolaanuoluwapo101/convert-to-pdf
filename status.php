<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$timeout = 25; // seconds
$interval = 1; // seconds

if (!isset($_GET['file'])) {
    echo json_encode(["error" => "Missing file param"]);
    exit;
}

$file = __DIR__ . "/storage/converted/" . basename($_GET['file']);
if (!file_exists($file)) {
    echo json_encode(["status" => "not_found"]);
    exit;
}

$startStatus = trim(file_get_contents($file));
$startTime = time();

while (true) {
    clearstatcache();
    $currentStatus = trim(file_get_contents($file));
    if ($currentStatus !== $startStatus || $currentStatus === 'done') {
        echo json_encode(["status" => $currentStatus]);
        exit;
    }
    if ((time() - $startTime) >= $timeout) {
        echo json_encode(["status" => $currentStatus]);
        exit;
    }
    sleep($interval);
}
