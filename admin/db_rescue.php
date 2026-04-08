<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

$msg = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_fix'])) {
    try {
        // Fix athletes table
        $pdo->exec("ALTER TABLE athletes 
            ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT NULL AFTER birth_year,
            ADD COLUMN IF NOT EXISTS pzla_url VARCHAR(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS instagram_username VARCHAR(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS public_email VARCHAR(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS special_rank VARCHAR(50) DEFAULT NULL
        ");

        // Fix users table for permissions
        $pdo->exec("ALTER TABLE users 
            ADD COLUMN IF NOT EXISTS permissions TEXT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS is_super TINYINT(1) DEFAULT 0
        ");
        
        // Ensure the first admin is super
        $pdo->exec("UPDATE users SET is_super = 1 WHERE is_super = 0 LIMIT 1");

        // Sponsors table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sponsors` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `logo_path` VARCHAR(255) NOT NULL,
            `website_url` VARCHAR(255) DEFAULT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `sort_order` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Devices status
        $pdo->exec("ALTER TABLE devices 
            ADD COLUMN IF NOT EXISTS ota_channel ENUM('stable','beta') DEFAULT 'stable',
            ADD COLUMN IF NOT EXISTS pending_action ENUM('none','factory_reset') DEFAULT 'none'
        ");

        // System Settings
        $pdo->exec("CREATE TABLE IF NOT EXISTS `system_settings` (
            `setting_key` VARCHAR(50) PRIMARY KEY,
            `setting_value` TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) VALUES 
            ('maintenance_mode', '0'),
            ('global_broadcast', ''),
            ('custom_css', ''),
            ('global_safe_time_min', '2000')
        ");

        $msg = '<div class="alert alert-g" style="padding:16px;background:var(--green-dim);color:var(--green);border-radius:var(--rs);margin-bottom:20px;font-weight:800"><i class="fa-solid fa-check-double"></i> ✓ Gotowe! Tabele zostały naprawione i zsynchronizowane.</div>';

    } catch (PDOException $e) {
        $msg = '<div class="alert alert-r" style="padding:16px;background:var(--danger);color:#fff;border-radius:var(--rs);margin-bottom:20px;font-weight:700">✕ Błąd: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

include 'includes/header.php';
?>
<div class="ph">
  <div class="ph-title"><i class="fa-solid fa-truck-medical"></i> Ratownik Bazy Danych</div>
  <div class="ph-sub">To narzędzie naprawia strukturę tabel, gdy coś nie działa po aktualizacji kodu.</div>
</div>

<?= $msg ?>

<div class="card" style="border-left:4px solid var(--danger); text-align:center; padding:40px">
    <div style="font-size:3rem; color:var(--warn); margin-bottom:20px"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <h2 style="font-size:1.5rem; font-weight:800; margin-bottom:12px">Masz błąd podczas dodawania zawodnika?</h2>
    <p style="color:var(--muted); max-width:600px; margin:0 auto 30px;">
        Kliknięcie tego przycisku dobuduje brakujące kolumny w Twojej bazie danych. 
        Użyj tego, jeśli po aktualizacji systemu niektóre funkcje przestały działać lub sypią błędami.
    </p>

    <form method="POST">
        <input type="hidden" name="run_fix" value="1">
        <button type="submit" class="btn btn-d btn-lg" style="margin:0 auto; padding:20px 40px; font-size:1.2rem; display:inline-flex; align-items:center; box-shadow:0 0 40px rgba(239,68,68,0.4)">
            <i class="fa-solid fa-wrench" style="margin-right:10px"></i> Napraw strukturę tabel
        </button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
