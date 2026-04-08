<?php
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

// Fetch admin permissions
$u_stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$u_stmt->execute([$_SESSION['admin_email']]);
$user = $u_stmt->fetch();

// Check if is_super exists (migration safety)
$is_super = isset($user['is_super']) ? ($user['is_super'] == 1) : true; // Default true to prevent lockout
$u_perms = json_decode($user['permissions'] ?? '[]', true);
if(!is_array($u_perms)) $u_perms = [];

function hasPerm($p) {
    global $is_super, $u_perms;
    return ($is_super || in_array($p, $u_perms));
}

// Page to permission mapping
$page = basename($_SERVER['PHP_SELF']);
$gate = [
    'live.php'      => 'results',
    'accounts.php'  => 'accounts',
    'athletes.php'  => 'athletes',
    'results.php'   => 'results',
    'events.php'    => 'results',
    'devices.php'   => 'hardware',
    'ota.php'       => 'hardware',
    'settings.php'  => 'system',
    'sponsors.php'  => 'content',
    'server_health.php' => 'system',
    'logs.php'      => 'system',
    'content.php'   => 'content',
    'users.php'     => 'super'
];

// Protect restricted pages
if(isset($gate[$page])) {
    if($gate[$page] === 'super') {
        if(!$is_super) { header('Location: dashboard.php'); exit; }
    } else {
        if(!hasPerm($gate[$page])) { header('Location: dashboard.php'); exit; }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Centrum Dowodzenia — WikOS</title>
<link rel="icon" type="image/png" href="favicon.png">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700;800&family=Fira+Code:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#030303;--bg2:#070707;--surface:rgba(255,255,255,0.03);--surface-h:rgba(255,255,255,0.06);--border:rgba(255,255,255,0.08);--border-l:rgba(255,255,255,0.15);--primary:#3b82f6;--primary-dim:rgba(59,130,246,0.15);--primary-glow:rgba(59,130,246,0.3);--text:#f8fafc;--muted:#64748b;--danger:#ef4444;--success:#10b981;--warn:#f59e0b;--sw:250px;--rs:12px;--r:16px;}
body{background:var(--bg);color:var(--text);font-family:'Space Grotesk',sans-serif;min-height:100vh}
.layout{display:flex;min-height:100vh}
.sidebar{width:var(--sw);background:var(--bg2);border-right:1px solid var(--border);position:fixed;top:0;left:0;bottom:0;display:flex;flex-direction:column;z-index:100;transition:transform .2s}
.sb-logo{padding:24px 20px 18px;border-bottom:1px solid var(--border);text-align:center}
.sb-logo-text{font-size:1.1rem;font-weight:900;font-style:italic;text-transform:uppercase;letter-spacing:-.04em;color:#fff}
.sb-logo-text span{color:#10b981}
.sb-badge{font-size:9px;color:var(--danger);background:rgba(239,68,68,0.1);padding:3px 8px;border-radius:10px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;margin-top:6px;display:inline-block}
.sb-nav{flex:1;padding:12px 10px}
.sb-label{font-size:9px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--muted);padding:10px 8px 3px;margin-top:6px}
.sb-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:var(--rs);color:var(--muted);text-decoration:none;font-size:.85rem;font-weight:600;margin-bottom:4px;transition:all .15s}
.sb-link:hover{background:var(--surface-h);color:var(--text)}
.sb-link.active{background:var(--primary-dim);color:var(--primary);border:1px solid rgba(59,130,246,0.3)}
.sb-link i{width:16px;text-align:center;font-size:.8rem}
.main{margin-left:var(--sw);flex:1;padding:32px}
.ph{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
.ph-title{font-size:1.6rem;font-weight:800;letter-spacing:-.03em}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:24px;margin-bottom:20px}
.card-hd{font-size:10px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;color:var(--muted);margin-bottom:18px;display:flex;justify-content:space-between}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px}
.stat{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:20px}
.stat-val{font-size:2rem;font-weight:800;line-height:1;margin-bottom:6px;font-family:'Fira Code',monospace}
.stat-lbl{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.1em}
table{width:100%;border-collapse:collapse;font-size:.85rem}
th{text-align:left;padding:10px 14px;font-size:9px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border)}
td{padding:12px 14px;border-bottom:1px solid var(--border)}
tr:hover td{background:rgba(255,255,255,.02)}
.time-mono{font-family:'Fira Code',monospace}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:var(--rs);font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;cursor:pointer;border:none;text-decoration:none;transition:all .15s}
.btn-p{background:var(--primary);color:#fff}
.btn-p:hover{background:#2563eb;box-shadow:0 0 20px var(--primary-glow)}
.btn-d{background:rgba(239,68,68,.1);color:var(--danger);border:1px solid rgba(239,68,68,.3)}
.btn-d:hover{background:rgba(239,68,68,.2)}
.btn-sm{padding:6px 12px;font-size:.7rem}
.badge{padding:3px 8px;border-radius:100px;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em}
@media(max-width:768px){.sidebar{transform:translateX(-100%)}.main{margin-left:0;padding:16px}.hamburger{display:block;position:fixed;top:10px;left:10px;z-index:200;background:var(--bg2);border:1px solid var(--border);padding:10px;border-radius:8px}}
<?php
try {
    $cssQ = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key='custom_css'")->fetchColumn();
    if($cssQ) echo htmlspecialchars_decode($cssQ, ENT_QUOTES);
} catch(PDOException $e){}
?>
</style>
</head>
<body>
<div class="layout">
  <div class="sidebar">
    <div class="sb-logo">
      <div class="sb-logo-text">WIK<span>OS</span> ADMIN</div>
      <div class="sb-badge"><?= $is_super?'SUPER ADMIN':'SUB-ADMIN' ?></div>
    </div>
    <div class="sb-nav">
      <?php if(hasPerm('dashboard')): ?>
      <div class="sb-label">Główny Panel</div>
      <a href="dashboard.php" class="sb-link <?= $page=='dashboard.php'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> Podsumowanie & Statystyki</a>
      <?php endif; ?>
      
      <?php if(hasPerm('results')): ?>
      <a href="live.php" class="sb-link <?= $page=='live.php'?'active':'' ?>" style="color:var(--green)"><i class="fa-solid fa-satellite-dish"></i> Radar Na Żywo</a>
      <?php endif; ?>

      <div class="sb-label">Ludzie</div>
      <?php if(hasPerm('accounts')): ?>
      <a href="accounts.php" class="sb-link <?= $page=='accounts.php'?'active':'' ?>"><i class="fa-solid fa-users"></i> Trenerzy</a>
      <?php endif; ?>
      <?php if(hasPerm('athletes')): ?>
      <a href="athletes.php" class="sb-link <?= $page=='athletes.php'?'active':'' ?>"><i class="fa-solid fa-person-running"></i> Baza Zawodników</a>
      <?php endif; ?>
      <?php if(hasPerm('results')): ?>
      <a href="results.php" class="sb-link <?= $page=='results.php'?'active':'' ?>"><i class="fa-solid fa-stopwatch"></i> Audyt Biegów</a>
      <a href="events.php" class="sb-link <?= $page=='events.php'?'active':'' ?>"><i class="fa-solid fa-calendar-check"></i> Przegląd Sesji</a>
      <?php endif; ?>

      <?php if(hasPerm('hardware')): ?>
      <div class="sb-label">Sprzęt & Akcesoria</div>
      <a href="devices.php" class="sb-link <?= $page=='devices.php'?'active':'' ?>"><i class="fa-solid fa-microchip"></i> Pachołki & Hotspoty</a>
      <a href="ota.php" class="sb-link <?= $page=='ota.php'?'active':'' ?>"><i class="fa-solid fa-cloud-arrow-up"></i> Aktualizacje (OTA)</a>
      <?php endif; ?>
      
      <div class="sb-label">Konfiguracja</div>
      <?php if(hasPerm('system')): ?>
      <a href="settings.php" class="sb-link <?= $page=='settings.php'?'active':'' ?>"><i class="fa-solid fa-power-off"></i> Przerwa Techniczna</a>
      <a href="db_rescue.php" class="sb-link <?= $page=='db_rescue.php'?'active':'' ?>"><i class="fa-solid fa-code"></i> Naprawa Bazy</a>
      <?php endif; ?>
      
      <?php if(hasPerm('content')): ?>
      <a href="sponsors.php" class="sb-link <?= $page=='sponsors.php'?'active':'' ?>"><i class="fa-solid fa-handshake"></i> Zarządzanie Sponsorami</a>
      <a href="content.php" class="sb-link <?= $page=='content.php'?'active':'' ?>"><i class="fa-solid fa-pen-to-square"></i> Edytor CMS</a>
      <?php endif; ?>

      <?php if(hasPerm('system')): ?>
      <a href="server_health.php" class="sb-link <?= $page=='server_health.php'?'active':'' ?>"><i class="fa-solid fa-server"></i> Zdrowie Serwera</a>
      <a href="logs.php" class="sb-link <?= $page=='logs.php'?'active':'' ?>"><i class="fa-solid fa-file-lines"></i> Logi Nginx</a>
      <?php endif; ?>

      <div class="sb-label" style="margin-top:20px">Bezpieczeństwo</div>
      <?php if($is_super): ?>
      <a href="users.php" class="sb-link <?= $page=='users.php'?'active':'' ?>" style="color:var(--warn)"><i class="fa-solid fa-user-shield"></i> Konta Adminów</a>
      <?php endif; ?>
      
      <a href="logout.php" class="sb-link" style="color:var(--danger)"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj Się</a>
    </div>
  </div>
  <div class="main">
