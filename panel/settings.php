<?php
session_start(); require_once 'includes/auth.php'; require_once 'db.php';
$page_title = 'Ustawienia'; $current_page = 'settings';

$success = '';

// Get first device for safe-time config
$dev = $pdo->prepare("SELECT * FROM devices WHERE account_id=? ORDER BY created_at ASC LIMIT 1");
$dev->execute([$uid]); $device = $dev->fetch();

if($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';

    if($action === 'password') {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $acc = $pdo->prepare("SELECT * FROM accounts WHERE id=?"); $acc->execute([$uid]); $acc=$acc->fetch();
        if(!password_verify($old, $acc['password'])) {
            $error = 'Stare hasło nieprawidłowe.';
        } elseif(strlen($new)<6) {
            $error = 'Nowe hasło min. 6 znaków.';
        } else {
            $pdo->prepare("UPDATE accounts SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_BCRYPT),$uid]);
            $success = 'Hasło zmienione pomyślnie.';
        }
    }

    if($action === 'safetimes' && $device) {
        $st60  = max(1000,(int)$_POST['st_60']*1000);
        $st100 = max(1000,(int)$_POST['st_100']*1000);
        $st200 = max(1000,(int)$_POST['st_200']*1000);
        $st400 = max(1000,(int)$_POST['st_400']*1000);
        $stCust = max(1000,(int)$_POST['st_custom']*1000);
        $pdo->prepare("UPDATE devices SET safe_time_60=?,safe_time_100=?,safe_time_200=?,safe_time_400=?,safe_time_custom=? WHERE account_id=?")
            ->execute([$st60,$st100,$st200,$st400,$stCust,$uid]);
        $success = 'Safe-time zaktualizowane.';
        $dev->execute([$uid]); $device = $dev->fetch();
    }

    if($action === 'profile') {
        $name = trim($_POST['name']??'');
        if($name) {
            $pdo->prepare("UPDATE accounts SET name=? WHERE id=?")->execute([$name,$uid]);
            $_SESSION['account_name'] = $name; $uname = $name;
            $success = 'Profil zaktualizowany.';
        }
    }
}

// Account info
$acc_info = $pdo->prepare("SELECT * FROM accounts WHERE id=?"); $acc_info->execute([$uid]); $acc_info=$acc_info->fetch();

include 'includes/header.php'; include 'includes/sidebar.php';
?>
<div class="ph">
  <div><div class="ph-title">Ustawienia</div><div class="ph-sub">Konto, pachołki i konfiguracja</div></div>
</div>

<?php if(isset($success) && $success): ?><div class="alert alert-g" style="margin-bottom:16px">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if(isset($error) && $error): ?><div class="alert alert-r" style="margin-bottom:16px"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="grid-2" style="gap:20px">
  <!-- Profile -->
  <div class="card">
    <div class="card-hd"><span class="card-title">Profil konta</span></div>
    <form method="POST">
      <input type="hidden" name="action" value="profile">
      <div class="fg"><label>Imię i nazwisko</label><input type="text" name="name" value="<?= htmlspecialchars($acc_info['name']??'') ?>"></div>
      <div class="fg"><label>E-mail</label><input type="email" value="<?= htmlspecialchars($acc_info['email']??'') ?>" disabled style="opacity:.5;cursor:not-allowed"></div>
      <button type="submit" class="btn btn-p btn-sm"><i class="fa-solid fa-floppy-disk"></i> Zapisz</button>
    </form>
  </div>

  <!-- Change password -->
  <div class="card">
    <div class="card-hd"><span class="card-title">Zmiana hasła</span></div>
    <form method="POST">
      <input type="hidden" name="action" value="password">
      <div class="fg"><label>Stare hasło</label><input type="password" name="old_password" required></div>
      <div class="fg"><label>Nowe hasło</label><input type="password" name="new_password" required minlength="6"></div>
      <button type="submit" class="btn btn-g btn-sm"><i class="fa-solid fa-lock"></i> Zmień hasło</button>
    </form>
  </div>

  <!-- Safe times -->
  <div class="card" style="grid-column:1/3">
    <div class="card-hd">
      <span class="card-title">Safe-time dla dystansów</span>
      <span style="font-size:.75rem;color:var(--muted)">Czas w trakcie którego przerwanie wiązki jest ignorowane</span>
    </div>
    <?php if(!$device): ?>
    <div class="alert alert-r">Brak zarejestrowanego pachołka. <a href="devices.php?add=1" style="color:inherit;font-weight:700">Dodaj pachołek →</a></div>
    <?php else: ?>
    <form method="POST">
      <input type="hidden" name="action" value="safetimes">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:14px">
        <?php foreach([60,100,200,400] as $d): $key="safe_time_$d"; ?>
        <div class="fg">
          <label><?= $d ?>m (sekundy)</label>
          <input type="number" name="st_<?= $d ?>" value="<?= round($device[$key]/1000) ?>" min="1" max="120" step="1">
        </div>
        <?php endforeach; ?>
        <div class="fg" style="border-left:2px solid var(--primary);padding-left:10px">
          <label style="color:var(--primary)">Inne dystanse</label>
          <input type="number" name="st_custom" value="<?= round(($device['safe_time_custom']??8000)/1000) ?>" title="Domyślny bezpieczny czas dla dystansów innych niż standardowe" min="1" max="120" step="1">
          <div style="font-size:10px;color:var(--muted);margin-top:4px">Zabezpieczenie domyślne</div>
        </div>
      </div>
      <button type="submit" class="btn btn-p" style="margin-top:14px"><i class="fa-solid fa-floppy-disk"></i> Zapisz safe-time</button>
    </form>
    <?php endif; ?>
  </div>

  <!-- Info -->
  <div class="card" style="grid-column:1/3">
    <div class="card-hd"><span class="card-title">Informacje systemowe</span></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
      <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--rs);padding:14px">
        <div style="font-size:9px;color:var(--muted);font-weight:700;letter-spacing:.15em;text-transform:uppercase;margin-bottom:4px">Czas serwera</div>
        <div style="font-family:'Fira Code',monospace;font-size:.9rem;font-weight:600" id="srv-time"><?= date('H:i:s') ?></div>
      </div>
      <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--rs);padding:14px">
        <div style="font-size:9px;color:var(--muted);font-weight:700;letter-spacing:.15em;text-transform:uppercase;margin-bottom:4px">Strefa</div>
        <div style="font-size:.9rem;font-weight:600">Europe/Warsaw</div>
      </div>
      <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--rs);padding:14px">
        <div style="font-size:9px;color:var(--muted);font-weight:700;letter-spacing:.15em;text-transform:uppercase;margin-bottom:4px">API Endpoint</div>
        <div style="font-family:'Fira Code',monospace;font-size:.78rem;color:var(--green)">api.<?= $_SERVER['HTTP_HOST']??'wikos.run' ?></div>
      </div>
      <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--rs);padding:14px">
        <div style="font-size:9px;color:var(--muted);font-weight:700;letter-spacing:.15em;text-transform:uppercase;margin-bottom:4px">Wersja</div>
        <div style="font-size:.9rem;font-weight:600">WikOS v1.0</div>
      </div>
    </div>
  </div>
</div>

<script>
setInterval(()=>{
  const n=new Date();
  document.getElementById('srv-time').textContent=String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0')+':'+String(n.getSeconds()).padStart(2,'0');
},1000);
</script>
<?php include 'includes/footer.php'; ?>
