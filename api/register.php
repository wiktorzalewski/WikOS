<?php
// api.WikOS.run/register.php
// Urzadzenie wysyla kod rejestracyjny i otrzymuje swoj api_key
// POST: reg_code

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../admin.WikOS.run/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'POST only']));
}

$reg_code = strtoupper(trim($_POST['reg_code'] ?? ''));
if (!$reg_code) {
    http_response_code(400);
    die(json_encode(['error' => 'Missing reg_code']));
}

$dev = $pdo->prepare("SELECT * FROM devices WHERE reg_code=?");
$dev->execute([$reg_code]);
$device = $dev->fetch();

if (!$device) {
    http_response_code(200);
    die(json_encode(['ok' => false, 'status' => 'waiting', 'message' => 'Waiting for Trener to register this code in panel...']));
}

// Activate device
$pdo->prepare("UPDATE devices SET status='active', last_seen=NOW(), last_ip=? WHERE id=?")
    ->execute([$_SERVER['REMOTE_ADDR'], $device['id']]);

echo json_encode([
    'ok'          => true,
    'api_key'     => $device['api_key'],
    'device_id'   => $device['id'],
    'device_name' => $device['name'],
    'safe_time_60'  => $device['safe_time_60'],
    'safe_time_100' => $device['safe_time_100'],
    'safe_time_200' => $device['safe_time_200'],
    'safe_time_400' => $device['safe_time_400'],
    'hotspot_name'  => $device['hotspot_name'],
    'hotspot_pass'  => $device['hotspot_password'],
]);
