<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

$id = (int)($_GET['id'] ?? 0);

$edit_athlete = null;
if($id > 0) {
    $q = $pdo->prepare("SELECT * FROM athletes WHERE id=?");
    $q->execute([$id]);
    $edit_athlete = $q->fetch();
    if(!$edit_athlete) { header("Location: athletes.php"); exit; }
}

$accounts = $pdo->query("SELECT id, name, email FROM accounts ORDER BY name")->fetchAll();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fn   = trim($_POST['first_name']??'');
    $ln   = trim($_POST['last_name']??'');
    $by   = (int)($_POST['birth_year']??0) ?: null;
    $club = trim($_POST['club']??'');
    $category = trim($_POST['category']??'');
    $rank = trim($_POST['special_rank']??'') ?: null;
    $pzla = trim($_POST['pzla_url']??'');
    $ig = trim($_POST['instagram_username']??'');
    $email = trim($_POST['public_email']??'');
    $notes= trim($_POST['notes']??'');
    $trainer_id = (int)($_POST['account_id']??0);
    
    if($fn && $ln && $trainer_id) {
        $photo = $edit_athlete['photo'] ?? null;
        if(!empty($_FILES['photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png','webp'])) {
                $dir = '../panel.WikOS.run/uploads/athletes/';
                if(!is_dir($dir)) mkdir($dir,0777,true);
                $fname = uniqid().'.'.$ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], $dir.$fname);
                $photo = $fname;
            }
        }

        if($id) {
            $s = $pdo->prepare("UPDATE athletes SET first_name=?,last_name=?,birth_year=?,club=?,category=?,special_rank=?,pzla_url=?,instagram_username=?,public_email=?,notes=?,photo=?,account_id=? WHERE id=?");
            $s->execute([$fn,$ln,$by,$club,$category,$rank,$pzla,$ig,$email,$notes,$photo,$trainer_id,$id]);
        } else {
            $s = $pdo->prepare("INSERT INTO athletes (account_id,first_name,last_name,birth_year,club,category,special_rank,pzla_url,instagram_username,public_email,notes,photo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $s->execute([$trainer_id,$fn,$ln,$by,$club,$category,$rank,$pzla,$ig,$email,$notes,$photo]);
            $id = $pdo->lastInsertId();
        }
        header("Location: athlete_edit.php?id=$id&msg=saved"); exit;
    }
}

include 'includes/header.php';
?>
<div class="ph">
  <div style="display:flex;align-items:center;gap:16px">
    <a href="athletes.php" class="btn btn-g btn-sm"><i class="fa-solid fa-arrow-left"></i> Wróć</a>
    <div>
      <div class="ph-title"><?= $id ? 'Edycja Zawodnika #'.$id : 'Wymuszone dodanie Zawodnika (Admin)' ?></div>
    </div>
  </div>
</div>

<?php if(isset($_GET['msg'])): ?>
<div class="alert alert-g" style="padding:16px;background:var(--green-dim);color:var(--green);border-radius:var(--rs);margin-bottom:20px;font-weight:700">✓ Dane zapisane poprawnie w bazie!</div>
<?php endif; ?>

<div class="card" style="max-width:800px;border-left:4px solid var(--primary)">
  <div class="card-hd">Właściwości Rekordu Sportowego</div>
  <form method="POST" enctype="multipart/form-data">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
      <div class="fg">
        <label style="color:var(--primary)"><i class="fa-solid fa-user-tie"></i> Przypisany Trener (Właściciel)</label>
        <select name="account_id" required style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--primary);color:#fff;border-radius:6px">
          <option value="">-- Wybierz (Wymagane) --</option>
          <?php foreach($accounts as $acc): ?>
          <option value="<?= $acc['id'] ?>" <?= ($edit_athlete['account_id']??'')==$acc['id']?'selected':'' ?>><?= htmlspecialchars($acc['name']) ?> (<?= htmlspecialchars($acc['email']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="fg" style="grid-column:1/3">
        <label>Zdjęcie Profilowe</label>
        <?php if(!empty($edit_athlete['photo'])): ?>
            <img src="https://panel.lo48.pl/uploads/athletes/<?= $edit_athlete['photo'] ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:10px;border:2px solid var(--green)">
        <?php endif; ?>
        <input type="file" name="photo" accept="image/*" style="width:100%;font-size:12px;padding:10px;border:1px dashed var(--border);border-radius:6px;background:var(--surface)">
      </div>

      <div class="fg"><label>Imię</label><input type="text" name="first_name" required value="<?= htmlspecialchars($edit_athlete['first_name']??'') ?>" style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px"></div>
      <div class="fg"><label>Nazwisko</label><input type="text" name="last_name" required value="<?= htmlspecialchars($edit_athlete['last_name']??'') ?>" style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px"></div>
      
      <div class="fg">
        <label>Rok Urodzenia</label>
        <input type="number" name="birth_year" min="1950" max="2020" value="<?= $edit_athlete['birth_year']??'' ?>" style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px">
      </div>
      <div class="fg">
        <label>Kategoria Sportowa</label>
        <select name="category" style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px">
          <option value="">-- Ignoruj --</option>
          <?php foreach(['U12','U14','U16','U18','U20','U23','Senior','Masters'] as $cat): ?>
          <option value="<?= $cat ?>" <?= ($edit_athlete['category']??'')==$cat?'selected':'' ?>><?= $cat ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="fg"><label>Klub / Związek</label><input type="text" name="club" value="<?= htmlspecialchars($edit_athlete['club']??'') ?>" style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px"></div>
      
      <div class="fg">
        <label style="color:var(--warn)"><i class="fa-solid fa-crown"></i> Ranga Specjalna (Subtelna)</label>
        <select name="special_rank" style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--warn);color:#fff;border-radius:6px">
          <option value="">-- Brak (Standard) --</option>
          <option value="owner" <?= ($edit_athlete['special_rank']??'')=='owner'?'selected':'' ?>>👑 Właściciel / Owner</option>
          <option value="creator" <?= ($edit_athlete['special_rank']??'')=='creator'?'selected':'' ?>>🎖️ Twórca / Creator</option>
        </select>
      </div>

      <div class="fg"><label>E-mail Publiczny (Widocznyt na profilu)</label><input type="email" name="public_email" value="<?= htmlspecialchars($edit_athlete['public_email']??'') ?>" style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px"></div>

      <div class="fg"><label>Link STATYSTYLKA PZLA</label><input type="url" name="pzla_url" value="<?= htmlspecialchars($edit_athlete['pzla_url']??'') ?>" style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px"></div>
      <div class="fg"><label>Instagram (@nazwa)</label><input type="text" name="instagram_username" value="<?= htmlspecialchars($edit_athlete['instagram_username']??'') ?>" style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px"></div>
      
      <div class="fg" style="grid-column:1/3"><label>Notatki Oficera systemu</label><input type="text" name="notes" value="<?= htmlspecialchars($edit_athlete['notes']??'') ?>" style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px"></div>
    </div>
    
    <button type="submit" class="btn btn-p btn-lg" style="width:100%;justify-content:center"><i class="fa-solid fa-floppy-disk"></i> Wgraj dane jako ROOT (Siłowo)</button>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
