<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');
$now     = microtime(true);
$unix_ms = round($now * 1000);
$dt      = new DateTime('now', new DateTimeZone('Europe/Warsaw'));
$ms_part = str_pad(round(($now - floor($now)) * 1000), 3, '0', STR_PAD_LEFT);
echo json_encode([
    'unix_ms'  => $unix_ms,
    'unix'     => time(),
    'time'     => $dt->format('H:i:s'),
    'ms'       => (int)(($now - floor($now)) * 1000),
    'date'     => $dt->format('Y-m-d'),
    'timezone' => 'Europe/Warsaw',
    'server'   => 'WikOS-MTP/1.0',
]);
