<?php
header('Content-Type: application/json');
echo json_encode([
    'name'    => 'WikOS Timing API',
    'version' => '1.0',
    'status'  => 'online',
    'time'    => date('Y-m-d H:i:s'),
    'endpoints' => [
        'GET  /time.php'       => 'Aktualny czas serwera (ms)',
        'POST /register.php'   => 'Rejestracja pachołka [reg_code]',
        'POST /submit.php'     => 'Wyslij wynik [api_key, distance_m, time_ms]',
        'GET  /athletes.php'   => 'Lista zawodnikow [api_key]',
        'GET  /status.php'     => 'Ostatnie wyniki [api_key, since_id]',
    ],
]);
