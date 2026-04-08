<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach($_POST['settings'] as $key => $val) {
        $pdo->prepare("UPDATE system_settings SET setting_value=? WHERE setting_key=?")->execute([$val, $key]);
    }
    header('Location: settings.php?msg=saved'); exit;
}

$set_raw = $pdo->query("SELECT * FROM system_settings")->fetchAll();
$settings = [];
foreach($set_raw as $s) $settings[$s['setting_key']] = $s['setting_value'];

include 'includes/header.php';
?>
<div class="ph">
  <div class="ph-title"><i class="fa-solid fa-power-off"></i> Tryb Pracy</div>
  <div class="ph-sub">Możesz tu szybko zablokować dostęp do panelu dla trenerów. Twoje pachołki dalej będą mierzyć czas w tle, nic nie zniknie.</div>
</div>

<?php if(isset($_GET['msg'])): ?>
<div class="alert alert-g" style="padding:16px;background:var(--danger);color:#fff;border-radius:var(--rs);margin-bottom:20px;font-weight:700">✓ Status zmieniony. Trenerzy dostaną blokadę ekranu.</div>
<?php endif; ?>

<form method="POST">
  <div class="card" style="border-left:4px solid var(--danger); text-align:center; padding:50px">
    <div style="font-size:3rem; color:var(--warn); margin-bottom:20px"><i class="fa-solid fa-lock"></i></div>
    <h2 style="font-size:1.5rem; font-weight:800; margin-bottom:20px">Główny wyłącznik panelu</h2>
    
    <div style="max-width:400px; margin:0 auto 30px">
      <select name="settings[maintenance_mode]" style="width:100%;padding:14px;background:var(--bg2);color:var(--text);border:1px solid var(--danger);border-radius:6px;font-weight:800;font-size:1rem;text-align:center">
        <option value="0" <?= ($settings['maintenance_mode']??'')=='0'?'selected':'' ?>>🟢 SYSTEM PRACUJE (NORMALNIE)</option>
        <option value="1" <?= ($settings['maintenance_mode']??'')=='1'?'selected':'' ?>>🔴 SYSTEM ZABLOKOWANY (MAINTENANCE)</option>
      </select>
    </div>

    <button type="submit" class="btn btn-d btn-lg" style="margin:0 auto; padding:15px 40px; font-size:1.1rem; display:inline-flex; align-items:center; box-shadow:0 0 40px rgba(239,68,68,0.4)">
        <i class="fa-solid fa-power-off" style="margin-right:10px"></i> ZASTOSUJ ZMIANĘ
    </button>
  </div>
</form>
<?php include 'includes/footer.php'; ?>
