<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach($_POST['content'] as $section_id => $data) {
        $title = trim($data['title']);
        $desc = trim($data['desc']);
        $pdo->prepare("UPDATE site_content SET section_title=?, description=? WHERE section_id=?")
            ->execute([$title, $desc, $section_id]);
            
        // Photo uploads
        for($i=1; $i<=3; $i++) {
            $file_key = "img_{$section_id}_{$i}";
            if(!empty($_FILES[$file_key]['name'])) {
                $ext = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));
                if(in_array($ext,['jpg','jpeg','png','webp'])) {
                    $dir = '../WikOS.run/uploads/';
                    if(!is_dir($dir)) mkdir($dir, 0777, true);
                    $fname = "{$section_id}_{$i}_" . uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES[$file_key]['tmp_name'], $dir . $fname);
                    $pdo->prepare("UPDATE site_content SET img{$i}=? WHERE section_id=?")->execute([$fname, $section_id]);
                }
            }
        }
    }
    header('Location: content.php?msg=saved'); exit;
}

$stmt = $pdo->query("SELECT * FROM site_content ORDER BY id ASC");
$sections = $stmt->fetchAll();

include 'includes/header.php';
?>
<div class="ph">
  <div class="ph-title">System CMS — Strona Główna</div>
  <div class="ph-sub">Edytuj treści publiczne na domenie głównej WikOS.run</div>
</div>

<?php if(isset($_GET['msg'])): ?>
<div class="alert alert-g" style="padding:16px;background:var(--green-dim);color:var(--green);border-radius:var(--rs);margin-bottom:20px;font-weight:700">✓ Treści zostały zaktualizowane publicznie.</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
  <?php foreach($sections as $sec): ?>
  <div class="card" style="border-left:4px solid var(--primary)">
    <div class="card-hd" style="color:var(--primary);font-size:12px"><?= htmlspecialchars($sec['section_id']) ?></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
      <div class="fg" style="grid-column:1/3">
        <label>Tytuł sekcji (Twarde nagranie)</label>
        <input type="text" name="content[<?= $sec['section_id'] ?>][title]" value="<?= htmlspecialchars($sec['section_title']) ?>" style="background:var(--bg2);border:1px solid var(--border);color:var(--text);padding:10px;width:100%;border-radius:6px;font-weight:700">
      </div>
      <div class="fg" style="grid-column:1/3">
        <label>Główny tekst / Opis</label>
        <textarea name="content[<?= $sec['section_id'] ?>][desc]" rows="4" style="background:var(--bg2);border:1px solid var(--border);color:var(--text);padding:10px;width:100%;border-radius:6px;font-family:inherit"><?= htmlspecialchars($sec['description']) ?></textarea>
      </div>
      <div class="fg" style="grid-column:1/3;display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
        <?php for($i=1; $i<=3; $i++): $img_key = "img$i"; if(isset($sec[$img_key]) && $sec[$img_key] !== ''): ?>
        <div>
          <label style="font-size:10px;color:var(--muted);display:block;margin-bottom:4px">Obrazek <?= $i ?></label>
          <?php if($sec[$img_key] != 'default.jpg'): ?>
            <img src="../WikOS.run/uploads/<?= $sec[$img_key] ?>" style="width:100%;height:80px;object-fit:cover;border-radius:4px;border:1px solid var(--border);margin-bottom:8px">
          <?php else: ?>
            <div style="width:100%;height:80px;background:var(--surface);border-radius:4px;border:1px dashed var(--border);margin-bottom:8px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:10px">BRAK</div>
          <?php endif; ?>
          <input type="file" name="img_<?= $sec['section_id'] ?>_<?= $i ?>" accept="image/*" style="font-size:11px">
        </div>
        <?php endif; endfor; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  
  <div style="position:sticky;bottom:30px;background:var(--bg2);padding:20px;border:1px solid var(--primary);border-radius:var(--r);display:flex;justify-content:space-between;align-items:center;box-shadow:0 10px 40px rgba(0,0,0,0.5)">
    <div style="font-size:.9rem;color:var(--muted)">Pamiętaj, że zmiany na stronie głównej są natychmiastowe. Uważaj na formatowanie tekstów.</div>
    <button type="submit" class="btn btn-p btn-lg" style="font-size:1rem;padding:12px 32px"><i class="fa-solid fa-cloud-arrow-up"></i> Opublikuj zmiany</button>
  </div>
</form>
<?php include 'includes/footer.php'; ?>
