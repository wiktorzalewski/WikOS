<?php
session_start(); require_once 'includes/auth.php'; require_once 'db.php';
$page_title = 'Pachołki'; $current_page = 'devices';

// Delete device
if(isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM devices WHERE id=? AND account_id=?")->execute([$_GET['delete'],$uid]);
    header('Location: devices.php?msg=deleted'); exit;
}

// Register / add device
if($_SERVER['REQUEST_METHOD']==='POST') {
    $name     = trim($_POST['name']??'Pachołek #1');
    $reg_code = strtoupper(trim($_POST['reg_code']??''));
    $hotspot  = trim($_POST['hotspot_name']??'');
    $hotpass  = trim($_POST['hotspot_password']??'');
    $id       = (int)($_POST['id']??0);

    if($id) {
        // Update
        $pdo->prepare("UPDATE devices SET name=?, hotspot_name=?, hotspot_password=? WHERE id=? AND account_id=?")
            ->execute([$name, $hotspot, $hotpass, $id, $uid]);
    } else {
        // Add new device by reg_code
        if(empty($reg_code)) {
            header('Location: devices.php?msg=empty_code'); exit;
        }
        $exists = $pdo->prepare("SELECT id FROM devices WHERE reg_code=?");
        $exists->execute([$reg_code]);
        if($exists->fetch()) {
            header('Location: devices.php?msg=code_used'); exit;
        }

        $api_key = bin2hex(random_bytes(32));
        $pdo->prepare("INSERT INTO devices (account_id, name, reg_code, api_key, hotspot_name, hotspot_password, status) VALUES (?,?,?,?,?,?,'active')")
            ->execute([$uid, $name, $reg_code, $api_key, $hotspot, $hotpass]);
    }
    header('Location: devices.php?msg=saved'); exit;
}

$edit = null;
if(isset($_GET['edit'])) { $s=$pdo->prepare("SELECT * FROM devices WHERE id=? AND account_id=?"); $s->execute([$_GET['edit'],$uid]); $edit=$s->fetch(); }

$devices = $pdo->prepare("SELECT * FROM devices WHERE account_id=? ORDER BY created_at DESC");
$devices->execute([$uid]); $devices_list=$devices->fetchAll();

include 'includes/header.php'; include 'includes/sidebar.php';
?>
<div class="ph">
  <div><div class="ph-title">Pachołki</div><div class="ph-sub">Zarządzanie urządzeniami pomiarowymi</div></div>
  <button class="btn btn-p" id="toggle-form"><i class="fa-solid fa-satellite-dish"></i> Dodaj pachołek</button>
</div>
<?php if(isset($_GET['msg'])): ?><div class="alert <?= $_GET['msg']==='saved'?'alert-g':'alert-r' ?>" style="margin-bottom:16px"><?= $_GET['msg']==='saved'?'✓ Pachołek zapisany.':'✓ Pachołek usunięty.' ?></div><?php endif; ?>

<!-- ADD FORM -->
<div id="dev-form" class="card" style="margin-bottom:20px;<?= ($edit||isset($_GET['add']))?'':'display:none' ?>">
  <div class="card-hd">
    <span class="card-title"><?= $edit?'Edytuj pachołek':'Zarejestruj nowy pachołek' ?></span>
    <button class="btn btn-g btn-sm" onclick="document.getElementById('dev-form').style.display='none'">✕</button>
  </div>
  <?php if(!$edit): ?>
  <div class="alert alert-g" style="margin-bottom:16px">
    <strong>Jak to działa?</strong><br>1. Zresetowany Pachołek pokaże na ekranie 6-znakowy kod (np. K9F-1X2).<br>2. Przepisz go poniżej.<br>3. Podaj docelową sieć WiFi, do której Pachołek ma się następnie połączyć i pracować.
  </div>
  <?php endif; ?>
  <form method="POST">
    <input type="hidden" name="id" value="<?= $edit['id']??'' ?>">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <?php if(!$edit): ?>
      <div class="fg" style="grid-column:1/3">
        <label>Kod z ekranu urządzenia (Reg Code)</label>
        <input type="text" name="reg_code" placeholder="Napisany na Pachołku, np. ABC-123" required style="font-family:'Fira Code',monospace;font-size:1.2rem;letter-spacing:2px;text-transform:uppercase">
      </div>
      <?php endif; ?>
      <div class="fg" style="grid-column:1/3"><label>Nazwa w panelu</label><input type="text" name="name" value="<?= htmlspecialchars($edit['name']??'Pachołek #1') ?>"></div>
      <div class="fg"><label>Docelowe (Nowe) WiFi (SSID)</label><input type="text" name="hotspot_name" value="<?= htmlspecialchars($edit['hotspot_name']??'') ?>" placeholder="Sieć na stadionie"></div>
      <div class="fg"><label>Hasło WiFi</label><input type="text" name="hotspot_password" value="<?= htmlspecialchars($edit['hotspot_password']??'') ?>" placeholder="min. 8 znaków"></div>
    </div>
    <button type="submit" class="btn btn-p" style="margin-top:14px"><i class="fa-solid fa-floppy-disk"></i> Zapisz i Powiąż Pachołek</button>
  </form>
</div>

<!-- DEVICES LIST -->
<?php if(empty($devices_list)): ?>
<div class="card"><div class="empty"><i class="fa-solid fa-satellite-dish"></i><p>Brak zarejestrowanych pachołków.<br>Dodaj pierwsze urządzenie.</p></div></div>
<?php else: ?>
<div style="display:grid;gap:14px">
<?php foreach($devices_list as $d):
  $ago = $d['last_seen'] ? floor((time()-strtotime($d['last_seen']))/60) : null;
  $online = $ago !== null && $ago < 3;
  $statusColor = $online?'var(--green)':($d['status']==='pending'?'var(--warn)':'var(--muted)');
?>
<div class="card">
  <div style="display:grid;grid-template-columns:1fr auto;gap:20px;align-items:start">
    <div>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
        <div style="width:10px;height:10px;border-radius:50%;background:<?= $statusColor ?>;flex-shrink:0;box-shadow:0 0 8px <?= $statusColor ?>"></div>
        <span style="font-size:1.05rem;font-weight:700"><?= htmlspecialchars($d['name']) ?></span>
        <span class="badge <?= $online?'bdg-g':($d['status']==='pending'?'bdg-y':'bdg-grey') ?>"><?= $d['status'] ?></span>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px">
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--rs);padding:10px">
          <div style="font-size:9px;color:var(--muted);font-weight:700;letter-spacing:.15em;text-transform:uppercase">Kod rejestracyjny</div>
          <div style="font-family:'Fira Code',monospace;font-size:1.2rem;font-weight:700;color:var(--green);letter-spacing:.2em"><?= $d['reg_code'] ?></div>
        </div>
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--rs);padding:10px">
          <div style="font-size:9px;color:var(--muted);font-weight:700;letter-spacing:.15em;text-transform:uppercase">Ostatnio widziany</div>
          <div style="font-size:.85rem;font-weight:600"><?= $online?'Online teraz':($ago!==null?"{$ago} min temu":'Nigdy') ?></div>
        </div>
        <?php if($d['hotspot_name']): ?>
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--rs);padding:10px">
          <div style="font-size:9px;color:var(--muted);font-weight:700;letter-spacing:.15em;text-transform:uppercase">Hotspot WiFi</div>
          <div style="font-size:.85rem;font-weight:600"><?= htmlspecialchars($d['hotspot_name']) ?></div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Safe times -->
      <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
        <span style="font-size:9px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.1em;align-self:center">Safe-time:</span>
        <?php foreach([60,100,200,400] as $dist): $key="safe_time_$dist"; ?>
        <span class="badge bdg-grey"><?= $dist ?>m: <?= number_format($d[$key]/1000,1) ?>s</span>
        <?php endforeach; ?>
        <a href="settings.php" style="font-size:9px;color:var(--green);align-self:center;text-decoration:none">Zmień →</a>
      </div>

      <?php if($d['last_ip']): ?>
      <div style="margin-top:8px;font-size:.78rem;color:var(--muted)">IP: <?= $d['last_ip'] ?></div>
      <?php endif; ?>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px">
      <a href="devices.php?edit=<?= $d['id'] ?>" class="btn btn-g btn-sm"><i class="fa-solid fa-pen"></i> Edytuj</a>
      <a href="devices.php?delete=<?= $d['id'] ?>" onclick="return confirm('Usunąć pachołek?')" class="btn btn-d btn-sm"><i class="fa-solid fa-trash"></i> Usuń</a>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
document.getElementById('toggle-form').addEventListener('click', ()=>{
  const f=document.getElementById('dev-form');
  f.style.display=f.style.display==='none'?'block':'none';
});
<?php if(isset($_GET['add'])): ?> document.getElementById('dev-form').style.display='block'; <?php endif; ?>
</script>
<?php include 'includes/footer.php'; ?>
