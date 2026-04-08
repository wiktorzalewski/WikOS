<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM devices WHERE id=?")->execute([$_GET['delete']]);
    header('Location: devices.php'); exit;
}

if(isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if($_GET['action'] == 'ota_toggle') {
        $pdo->prepare("UPDATE devices SET ota_channel = IF(ota_channel='stable', 'beta', 'stable') WHERE id=?")->execute([$id]);
    }
    if($_GET['action'] == 'hard_reset') {
        $pdo->prepare("UPDATE devices SET pending_action='factory_reset' WHERE id=?")->execute([$id]);
    }
    header('Location: devices.php'); exit;
}

$devices = $pdo->query("
    SELECT d.*, a.name as trainer_name, a.email as trainer_email 
    FROM devices d 
    LEFT JOIN accounts a ON d.account_id = a.id 
    ORDER BY d.last_seen DESC
")->fetchAll();

include 'includes/header.php';
?>
<div class="ph">
  <div class="ph-title">System Pomiarowy — Urządzenia</div>
</div>

<div class="card">
  <div class="card-hd">Wszystkie Podłączone Pachołki</div>
  <div class="tbl-wrap" style="overflow-x:auto">
    <table>
      <tr><th>ID</th><th>Nazwa & Kod (Własność)</th><th>Stan & Reguła (OTA)</th><th>Status & Ostatnie Logowanie</th><th>IP</th><th>Akcje Sprzętowe</th></tr>
      <?php foreach($devices as $dev): 
        $ago = $dev['last_seen'] ? floor((time()-strtotime($dev['last_seen']))/60) : null;
        $online = $ago !== null && $ago < 3;
        $statusBadge = $online ? '<span class="badge" style="background:rgba(16,185,129,.15);color:var(--success)">ONLINE</span>' : 
                      ($dev['status']=='pending' ? '<span class="badge" style="background:rgba(245,158,11,.15);color:var(--warn)">OCZEKUJE</span>' :
                      '<span class="badge" style="background:rgba(255,255,255,.05);color:var(--muted)">OFFLINE</span>');
      ?>
      <tr>
        <td style="color:var(--muted)">#<?= $dev['id'] ?></td>
        <td>
          <div style="font-weight:800"><?= htmlspecialchars($dev['name']) ?> <span style="font-family:'Fira Code',monospace;color:var(--primary);font-size:.8rem;background:var(--surface);padding:2px 6px;border-radius:6px;margin-left:6px"><?= $dev['reg_code'] ?></span></div>
          <div style="font-size:.75rem;color:var(--muted)"><?= $dev['trainer_name'] ? htmlspecialchars($dev['trainer_name']) : 'Brak przypisania' ?></div>
        </td>
        <td style="font-size:.8rem">
          <div><i class="fa-solid fa-wifi" style="color:var(--muted)"></i> <?= $dev['hotspot_name'] ? htmlspecialchars($dev['hotspot_name']) : '—' ?></div>
          <div style="margin-top:4px"><span class="badge" style="background:<?= $dev['ota_channel']=='beta'?'var(--warn)':'var(--surface)' ?>"><i class="fa-solid fa-code-branch"></i> <?= strtoupper($dev['ota_channel']) ?> OTA</span></div>
        </td>
        <td>
          <?= $statusBadge ?>
          <?php if($dev['pending_action']=='factory_reset'): ?><div class="badge" style="background:var(--danger);color:#fff;margin-top:4px"><i class="fa-solid fa-skull"></i> WAITING FACTORY RESET</div><?php endif; ?>
          <div class="time-mono" style="font-size:.75rem;color:var(--muted);margin-top:4px"><?= $dev['last_seen'] ? date('d.m.Y H:i:s', strtotime($dev['last_seen'])) : 'Nigdy' ?></div>
        </td>
        <td style="font-family:'Fira Code',monospace;font-size:.78rem;color:var(--muted)"><?= $dev['last_ip'] ?: '—' ?></td>
        <td>
          <a href="devices.php?action=ota_toggle&id=<?= $dev['id'] ?>" class="btn btn-g btn-sm" title="Zmień kanał OTA (Beta / Stable)"><i class="fa-solid fa-shuffle"></i></a>
          <a href="devices.php?action=hard_reset&id=<?= $dev['id'] ?>" class="btn btn-d btn-sm" title="Wyślij sygnał samozniszczenia i Factory Resetu na zapalnik Pachołka (API)" onclick="return confirm('WYSŁAĆ SYGNAŁ FACTORY RESET? Urządzenie sprzętowo usunie sieć WiFi przy następnym pingu!')"><i class="fa-solid fa-power-off"></i></a>
          <a href="devices.php?delete=<?= $dev['id'] ?>" class="btn btn-d btn-sm" title="Revoke API Key (Usuń całkowicie)" onclick="return confirm('Spalić API Key urządzenia całkowicie kasując certyfikat (ban)?')"><i class="fa-solid fa-plug-circle-xmark"></i></a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
