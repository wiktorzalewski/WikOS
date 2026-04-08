<?php
// api.WikOS.run/status.php
// Pobiera aktualne wyniki w czasie rzeczywistym (polling endpoint)
// GET ?api_key=XXX&since_id=0

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

require_once '../admin.WikOS.run/db.php';

$api_key  = trim($_GET['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '');
$since_id = (int)($_GET['since_id'] ?? 0);

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

// Get new results since last seen id
$results = $pdo->prepare("
    SELECT r.id, r.distance_m, r.time_ms, r.source, r.created_at,
           a.first_name, a.last_name
    FROM results r
    LEFT JOIN athletes a ON r.athlete_id = a.id
    WHERE r.account_id = ? AND r.id > ?
    ORDER BY r.id DESC
    LIMIT 20
");
$results->execute([$device['account_id'], $since_id]);
$rows = $results->fetchAll();

// Format times
foreach ($rows as &$row) {
    $ms = $row['time_ms'];
    $cs = floor($ms / 10) % 100;
    $sec = floor($ms / 1000);
    $min = floor($sec / 60);
    $sec %= 60;
    $row['time_fmt'] = $min > 0
        ? sprintf('%d:%02d.%02d', $min, $sec, $cs)
        : sprintf('%d.%02d', $sec, $cs);
}

echo json_encode([
    'ok'         => true,
    'server_ms'  => round(microtime(true) * 1000),
    'results'    => $rows,
    'max_id'     => $rows ? max(array_column($rows, 'id')) : $since_id,
]);
