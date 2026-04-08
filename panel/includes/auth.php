<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['account_id'])) {
    header('Location: /index.php');
    exit;
}
$uid   = (int)$_SESSION['account_id'];
$uname = $_SESSION['account_name'] ?? 'Użytkownik';

// Maintenance Mode Enforcement
try {
    require_once __DIR__.'/../db.php';
    if(isset($pdo)) {
        $mainQ = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key='maintenance_mode'")->fetchColumn();
        if($mainQ === '1') {
            echo '<div style="background:#020617;color:#f1f5f9;height:100vh;display:flex;align-items:center;justify-content:center;font-family:sans-serif;text-align:center;padding:20px"><div style="max-width:500px"><h1 style="color:#ef4444;font-size:2.5rem;margin-bottom:10px;font-weight:900">PRZERWA TECHNICZNA</h1><p style="color:#94a3b8;font-size:1.1rem;margin-bottom:20px;line-height:1.5">Trwają prace w strefie serwerowej.<br>Twoje sesje i pomiary sprzętowe u boku bieżni cały czas potrafią odbierać wyniki fizycznie. Menedżer wróci za chwilę.</p></div></div>';
            exit;
        }
    }
} catch (Exception $e) {}
