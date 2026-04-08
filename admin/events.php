<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM events WHERE id=?")->execute([$_GET['delete']]);
    header('Location: events.php'); exit;
}

$events = $pdo->query("
    SELECT e.*, acc.name as trainer_name,
    (SELECT COUNT(*) FROM results r WHERE r.event_id=e.id) as result_count
    FROM events e
    JOIN accounts acc ON e.account_id=acc.id
    ORDER BY e.date DESC, e.created_at DESC
")->fetchAll();

include 'includes/header.php';
?>
<div class="ph">
  <div class="ph-title"><i class="fa-solid fa-calendar-check"></i> Przegląd Sesji Treningowych</div>
  <div class="ph-sub">Lista nadanych spotkań zorganizowanych przez Twoich trenerów, wraz z ilością przypisanych czasów.</div>
</div>

<div class="card">
  <div class="card-hd">Wszystkie aktywne i minione wydarzenia z bazy</div>
  <div class="tbl-wrap" style="overflow-x:auto">
    <table>
      <tr><th>Wydarzenie</th><th>Trener</th><th>Lokalizacja</th><th>Pomiary</th><th>Data Utworzenia</th><th>Akcja</th></tr>
      <?php foreach($events as $ev): ?>
      <tr>
        <td>
          <div style="font-weight:800;color:var(--text);font-size:1.1rem"><?= htmlspecialchars($ev['name']) ?></div>
          <div class="time-mono" style="font-size:.8rem;color:var(--primary);margin-top:4px"><i class="fa-solid fa-calendar-day"></i> <?= date('d.m.Y', strtotime($ev['date'])) ?></div>
        </td>
        <td style="font-weight:600;color:var(--muted)"><i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($ev['trainer_name']) ?></td>
        <td style="color:var(--muted)"><?= $ev['location'] ? htmlspecialchars($ev['location']) : '—' ?></td>
        <td><span class="badge" style="background:var(--surface);color:var(--text)"><?= $ev['result_count'] ?> zebranych wyników</span></td>
        <td class="time-mono" style="font-size:.8rem;color:var(--muted)"><?= date('d.m.Y H:i:s', strtotime($ev['created_at'])) ?></td>
        <td>
          <a href="events.php?delete=<?= $ev['id'] ?>" class="btn btn-d btn-sm" onclick="return confirm('Trwale usunąć tę sesję i wypiąć z niej przynależne pomiary?')"><i class="fa-solid fa-trash"></i></a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(count($events)===0): ?>
      <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--muted)">Trenerzy nie otworzyli jeszcze żadnych sesji ani treningów.</td></tr>
      <?php endif; ?>
    </table>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
