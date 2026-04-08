<?php
session_start(); require_once 'includes/auth.php'; require_once 'db.php';
$page_title = 'Zawodnicy'; $current_page = 'athletes';

// Handle delete
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del = $pdo->prepare("DELETE FROM athletes WHERE id=? AND account_id=?");
    $del->execute([(int)$_GET['delete'], $uid]);
    header('Location: athletes.php?msg=deleted'); exit;
}

// Handle add/edit POST
$edit_athlete = null;
if(isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM athletes WHERE id=? AND account_id=?");
    $s->execute([(int)$_GET['edit'], $uid]);
    $edit_athlete = $s->fetch();
}

if($_SERVER['REQUEST_METHOD']==='POST') {
    $fn   = trim($_POST['first_name']??'');
    $ln   = trim($_POST['last_name']??'');
    $by   = (int)($_POST['birth_year']??0) ?: null;
    $club = trim($_POST['club']??'');
    $category = trim($_POST['category']??'');
    $pzla = trim($_POST['pzla_url']??'');
    $ig = trim($_POST['instagram_username']??'');
    $email = trim($_POST['public_email']??'');
    $notes= trim($_POST['notes']??'');
    $id   = (int)($_POST['id']??0);

    if($fn && $ln) {
        // Handle photo upload
        $photo = $_POST['current_photo'] ?? null;
        if(!empty($_FILES['photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png','webp'])) {
                $dir = 'uploads/athletes/';
                if(!is_dir($dir)) mkdir($dir,0777,true);
                $fname = uniqid().'.'.$ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], $dir.$fname);
                $photo = $fname;
            }
        }

        if($id) {
            $s = $pdo->prepare("UPDATE athletes SET first_name=?,last_name=?,birth_year=?,club=?,category=?,pzla_url=?,instagram_username=?,public_email=?,notes=?,photo=? WHERE id=? AND account_id=?");
            $s->execute([$fn,$ln,$by,$club,$category,$pzla,$ig,$email,$notes,$photo,$id,$uid]);
        } else {
            $s = $pdo->prepare("INSERT INTO athletes (account_id,first_name,last_name,birth_year,club,category,pzla_url,instagram_username,public_email,notes,photo) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $s->execute([$uid,$fn,$ln,$by,$club,$category,$pzla,$ig,$email,$notes,$photo]);
        }
        header('Location: athletes.php?msg=saved'); exit;
    }
}

// Fetch athletes with PBs
$athletes = $pdo->prepare("
    SELECT a.*,
        (SELECT MIN(time_ms) FROM results r WHERE r.athlete_id=a.id AND r.distance_m=100) as pb_100,
        (SELECT MIN(time_ms) FROM results r WHERE r.athlete_id=a.id AND r.distance_m=200) as pb_200,
        (SELECT MIN(time_ms) FROM results r WHERE r.athlete_id=a.id AND r.distance_m=400) as pb_400,
        (SELECT COUNT(*) FROM results r WHERE r.athlete_id=a.id) as total_runs
    FROM athletes a WHERE a.account_id=? ORDER BY a.last_name, a.first_name
");
$athletes->execute([$uid]);
$all = $athletes->fetchAll();

$show_form = isset($_GET['add']) || $edit_athlete;

function fmt($ms) {
    if(!$ms) return '—';
    $cs=$ms/10%100; $sec=floor($ms/1000); $min=floor($sec/60); $sec%=60;
    return $min>0?sprintf('%d:%02d.%02d',$min,$sec,$cs):sprintf('%d.%02d',$sec,$cs);
}
function initials($fn,$ln){return strtoupper(mb_substr($fn,0,1).mb_substr($ln,0,1));}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<div class="ph">
  <div>
    <div class="ph-title">Zawodnicy</div>
    <div class="ph-sub"><?= count($all) ?> zarejestrowanych profili</div>
  </div>
  <button class="btn btn-p" onclick="toggleForm()"><i class="fa-solid fa-user-plus"></i> Dodaj zawodnika</button>
</div>

<?php if(isset($_GET['msg'])): ?>
<div class="alert <?= $_GET['msg']==='deleted'?'alert-r':'alert-g' ?>" style="margin-bottom:18px">
  <?= $_GET['msg']==='saved'?'✓ Zawodnik zapisany.':'✓ Zawodnik usunięty.' ?>
</div>
<?php endif; ?>

<!-- ADD/EDIT FORM -->
<div id="athlete-form" class="card" style="margin-bottom:24px;<?= $show_form?'':'display:none' ?>">
  <div class="card-hd">
    <span class="card-title"><?= $edit_athlete?'Edytuj zawodnika':'Nowy zawodnik' ?></span>
    <button class="btn btn-g btn-sm" onclick="toggleForm()">✕ Zamknij</button>
  </div>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $edit_athlete['id']??'' ?>">
    <input type="hidden" name="current_photo" value="<?= $edit_athlete['photo']??'' ?>">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="fg">
        <label>Imię</label>
        <input type="text" name="first_name" required value="<?= htmlspecialchars($edit_athlete['first_name']??'') ?>">
      </div>
      <div class="fg">
        <label>Nazwisko</label>
        <input type="text" name="last_name" required value="<?= htmlspecialchars($edit_athlete['last_name']??'') ?>">
      </div>
      <div class="fg">
        <label>Rok urodzenia</label>
        <input type="number" name="birth_year" min="1950" max="2015" value="<?= $edit_athlete['birth_year']??'' ?>">
      </div>
      <div class="fg">
        <label>Kategoria / Grupa</label>
        <select name="category">
          <option value="">-- Wybierz (lub zostaw puste) --</option>
          <?php foreach(['U12','U14','U16','U18','U20','U23','Senior','Masters'] as $cat): ?>
          <option value="<?= $cat ?>" <?= ($edit_athlete['category']??'')==$cat?'selected':'' ?>><?= $cat ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fg">
        <label>Klub</label>
        <input type="text" name="club" placeholder="np. AZS Warszawa" value="<?= htmlspecialchars($edit_athlete['club']??'') ?>">
      </div>
      <div class="fg">
        <label>Link PZLA</label>
        <input type="url" name="pzla_url" placeholder="https://statystyka.pzla.pl/..." value="<?= htmlspecialchars($edit_athlete['pzla_url']??'') ?>">
      </div>
      <div class="fg">
        <label>Instagram (@nazwa)</label>
        <input type="text" name="instagram_username" placeholder="oskar_kulinskii" value="<?= htmlspecialchars($edit_athlete['instagram_username']??'') ?>">
      </div>
      <div class="fg" style="grid-column:1/3">
        <label>Widoczny E-mail (Publiczny)</label>
        <input type="email" name="public_email" placeholder="email@zawodnika.pl" value="<?= htmlspecialchars($edit_athlete['public_email']??'') ?>">
      </div>
      <div class="fg" style="grid-column:1/3">
        <label>Zdjęcie</label>
        <input type="file" name="photo" accept="image/*">
      </div>
      <div class="fg" style="grid-column:1/3">
        <label>Notatki</label>
        <textarea name="notes" rows="2" placeholder="Specjalizacja, uwagi..."><?= htmlspecialchars($edit_athlete['notes']??'') ?></textarea>
      </div>
    </div>
    <button type="submit" class="btn btn-p"><i class="fa-solid fa-floppy-disk"></i> Zapisz</button>
  </form>
</div>

<!-- ATHLETES LIST -->
<?php if(empty($all)): ?>
<div class="card"><div class="empty"><i class="fa-solid fa-person-running"></i><p>Brak zawodników.<br>Dodaj pierwszego!</p></div></div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:14px">
<?php foreach($all as $a):
  $photo_url = $a['photo'] ? "uploads/athletes/{$a['photo']}" : null;
?>
<div class="card" style="transition:border-color .2s;cursor:pointer" onmouseover="this.style.borderColor='var(--border-l)'" onmouseout="this.style.borderColor='var(--border)'">
  <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:14px">
    <div class="av" style="width:50px;height:50px;font-size:16px;flex-shrink:0">
      <?php if($photo_url): ?><img src="<?= $photo_url ?>" alt=""><?php else: ?><?= initials($a['first_name'],$a['last_name']) ?><?php endif; ?>
    </div>
    <div style="flex:1;min-width:0">
      <div style="font-weight:700;font-size:1rem;display:flex;align-items:center;gap:6px">
        <?= htmlspecialchars($a['first_name'].' '.$a['last_name']) ?>
        <?php if($a['special_rank'] === 'owner'): ?>
          <i class="fa-solid fa-crown" style="color:var(--warn);font-size:0.75rem" title="Właściciel"></i>
        <?php elseif($a['special_rank'] === 'creator'): ?>
          <i class="fa-solid fa-certificate" style="color:var(--primary);font-size:0.75rem" title="Twórca"></i>
        <?php endif; ?>
      </div>
      <div style="font-size:.78rem;color:var(--muted)"><?= $a['club']?htmlspecialchars($a['club']):'Brak klubu' ?><?= $a['birth_year']?" · ur. {$a['birth_year']}":''; ?></div>
    </div>
    <span class="badge bdg-grey"><?= $a['total_runs'] ?> biegów</span>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin-bottom:14px">
    <?php foreach([100,200,400] as $d): $pb=$a["pb_$d"]; ?>
    <div style="text-align:center;padding:8px;background:var(--bg);border-radius:8px;border:1px solid var(--border)">
      <div style="font-size:9px;color:var(--muted);font-weight:700;letter-spacing:.1em"><?= $d ?>M</div>
      <div class="time-mono" style="font-size:.85rem"><?= fmt($pb) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <div style="display:flex;gap:8px">
    <a href="measure.php?athlete=<?= $a['id'] ?>" class="btn btn-p btn-sm" style="flex:1;justify-content:center"><i class="fa-solid fa-stopwatch"></i> Mierz</a>
    <a href="athlete.php?id=<?= $a['id'] ?>" class="btn btn-g btn-sm"><i class="fa-solid fa-chart-line"></i></a>
    <a href="athletes.php?edit=<?= $a['id'] ?>" class="btn btn-g btn-sm"><i class="fa-solid fa-pen"></i></a>
    <a href="athletes.php?delete=<?= $a['id'] ?>" class="btn btn-d btn-sm" onclick="return confirm('Usunąć zawodnika i wszystkie jego wyniki?')"><i class="fa-solid fa-trash"></i></a>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function toggleForm(){
  var f=document.getElementById('athlete-form');
  f.style.display=f.style.display==='none'?'block':'none';
}
<?php if($show_form): ?>toggleForm();<?php endif; ?>
<?php if(isset($_GET['add'])): ?>
document.getElementById('athlete-form').style.display='block';
<?php endif; ?>
</script>
<?php include 'includes/footer.php'; ?>
