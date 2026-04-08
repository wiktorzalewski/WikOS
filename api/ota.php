<?php
// api.WikOS.run/ota.php
// Sprawdza najnowszą wersję lub umożliwia obranie pliku Firmware dla Pachołka (RPi Zero / ESP)
// GET: ver (obecna wersja pachołka, opcjonalnie, by dowiedziec się czy najnowsza jest wieksza)
// GET: download=1 (pobiera fizycznie sam plik)

header('Access-Control-Allow-Origin: *');

require_once '../admin.WikOS.run/db.php';

$fw = $pdo->query("SELECT * FROM system_firmware ORDER BY created_at DESC LIMIT 1")->fetch();

if (!$fw) {
    http_response_code(404);
    die(json_encode(['error' => 'No firmware released']));
}

$path = __DIR__ . '/firmware/' . $fw['file_name'];

if (isset($_GET['download']) && $_GET['download'] == 1 && file_exists($path)) {
    // Send file
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($fw['file_name']).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

// Just version check
header('Content-Type: application/json');
echo json_encode([
    'latest_version' => $fw['version'],
    'release_notes' => $fw['release_notes'],
    'file_size' => file_exists($path) ? filesize($path) : 0,
    'download_url' => 'http://api.wikos.run/ota.php?download=1',
    'created_at' => $fw['created_at']
]);
