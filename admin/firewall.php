<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ip'])) {
    $ip = trim($_POST['ip']);
    $r = trim($_POST['reason']??'Sprzętowy spam / Blokada root');
    if(filter_var($ip, FILTER_VALIDATE_IP)) {
        try {
            $pdo->prepare("INSERT INTO ip_blacklist (ip_address, reason) VALUES (?,?)")->execute([$ip, $r]);
            header('Location: firewall.php?msg=added'); exit;
        } catch(PDOException $e) { /* duplicate */ header('Location: firewall.php?msg=exist'); exit; }
    }
}

if(isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM ip_blacklist WHERE id=?")->execute([$_GET['delete']]);
    header('Location: firewall.php'); exit;
}

$ips = $pdo->query("SELECT * FROM ip_blacklist ORDER BY created_at DESC")->fetchAll();

include 'includes/header.php';
?>
<div class="ph">
  <div class="ph-title">System Firewall (IP Blacklist)</div>
  <div class="ph-sub">Twarde odrzucanie żądań nałożone na zewnętrzny ruch do Pachołków i API.</div>
</div>

<?php if(isset($_GET['msg'])): ?>
<div class="alert <?= $_GET['msg']=='added'?'alert-g':'alert-r' ?>" style="padding:16px;background:var(--surface);border-radius:var(--rs);margin-bottom:20px;font-weight:700">
  <?= $_GET['msg']=='added'?'✓ IP zostało zatrzaśnięte w zaporze.':'✕ Ten adres IP już znajduje się na liście.' ?>
</div>
<?php endif; ?>

<div class="grid" style="display:grid;grid-template-columns:1fr 2fr;gap:20px">
  <div class="card" style="border-left:4px solid var(--danger)">
    <div class="card-hd" style="color:var(--danger)"><i class="fa-solid fa-ban"></i> Zablokuj IP natychmiast</div>
    <form method="POST">
      <div class="fg" style="margin-bottom:14px">
        <label>Adres IPV4 v IPV6</label>
        <input type="text" name="ip" required placeholder="np. 192.168.1.55" style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:var(--danger);font-family:'Fira Code',monospace;font-weight:700;border-radius:6px">
      </div>
      <div class="fg" style="margin-bottom:20px">
        <label>Powód nałożenia dożywotniej blokady</label>
        <input type="text" name="reason" placeholder="Opcjonalnie: Atak DDoS, Przechwycone żądanie" style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px">
      </div>
      <button type="submit" class="btn btn-d" style="width:100%;justify-content:center"><i class="fa-solid fa-lock"></i> Nałóż Cenzurę (Ban IP)</button>
    </form>
  </div>
  
  <div class="card">
    <div class="card-hd">Inwigilowane / Zablokowane Adresy (Cenzura)</div>
    <table>
      <tr><th>ID</th><th>Adres IP Zbanowany</th><th>Powód / Notatka Oficera</th><th>Data Zablokowania</th><th>Akcja</th></tr>
      <?php foreach($ips as $ip): ?>
      <tr>
        <td style="color:var(--muted)">#<?= $ip['id'] ?></td>
        <td style="font-weight:800;font-size:1.1rem;color:var(--danger);font-family:'Fira Code',monospace"><?= htmlspecialchars($ip['ip_address']) ?></td>
        <td style="font-size:.8rem;color:var(--muted)"><?= htmlspecialchars($ip['reason']) ?></td>
        <td class="time-mono" style="font-size:.8rem;color:var(--muted)"><?= date('d.m.Y H:i', strtotime($ip['created_at'])) ?></td>
        <td><a href="firewall.php?delete=<?= $ip['id'] ?>" class="btn btn-g btn-sm">Odbanuj</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($ips)): ?>
      <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:20px">Zapora jest czysta. Ruch przepływa swobodnie.</td></tr>
      <?php endif; ?>
    </table>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
