<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

$d_logs = $pdo->query("
    SELECT l.*, d.name, acc.name as trainer_name 
    FROM device_logs l
    JOIN devices d ON l.device_id=d.id
    LEFT JOIN accounts acc ON d.account_id=acc.id
    ORDER BY l.created_at DESC LIMIT 100
")->fetchAll();

$nginx_error = '';
if(is_readable('/var/log/nginx/error.log')) {
    $out = shell_exec('tail -n 50 /var/log/nginx/error.log');
    $nginx_error = $out ? $out : 'Brak błędów Nginx w ostatnich 50 liniach.';
} else {
    $nginx_error = 'Brak uprawnień odczytu do /var/log/nginx/error.log. Dodaj użytkownika ww-data do grupy adm lub uruchom tunel bezpieczniej.';
}

include 'includes/header.php';
?>
<div class="ph">
  <div class="ph-title">System Logów & Awarie</div>
  <div class="ph-sub">Śledzenie awarii środowiska Nginx oraz logów działania API na urządzeniach fizycznych.</div>
</div>

<div class="grid" style="display:grid;grid-template-columns:1fr;gap:20px">
  <div class="card" style="border-left:4px solid var(--danger)">
    <div class="card-hd" style="color:var(--danger)"><i class="fa-solid fa-server"></i> Ostatnie błędy Nginx (/var/log/nginx/error.log)</div>
    <div style="background:var(--bg2);color:var(--danger);font-family:'Fira Code',monospace;font-size:11px;padding:16px;border-radius:6px;border:1px solid var(--border);height:280px;overflow-y:auto;white-space:pre-wrap"><?= htmlspecialchars($nginx_error) ?></div>
  </div>

  <div class="card">
    <div class="card-hd"><i class="fa-solid fa-microchip"></i> Audyt logowań Urządzeń (API Device Logs)</div>
    <div class="tbl-wrap" style="overflow-x:auto">
      <table>
        <tr><th>Kiedy</th><th>Pachołek</th><th>Adres IP</th><th>Akcja</th><th>Szczegóły</th></tr>
        <?php foreach($d_logs as $l): ?>
        <tr>
          <td class="time-mono" style="font-size:.75rem;color:var(--muted)"><?= $l['created_at'] ?></td>
          <td style="font-weight:700"><?= htmlspecialchars($l['name']) ?><br><span style="font-size:10px;color:var(--muted)"><?= htmlspecialchars($l['trainer_name']) ?></span></td>
          <td style="font-family:'Fira Code',font-size:.8rem;color:var(--primary)"><?= $l['ip_address'] ?></td>
          <td><span class="badge" style="background:var(--surface)"><?= htmlspecialchars($l['action_type']) ?></span></td>
          <td style="font-size:.8rem;color:var(--muted)"><?= htmlspecialchars($l['details']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($d_logs)): ?>
        <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--muted)">Pachołki obecnie nie wygenerowały żadnych podejrzanych logów komunikacji.</td></tr>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
