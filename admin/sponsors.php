<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

$msg = '';

// Delete sponsor
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("SELECT logo_path FROM sponsors WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    $logo = $stmt->fetchColumn();
    if($logo && file_exists('../panel.WikOS.run/uploads/sponsors/'.$logo)) unlink('../panel.WikOS.run/uploads/sponsors/'.$logo);
    
    $pdo->prepare("DELETE FROM sponsors WHERE id=?")->execute([$_GET['delete']]);
    $msg = '<div class="alert alert-g" style="padding:16px;background:var(--danger);color:#fff;border-radius:var(--rs);margin-bottom:20px;font-weight:700">✓ Sponsor usunięty.</div>';
}

// Add/Edit sponsor
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $url  = trim($_POST['website_url'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;
    $order  = (int)($_POST['sort_order'] ?? 0);
    $id     = (int)($_POST['id'] ?? 0);

    if($name) {
        $logo_path = $_POST['existing_logo'] ?? '';
        if(!empty($_FILES['logo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg','jpeg','png','webp','svg'])) {
                $dir = '../panel.WikOS.run/uploads/sponsors/';
                if(!is_dir($dir)) mkdir($dir, 0777, true);
                
                // Remove old logo
                if($logo_path && file_exists($dir.$logo_path)) unlink($dir.$logo_path);
                
                $fname = uniqid().'.'.$ext;
                move_uploaded_file($_FILES['logo']['tmp_name'], $dir.$fname);
                $logo_path = $fname;
            }
        }

        if($logo_path) {
            if($id) {
                $stmt = $pdo->prepare("UPDATE sponsors SET name=?, logo_path=?, website_url=?, is_active=?, sort_order=? WHERE id=?");
                $stmt->execute([$name, $logo_path, $url, $active, $order, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO sponsors (name, logo_path, website_url, is_active, sort_order) VALUES (?,?,?,?,?)");
                $stmt->execute([$name, $logo_path, $url, $active, $order]);
            }
            header("Location: sponsors.php?msg=success"); exit;
        } else {
            $msg = '<div class="alert alert-r" style="padding:16px;background:var(--danger);color:#fff;border-radius:var(--rs);margin-bottom:20px;font-weight:700">✕ Błąd: Musisz wgrać logo!</div>';
        }
    }
}

try {
    $sponsors = $pdo->query("SELECT * FROM sponsors ORDER BY sort_order ASC, created_at DESC")->fetchAll();
} catch(Exception $e) { $sponsors = []; }

include 'includes/header.php';
?>
<div class="ph">
  <div class="ph-title"><i class="fa-solid fa-handshake"></i> Nasi Sponsorzy</div>
  <div class="ph-sub">Tutaj zarządzasz logotypami, które widać w stopkach strony głównej i wyników.</div>
</div>

<?= $msg ?>
<?php if(isset($_GET['msg'])) echo '<div class="alert alert-g" style="padding:16px;background:var(--green-dim);color:var(--green);border-radius:var(--rs);margin-bottom:20px;font-weight:700">✓ Zmiany zapisane pomyślnie.</div>'; ?>

<div style="display:grid; grid-template-columns: 1fr 350px; gap:24px; align-items:start">
    <div class="card">
        <div class="card-hd">Obecni darczyńcy</div>
        <div class="tbl-wrap">
            <table>
                <tr><th>Logo</th><th>Firma</th><th>Link</th><th>Status</th><th>Kolejność</th><th>Akcje</th></tr>
                <?php foreach($sponsors as $s): ?>
                <tr>
                    <td><img src="https://panel.lo48.pl/uploads/sponsors/<?= $s['logo_path'] ?>" style="height:30px; max-width:80px; object-fit:contain; filter:grayscale(1)"></td>
                    <td style="font-weight:800"><?= htmlspecialchars($s['name']) ?></td>
                    <td style="font-size:0.75rem; color:var(--muted)"><?= htmlspecialchars($s['website_url'] ?: '—') ?></td>
                    <td><?= $s['is_active'] ? '<span class="badge bdg-g" style="background:var(--green-dim);color:var(--green)">Widoczny</span>' : '<span class="badge" style="background:var(--surface);color:var(--muted)">Ukryty</span>' ?></td>
                    <td style="text-align:center"><?= $s['sort_order'] ?></td>
                    <td>
                        <button onclick="editSponsor(<?= htmlspecialchars(json_encode($s)) ?>)" class="btn btn-g btn-sm"><i class="fa-solid fa-pen"></i></button>
                        <a href="sponsors.php?delete=<?= $s['id'] ?>" class="btn btn-d btn-sm" onclick="return confirm('Usunąć sponsora?')"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($sponsors)===0): ?>
                <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--muted)">Brak sponsorów w bazie. Dodaj pierwszego po prawej!</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div class="card" style="border-left:4px solid var(--primary)">
        <div class="card-hd" id="form-title">Nowy sponsor</div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="form-id" value="0">
            <input type="hidden" name="existing_logo" id="form-existing-logo" value="">
            
            <div class="fg" style="margin-bottom:15px">
                <label>Nazwa sponsora / podmiotu</label>
                <input type="text" name="name" id="form-name" required style="width:100%;padding:10px;background:var(--bg2);color:var(--text);border:1px solid var(--border);border-radius:6px">
            </div>

            <div class="fg" style="margin-bottom:15px">
                <label>Logo (PNG/SVG/WebP)</label>
                <input type="file" name="logo" accept="image/*" style="width:100%;font-size:0.8rem;color:var(--muted)">
                <div id="logo-preview" style="margin-top:10px; display:none"><img src="" style="height:40px; filter:grayscale(1)"></div>
            </div>

            <div class="fg" style="margin-bottom:15px">
                <label>Link WWW (URL)</label>
                <input type="url" name="website_url" id="form-url" placeholder="https://..." style="width:100%;padding:10px;background:var(--bg2);color:var(--text);border:1px solid var(--border);border-radius:6px">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px">
                <div class="status-box" style="background:var(--bg2); padding:10px; border-radius:6px; border:1px solid var(--border)">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer">
                        <input type="checkbox" name="is_active" id="form-active" checked> Widoczny
                    </label>
                </div>
                <div class="order-box">
                    <label style="font-size:0.7rem; color:var(--muted)">Kolejność</label>
                    <input type="number" name="sort_order" id="form-order" value="0" style="width:100%;padding:10px;background:var(--bg2);color:var(--text);border:1px solid var(--border);border-radius:6px">
                </div>
            </div>

            <button type="submit" class="btn btn-p" style="width:100%; justify-content:center"><i class="fa-solid fa-floppy-disk"></i> Zapisz Sponsora</button>
            <button type="button" onclick="resetForm()" id="btn-cancel" style="width:100%; margin-top:8px; display:none; justify-content:center" class="btn btn-d btn-sm">Anuluj edycję</button>
        </form>
    </div>
</div>

<script>
function editSponsor(s) {
    document.getElementById('form-title').innerText = 'Edytuj Sponsora: ' + s.name;
    document.getElementById('form-id').value = s.id;
    document.getElementById('form-name').value = s.name;
    document.getElementById('form-url').value = s.website_url;
    document.getElementById('form-active').checked = s.is_active == 1;
    document.getElementById('form-order').value = s.sort_order;
    document.getElementById('form-existing-logo').value = s.logo_path;
    
    document.getElementById('logo-preview').style.display = 'block';
    document.getElementById('logo-preview').querySelector('img').src = 'https://panel.lo48.pl/uploads/sponsors/' + s.logo_path;
    document.getElementById('btn-cancel').style.display = 'inline-flex';
}

function resetForm() {
    document.getElementById('form-title').innerText = 'Dodaj Sponsora';
    document.getElementById('form-id').value = '0';
    document.getElementById('form-name').value = '';
    document.getElementById('form-url').value = '';
    document.getElementById('form-active').checked = true;
    document.getElementById('form-order').value = '0';
    document.getElementById('form-existing-logo').value = '';
    document.getElementById('logo-preview').style.display = 'none';
    document.getElementById('btn-cancel').style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
