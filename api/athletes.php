<?php
// api.WikOS.run/athletes.php
// Pachołek pobiera liste zawodników dla konta
// GET ?api_key=XXX

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../admin.WikOS.run/db.php';

$api_key = trim($_GET['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '');
if (!$api_key) {
    http_response_code(400);
    die(json_encode(['error' => 'Missing api_key']));
}

$dev = $pdo->prepare("SELECT * FROM devices WHERE api_key=?");
$dev->execute([$api_key]);
$device = $dev->fetch();
if (!$device) {
    http_response_code(401);
    die(json_encode(['error' => 'Invalid API key']));
}

// Update last seen
$pdo->prepare("UPDATE devices SET last_seen=NOW(), last_ip=? WHERE id=?")
    ->execute([$_SERVER['REMOTE_ADDR'], $device['id']]);

$athletes = $pdo->prepare(
    "SELECT id, first_name, last_name, birth_year, club FROM athletes WHERE account_id=? ORDER BY last_name, first_name"
);
$athletes->execute([$device['account_id']]);

echo json_encode([
    'ok'       => true,
    'count'    => $athletes->rowCount(),
    'athletes' => $athletes->fetchAll(),
]);
