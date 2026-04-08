<?php
// api.WikOS.run/submit.php
// Pachołek wysyła wynik POST
// Wymagane pola: api_key, distance_m, time_ms
// Opcjonalne: athlete_id, event_id, notes

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../admin.WikOS.run/db.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); die(json_encode(['error'=>'Method not allowed']));
}

$api_key    = trim($_POST['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '');
$distance_m = (int)($_POST['distance_m'] ?? 0);
$time_ms    = (int)($_POST['time_ms'] ?? 0);
$athlete_id = isset($_POST['athlete_id']) ? (int)$_POST['athlete_id'] : null;
$event_id   = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;
$notes      = trim($_POST['notes'] ?? '');

if(!$api_key || !$distance_m || !$time_ms) {
    http_response_code(400);
    die(json_encode(['error'=>'Missing required fields: api_key, distance_m, time_ms']));
}

// Auth by api_key
$dev = $pdo->prepare("SELECT * FROM devices WHERE api_key=?");
$dev->execute([$api_key]); $device = $dev->fetch();
if(!$device) {
    http_response_code(401); die(json_encode(['error'=>'Invalid API key']));
}

$account_id = $device['account_id'];
$device_id  = $device['id'];

// Check safe-time
$safe_key = "safe_time_$distance_m";
$safe_time = isset($device[$safe_key]) ? $device[$safe_key] : (isset($device['safe_time_custom']) ? $device['safe_time_custom'] : 8000);
if($time_ms < $safe_time) {
    http_response_code(200);
    die(json_encode(['ok'=>false,'ignored'=>true,'reason'=>'Below safe-time','safe_time_ms'=>$safe_time,'time_ms'=>$time_ms]));
}

// Save result
$ins = $pdo->prepare("INSERT INTO results (account_id,athlete_id,event_id,distance_m,time_ms,source,device_id,notes) VALUES (?,?,?,?,?,'device',?,?)");
$ins->execute([$account_id, $athlete_id, $event_id, $distance_m, $time_ms, $device_id, $notes]);
$result_id = $pdo->lastInsertId();

// Update device last_seen
$pdo->prepare("UPDATE devices SET last_seen=NOW(), last_ip=?, status='active' WHERE id=?")
    ->execute([$_SERVER['REMOTE_ADDR'], $device_id]);

// Log activity for diagnostics
$pdo->prepare("INSERT INTO device_logs (device_id, action_type, details, ip_address) VALUES (?, 'signal', ?, ?)")
    ->execute([$device_id, "Distance: {$distance_m}m, Time: {$time_ms}ms", $_SERVER['REMOTE_ADDR']]);

// Format time for response
$cs  = floor($time_ms/10)%100;
$sec = floor($time_ms/1000);
$min = floor($sec/60); $sec %= 60;
$fmt = $min>0 ? sprintf('%d:%02d.%02d',$min,$sec,$cs) : sprintf('%d.%02d',$sec,$cs);

echo json_encode([
    'ok'         => true,
    'result_id'  => $result_id,
    'time_ms'    => $time_ms,
    'time_fmt'   => $fmt,
    'distance_m' => $distance_m,
    'device_id'  => $device_id,
    'athlete_id' => $athlete_id,
]);
