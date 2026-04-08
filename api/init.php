<?php
// api.WikOS.run/init.php
// Pachołek wywołuje to po podłączeniu się do internetu po raz pierwszy, aby otrzymać kod weryfikacyjny

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../admin.WikOS.run/db.php';

// Generate unique registration code
do {
    $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    $exists = $pdo->prepare("SELECT id FROM devices WHERE reg_code=?");
    $exists->execute([$code]);
} while($exists->fetch());

$api_key = bin2hex(random_bytes(32));

// Insert as unassigned device
$stmt = $pdo->prepare("INSERT INTO devices (name, reg_code, api_key, status, last_seen, last_ip) VALUES ('Nowy Pachołek', ?, ?, 'pending', NOW(), ?)");
$stmt->execute([$code, $api_key, $_SERVER['REMOTE_ADDR']]);
$new_id = $pdo->lastInsertId();

// Log activity for diagnostics
$pdo->prepare("INSERT INTO device_logs (device_id, action_type, details, ip_address) VALUES (?, 'init', 'New device registered', ?)")
    ->execute([$new_id, $_SERVER['REMOTE_ADDR']]);

echo json_encode([
    'ok' => true,
    'reg_code' => $code,
    'api_key' => $api_key,
    'message' => 'Show reg_code on screen and wait for user to claim it in panel.'
]);
