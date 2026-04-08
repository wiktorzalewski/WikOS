<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ver = trim($_POST['version'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if(!empty($ver) && !empty($_FILES['firmware']['name'])) {
        $ext = strtolower(pathinfo($_FILES['firmware']['name'], PATHINFO_EXTENSION));
        // Allow bin (ESP32/Arduino) or zip/py (RPi Zero)
        if(in_array($ext, ['bin', 'zip', 'gz', 'py', 'sh'])) {
            $dir = '../api.WikOS.run/firmware/';
            if(!is_dir($dir)) mkdir($dir, 0777, true);
            
            $fname = 'wikos_v' . preg_replace('/[^0-9a-zA-Z._-]/', '', $ver) . '_' . time() . '.' . $ext;
            if(move_uploaded_file($_FILES['firmware']['tmp_name'], $dir . $fname)) {
                $pdo->prepare("INSERT INTO system_firmware (version, file_name, release_notes) VALUES (?,?,?)")
                    ->execute([$ver, $fname, $notes]);
                header('Location: ota.php?msg=success'); exit;
            }
        } else {
            header('Location: ota.php?msg=invalid_file'); exit;
        }
    }
}

$history = $pdo->query("SELECT * FROM system_firmware ORDER BY created_at DESC")->fetchAll();
include 'includes/header.php';
?>
<div class="ph">
  <div class="ph-title">Aktualizacje (OTA)</div>
  <div class="ph-sub">Tutaj wgrasz nową wersję softu na pachołki (RPi / ESP).</div>
</div>

<?php if(isset($_GET['msg'])): ?>
<div class="alert <?= $_GET['msg']==='success'?'alert-g':'alert-r' ?>" style="padding:16px;background:var(--surface);border-radius:var(--rs);margin-bottom:20px;font-weight:700">
  <?= $_GET['msg']==='success'?'✓ Nowa wersja dodana do puli pobierania.':'✕ Niedozwolony format pliku (tylko .bin, .zip, .py, .sh).' ?>
</div>
<?php endif; ?>

<div class="grid" style="display:grid;grid-template-columns:1fr 2fr;gap:20px">
  <div class="card" style="border-left:4px solid var(--primary)">
    <div class="card-hd">Wgraj nową wersję</div>
    <form method="POST" enctype="multipart/form-data">
      <div class="fg" style="margin-bottom:14px">
        <label>Numer Wersji programu</label>
        <input type="text" name="version" required placeholder="np. 1.2.4" style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:#fff;border-radius:6px">
      </div>
      <div class="fg" style="margin-bottom:14px">
        <label>Plik aktualizacyjny (.bin, .zip, .py, .sh)</label>
        <input type="file" name="firmware" required style="width:100%;font-size:12px;padding:10px;border:1px dashed var(--border);border-radius:6px;background:var(--surface)">
      </div>
      <div class="fg" style="margin-bottom:20px">
        <label>Lista zmian (Release Notes)</label>
        <textarea name="notes" rows="3" placeholder="Poprawiono błąd z uśpieniem..." style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px"></textarea>
      </div>
      <button type="submit" class="btn btn-p" style="width:100%;justify-content:center"><i class="fa-solid fa-upload"></i> Wyślij na pachołki</button>
      <div style="font-size:10px;text-align:center;margin-top:10px;color:var(--muted)">Urządzenia podczas włączenia pobiorą najnowszy plik na tej liście. (Możesz usiąść i patrzeć).</div>
    </form>
  </div>
  
  <div class="card">
    <div class="card-hd">Wydane wersje (Firmware)</div>
    <table>
      <tr><th>Kompilacja</th><th>Wersja</th><th>Rozmiar/Plik</th><th>Zmiany</th><th>Wydano</th></tr>
      <?php foreach($history as $i => $fw): ?>
      <tr>
        <td style="color:var(--muted)">#<?= $fw['id'] ?> <?= $i===0?'<span class="badge" style="background:var(--green-dim);color:var(--green)">LATEST</span>':'' ?></td>
        <td style="font-weight:800;font-size:1.1rem;color:var(--primary)">v<?= htmlspecialchars($fw['version']) ?></td>
        <td style="font-family:'Fira Code',monospace;font-size:.75rem">
          <?= htmlspecialchars($fw['file_name']) ?><br>
          <?php $path = '../api.WikOS.run/firmware/'.$fw['file_name']; echo file_exists($path)?round(filesize($path)/1024,1).' KB':'Zaginął z dysku'; ?>
        </td>
        <td style="font-size:.8rem;color:var(--muted)"><?= htmlspecialchars($fw['release_notes']) ?></td>
        <td class="time-mono" style="font-size:.8rem;color:var(--muted)"><?= date('d.m.Y H:i', strtotime($fw['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($history)): ?>
      <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:20px">Brak wgranych wersji Oprogramowania.</td></tr>
      <?php endif; ?>
    </table>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
