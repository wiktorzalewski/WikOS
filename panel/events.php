<?php
session_start(); require_once 'includes/auth.php'; require_once 'db.php';
$page_title = 'Sesje'; $current_page = 'events';

if(isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM events WHERE id=? AND account_id=?")->execute([$_GET['delete'],$uid]);
    header('Location: events.php?msg=deleted'); exit;
}

if($_SERVER['REQUEST_METHOD']==='POST') {
    $name = trim($_POST['name']??'');
    $date = $_POST['date']??'';
    $loc  = trim($_POST['location']??'');
    $notes= trim($_POST['notes']??'');
    $id   = (int)($_POST['id']??0);
    if($name && $date) {
        if($id) {
            $pdo->prepare("UPDATE events SET name=?,date=?,location=?,notes=? WHERE id=? AND account_id=?")->execute([$name,$date,$loc,$notes,$id,$uid]);
        } else {
            $pdo->prepare("INSERT INTO events (account_id,name,date,location,notes) VALUES (?,?,?,?,?)")->execute([$uid,$name,$date,$loc,$notes]);
        }
        header('Location: events.php?msg=saved'); exit;
    }
}

$edit = null;
if(isset($_GET['edit'])) { $s=$pdo->prepare("SELECT * FROM events WHERE id=? AND account_id=?"); $s->execute([$_GET['edit'],$uid]); $edit=$s->fetch(); }

$list = $pdo->prepare("SELECT e.*, (SELECT COUNT(*) FROM results r WHERE r.event_id=e.id) as result_count FROM events e WHERE e.account_id=? ORDER BY e.date DESC");
$list->execute([$uid]); $events=$list->fetchAll();

include 'includes/header.php'; include 'includes/sidebar.php';
?>
<div class="ph">
  <div><div class="ph-title">Sesje</div><div class="ph-sub">Treningi i zawody</div></div>
  <button class="btn btn-p" onclick="document.getElementById('ev-form').style.display=document.getElementById('ev-form').style.display==='none'?'block':'none'"><i class="fa-solid fa-plus"></i> Nowa sesja</button>
</div>
<?php if(isset($_GET['msg'])): ?><div class="alert <?= $_GET['msg']==='saved'?'alert-g':'alert-r' ?>" style="margin-bottom:16px"><?= $_GET['msg']==='saved'?'✓ Sesja zapisana.':'✓ Sesja usunięta.' ?></div><?php endif; ?>

<div id="ev-form" class="card" style="margin-bottom:20px;<?= ($edit||isset($_GET['add']))?'':'display:none' ?>">
  <div class="card-hd"><span class="card-title"><?= $edit?'Edytuj':'Nowa' ?> sesja</span></div>
  <form method="POST">
    <input type="hidden" name="id" value="<?= $edit['id']??'' ?>">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="fg" style="grid-column:1/3"><label>Nazwa</label><input type="text" name="name" required value="<?= htmlspecialchars($edit['name']??'') ?>" placeholder="np. Trening środa 400m"></div>
      <div class="fg"><label>Data</label><input type="date" name="date" value="<?= $edit['date']??date('Y-m-d') ?>"></div>
      <div class="fg"><label>Miejsce</label><input type="text" name="location" value="<?= htmlspecialchars($edit['location']??'') ?>" placeholder="Stadion X, Warszawa"></div>
      <div class="fg" style="grid-column:1/3"><label>Notatki</label><textarea name="notes" rows="2"><?= htmlspecialchars($edit['notes']??'') ?></textarea></div>
    </div>
    <button type="submit" class="btn btn-p"><i class="fa-solid fa-floppy-disk"></i> Zapisz</button>
  </form>
</div>

<?php if(empty($events)): ?>
<div class="card"><div class="empty"><i class="fa-solid fa-calendar-days"></i><p>Brak sesji treningowych.<br>Dodaj pierwszą!</p></div></div>
<?php else: ?>
<div class="card">
<div class="tbl-wrap"><table>
  <tr><th>Nazwa</th><th>Data</th><th>Miejsce</th><th>Wyniki</th><th>Notatki</th><th></th></tr>
  <?php foreach($events as $e): ?>
  <tr>
    <td style="font-weight:600"><?= htmlspecialchars($e['name']) ?></td>
    <td><?= date('d.m.Y', strtotime($e['date'])) ?></td>
    <td style="color:var(--muted);font-size:.82rem"><?= $e['location']?htmlspecialchars($e['location']):'—' ?></td>
    <td><span class="badge bdg-b"><?= $e['result_count'] ?></span></td>
    <td style="color:var(--muted);font-size:.82rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= $e['notes']?htmlspecialchars($e['notes']):'—' ?></td>
    <td style="display:flex;gap:6px">
      <a href="events.php?edit=<?= $e['id'] ?>" class="btn btn-g btn-sm"><i class="fa-solid fa-pen"></i></a>
      <a href="events.php?delete=<?= $e['id'] ?>" class="btn btn-d btn-sm" onclick="return confirm('Usuń sesję?')"><i class="fa-solid fa-trash"></i></a>
    </td>
  </tr>
  <?php endforeach; ?>
</table></div>
</div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
