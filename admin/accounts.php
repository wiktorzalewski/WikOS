<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM accounts WHERE id=?")->execute([$_GET['delete']]);
    header('Location: accounts.php'); exit;
}

$accounts = $pdo->query("
    SELECT a.*, 
    (SELECT COUNT(*) FROM athletes WHERE account_id=a.id) as athletes_count,
    (SELECT COUNT(*) FROM devices WHERE account_id=a.id) as devices_count,
    (SELECT COUNT(*) FROM results WHERE account_id=a.id) as results_count
    FROM accounts a ORDER BY a.created_at DESC
")->fetchAll();

include 'includes/header.php';
?>
<div class="ph">
  <div class="ph-title">Lista Trenerów</div>
</div>

<div class="card">
  <div class="card-hd">Wszystkie zarejestrowane konta</div>
  <div class="tbl-wrap" style="overflow-x:auto">
    <table>
      <tr><th>ID</th><th>Imię i Nazwisko / Email</th><th>Zawodnicy</th><th>Pachołki</th><th>Wyniki</th><th>Data Rejestracji</th><th>Akcje</th></tr>
      <?php foreach($accounts as $acc): ?>
      <tr>
        <td style="color:var(--muted)">#<?= $acc['id'] ?></td>
        <td>
          <div style="font-weight:800"><?= htmlspecialchars($acc['name']) ?></div>
          <div style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars($acc['email']) ?></div>
        </td>
        <td><span class="badge" style="background:var(--surface);border:1px solid var(--border);color:var(--text)"><?= $acc['athletes_count'] ?></span></td>
        <td><span class="badge" style="background:var(--surface);border:1px solid var(--border);color:var(--text)"><?= $acc['devices_count'] ?></span></td>
        <td style="color:var(--primary);font-weight:700"><?= $acc['results_count'] ?></td>
        <td class="time-mono" style="font-size:.8rem;color:var(--muted)"><?= $acc['created_at'] ?></td>
        <td>
          <a href="account.php?id=<?= $acc['id'] ?>" class="btn btn-g btn-sm"><i class="fa-solid fa-pen"></i> Edytuj</a>
          <a href="accounts.php?delete=<?= $acc['id'] ?>" class="btn btn-d btn-sm" onclick="return confirm('Trwale skasować CAŁE konto trenera, wszystkie jego wyniki, pachołki i zawodników?')"><i class="fa-solid fa-trash"></i> Usuń</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
