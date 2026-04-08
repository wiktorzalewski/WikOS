<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

$stats = [
    'accounts' => $pdo->query("SELECT COUNT(*) FROM accounts")->fetchColumn(),
    'athletes' => $pdo->query("SELECT COUNT(*) FROM athletes")->fetchColumn(),
    'results'  => $pdo->query("SELECT COUNT(*) FROM results")->fetchColumn(),
    'devices'  => $pdo->query("SELECT COUNT(*) FROM devices")->fetchColumn(),
];

// Ostatnie logowania trenerów (lub ostatnio utworzone rekordy, jesli brakuje logowania db)
$last_accounts = $pdo->query("SELECT * FROM accounts ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<?php include 'includes/header.php'; ?>
<div class="ph">
  <div class="ph-title">System w pigułce</div>
</div>

<div class="stats-grid">
  <div class="stat"><div class="stat-lbl">Trenerzy</div><div class="stat-val"><?= $stats['accounts'] ?></div></div>
  <div class="stat"><div class="stat-lbl">Zawodnicy</div><div class="stat-val"><?= $stats['athletes'] ?></div></div>
  <div class="stat"><div class="stat-lbl">Zmierzone czasy</div><div class="stat-val" style="color:var(--primary)"><?= $stats['results'] ?></div></div>
  <div class="stat"><div class="stat-lbl">Aktywne pachołki</div><div class="stat-val"><?= $stats['devices'] ?></div></div>
</div>

<div class="card">
  <div class="card-hd">Narzędzia serwerowe</div>
  <div style="display:flex;gap:12px">
    <button onclick="alert('Zrzut bazy w trakcie prac integracyjnych.')" class="btn btn-p"><i class="fa-solid fa-database"></i> Pobierz SQL Backup</button>
  </div>
</div>

<div class="card">
  <div class="card-hd">Ostatnio dołączyli</div>
  <table>
    <tr><th>ID</th><th>Imię i Nazwisko</th><th>Email</th><th>Zarejestrowano</th></tr>
    <?php foreach($last_accounts as $acc): ?>
    <tr>
      <td style="color:var(--muted)">#<?= $acc['id'] ?></td>
      <td style="font-weight:700"><?= htmlspecialchars($acc['name']) ?></td>
      <td><?= htmlspecialchars($acc['email']) ?></td>
      <td class="time-mono" style="font-size:.8rem;color:var(--muted)"><?= $acc['created_at'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php include 'includes/footer.php'; ?>
