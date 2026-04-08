<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM results WHERE id=?")->execute([$_GET['delete']]);
    header('Location: results.php?msg=deleted'); exit;
}

// Fetch all results, with limit for safety and performance
$results = $pdo->query("
    SELECT r.*, a.first_name, a.last_name, acc.name as trainer_name
    FROM results r
    JOIN athletes a ON r.athlete_id=a.id
    JOIN accounts acc ON r.account_id=acc.id
    ORDER BY r.created_at DESC LIMIT 500
")->fetchAll();

function fmt($ms){if(!$ms)return '—';$cs=floor($ms/10)%100;$sec=floor($ms/1000);$min=floor($sec/60);$sec%=60;return $min>0?sprintf('%d:%02d.%02d',$min,$sec,$cs):sprintf('%d.%02d',$sec,$cs);}

include 'includes/header.php';
?>
<div class="ph">
  <div class="ph-title">Audyt Wyników i Biegów</div>
  <div class="ph-sub">Baza 500 najświeższych wyników w całym systemie z możliwością globalnego kasowania anulowanych biegów.</div>
</div>

<?php if(isset($_GET['msg'])): ?>
<div class="alert alert-g" style="padding:16px;background:var(--danger);color:#fff;border-radius:var(--rs);margin-bottom:20px;font-weight:700"><i class="fa-solid fa-trash"></i> Wynik trwale zlikwidowany.</div>
<?php endif; ?>

<div class="card">
  <div class="card-hd">Moderacja Czasów (Wyniki)</div>
  <div style="margin-bottom:16px"><a href="#" class="btn btn-g btn-sm"><i class="fa-solid fa-file-csv"></i> Eksportuj widoczne do CSV</a></div>
  
  <div class="tbl-wrap" style="overflow-x:auto">
    <table>
      <tr><th>ID</th><th>Kiedy</th><th>Zawodnik</th><th>Trener Raportujący</th><th>Źródło</th><th>Dystans = Czas</th><th>Akcje</th></tr>
      <?php foreach($results as $r): ?>
      <tr>
        <td style="color:var(--muted)">#<?= $r['id'] ?></td>
        <td class="time-mono" style="font-size:.78rem;color:var(--text)"><?= date('d.m.Y H:i:s', strtotime($r['created_at'])) ?></td>
        <td style="font-weight:700"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></td>
        <td style="font-size:.8rem;color:var(--muted)"><i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($r['trainer_name']) ?></td>
        <td>
            <?= $r['source']==='device' ? '<span class="badge" style="background:var(--green-dim);color:var(--green)"><i class="fa-solid fa-satellite-dish"></i> Optyka Pachołka</span>' : '<span class="badge" style="background:rgba(255,255,255,.05);color:var(--muted)"><i class="fa-solid fa-hand-pointer"></i> Ręczny stoper</span>' ?>
        </td>
        <td style="font-family:'Fira Code',font-weight:800;color:var(--primary);font-size:1.1rem">
            <?= $r['distance_m'] ?>m <i class="fa-solid fa-arrow-right" style="font-size:.7rem;margin:0 4px"></i> <span style="color:var(--green)"><?= fmt($r['time_ms']) ?></span>
            <?php if($r['notes']): ?><div style="font-size:10px;color:var(--warn);font-family:'Space Grotesk'"><i class="fa-solid fa-message"></i> <?= htmlspecialchars($r['notes']) ?></div><?php endif; ?>
        </td>
        <td>
          <a href="results.php?delete=<?= $r['id'] ?>" class="btn btn-d btn-sm" onclick="return confirm('Spalić ten wynik globalnie? Zniknie z publicznej tabeli rekodrów.')"><i class="fa-solid fa-trash"></i> Spal w wynikach</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
