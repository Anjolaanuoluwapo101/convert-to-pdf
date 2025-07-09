<?php
// ping.php: Lightweight endpoint to wake up the service and confirm readiness
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Simulate a delay if requested (for testing purposes)
//Ideal delay is like 40 Seconds
$wait = isset($_GET['wait']) ? intval($_GET['wait']) : 0;
if ($wait > 0 && $wait <= 30) {
    sleep($wait);
}

echo json_encode(["ready" => 1]);
exit;
