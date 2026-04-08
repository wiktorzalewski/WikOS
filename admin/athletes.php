<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM athletes WHERE id=?")->execute([$_GET['delete']]);
    header('Location: athletes.php'); exit;
}

$athletes = $pdo->query("
    SELECT ath.*, 
    acc.name as trainer_name,
    (SELECT COUNT(*) FROM results WHERE athlete_id=ath.id) as results_count
    FROM athletes ath 
    JOIN accounts acc ON ath.account_id = acc.id
    ORDER BY ath.created_at DESC
")->fetchAll();

include 'includes/header.php';
?>
<div class="ph">
  <div class="ph-title">Lista Zawodników</div>
  <div class="ph-sub">Podgląd wszystkich profili zarejestrowanych w systemie.</div>
</div>

<div class="card">
  <div class="card-hd" style="display:flex;justify-content:space-between;align-items:center">
      <span>Wszyscy zawodnicy</span>
      <a href="athlete_edit.php" class="btn btn-p btn-sm"><i class="fa-solid fa-plus"></i> Dodaj zawodnika</a>
  </div>
  <div class="tbl-wrap" style="overflow-x:auto">
    <table>
      <tr><th>ID</th><th>Imię i Nazwisko / Email</th><th>Klub / Kat</th><th>Trener Prowadzący</th><th>Biegi</th><th>Zarejestrowany</th><th>Akcje</th></tr>
      <?php foreach($athletes as $ath): ?>
      <tr>
        <td style="color:var(--muted)">#<?= str_pad($ath['id'], 4, '0', STR_PAD_LEFT) ?></td>
        <td>
          <div style="display:flex;align-items:center;gap:12px">
            <div class="av-mini" style="width:32px;height:32px;border-radius:50%;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;font-size:10px;font-weight:800;color:var(--muted)">
              <?php if($ath['photo']): ?>
                <img src="https://panel.lo48.pl/uploads/athletes/<?= $ath['photo'] ?>" style="width:100%;height:100%;object-fit:cover">
              <?php else: ?>
                <?= strtoupper(mb_substr($ath['first_name'],0,1).mb_substr($ath['last_name'],0,1)) ?>
              <?php endif; ?>
            </div>
            <div>
              <div style="font-weight:800;display:flex;align-items:center;gap:6px">
                <?= htmlspecialchars($ath['first_name'].' '.$ath['last_name']) ?>
                <?php if($ath['special_rank'] === 'owner'): ?>
                  <i class="fa-solid fa-crown" style="color:var(--warn);font-size:0.7rem" title="Właściciel"></i>
                <?php elseif($ath['special_rank'] === 'creator'): ?>
                  <i class="fa-solid fa-certificate" style="color:var(--primary);font-size:0.7rem" title="Twórca"></i>
                <?php endif; ?>
              </div>
              <div style="font-size:.7rem;color:var(--muted)"><?= $ath['public_email'] ? htmlspecialchars($ath['public_email']) : 'Brak maila' ?></div>
            </div>
          </div>
        </td>
        <td>
          <div style="font-weight:600"><?= $ath['club'] ? htmlspecialchars($ath['club']) : 'Niezrzeszony' ?></div>
          <div style="font-size:.75rem;color:var(--primary)"><?= $ath['category'] ? htmlspecialchars($ath['category']) : 'Brak kategorii' ?></div>
        </td>
        <td style="color:var(--muted);font-weight:600"><i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($ath['trainer_name']) ?></td>
        <td style="color:var(--green);font-weight:700"><?= $ath['results_count'] ?></td>
        <td class="time-mono" style="font-size:.8rem;color:var(--muted)"><?= date('d.m.Y', strtotime($ath['created_at'])) ?></td>
        <td>
          <a href="athlete_edit.php?id=<?= $ath['id'] ?>" class="btn btn-g btn-sm" title="Edytuj profil"><i class="fa-solid fa-pen"></i></a>
          <a href="https://wyniki.lo48.pl/athlete.php?id=<?= $ath['id'] ?>" target="_blank" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye"></i></a>
          <a href="athletes.php?delete=<?= $ath['id'] ?>" class="btn btn-d btn-sm" onclick="return confirm('Trwale usunąć tego zawodnika i jego postępy z systemu?')"><i class="fa-solid fa-trash"></i></a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
