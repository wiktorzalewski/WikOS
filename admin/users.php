<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

// Fetch the current admin to check if they are Super Admin
try {
    $me_stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $me_stmt->execute([$_SESSION['admin_email']]);
    $me = $me_stmt->fetch();
} catch(Exception $e) { $me = false; }

// Migration safety
$me_is_super = (isset($me['is_super']) && $me['is_super'] == 1) || ($me === false); 

if(!$me_is_super) {
    header('Location: dashboard.php'); exit; // Only super admin can manage other admins
}

$msg = '';

// Delete admin
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if($_GET['delete'] == $me['id']) {
        $msg = '<div class="alert alert-r" style="padding:16px;background:var(--danger);color:#fff;border-radius:var(--rs);margin-bottom:20px;font-weight:700">✕ Nie możesz usunąć samego siebie!</div>';
    } else {
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$_GET['delete']]);
        $msg = '<div class="alert alert-g" style="padding:16px;background:var(--success);color:#fff;border-radius:var(--rs);margin-bottom:20px;font-weight:700">✓ Administrator usunięty.</div>';
    }
}

// Add/Edit admin
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    $is_super = isset($_POST['is_super']) ? 1 : 0;
    $id = (int)($_POST['id'] ?? 0);
    
    // Process permissions
    $perms = $_POST['perms'] ?? [];
    $perms_json = json_encode($perms);

    if($email) {
        if($id) {
            // Update
            if(!empty($pass)) {
                $hashed = password_hash($pass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET email=?, password=?, permissions=?, is_super=? WHERE id=?");
                $stmt->execute([$email, $hashed, $perms_json, $is_super, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET email=?, permissions=?, is_super=? WHERE id=?");
                $stmt->execute([$email, $perms_json, $is_super, $id]);
            }
            $msg = '<div class="alert alert-g" style="padding:16px;background:var(--success);color:#fff;border-radius:var(--rs);margin-bottom:20px;font-weight:700">✓ Dane administratora zaktualizowane.</div>';
        } else {
            // Insert
            $hashed = password_hash($pass ?: 'wikos123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password, permissions, is_super) VALUES (?,?,?,?)");
            $stmt->execute([$email, $hashed, $perms_json, $is_super]);
            $msg = '<div class="alert alert-g" style="padding:16px;background:var(--success);color:#fff;border-radius:var(--rs);margin-bottom:20px;font-weight:700">✓ Nowy administrator dodany.</div>';
        }
    }
}

try {
    $all_admins = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
} catch(Exception $e) { $all_admins = []; }

$perm_list = [
    'dashboard' => 'Pulpit i Statystyki',
    'live'      => 'Radar Na Żywo',
    'accounts'  => 'Trenerzy i Konta',
    'athletes'  => 'Baza Zawodników',
    'results'   => 'Biegi i Sesje',
    'hardware'  => 'Pachołki, Hotspoty & OTA',
    'content'   => 'Wygląd & Sponsorzy',
    'system'    => 'Ustawienia Główne'
];

include 'includes/header.php';
?>
<div class="ph">
  <div class="ph-title"><i class="fa-solid fa-user-shield"></i> Konta Adminów</div>
  <div class="ph-sub">Zarządzanie dostępem do panelu dla sub-administratorów.</div>
</div>

<?= $msg ?>

<div style="display:grid; grid-template-columns: 1fr 400px; gap:24px; align-items:start">
    <div class="card">
        <div class="card-hd">Obecni Administratorzy</div>
        <div class="tbl-wrap">
            <table>
                <tr><th>Administrator</th><th>Rola</th><th>Uprawnienia</th><th>Akcje</th></tr>
                <?php foreach($all_admins as $admin): 
                    $p = json_decode($admin['permissions'] ?? '[]', true);
                ?>
                <tr>
                    <td>
                        <div style="font-weight:800"><?= htmlspecialchars($admin['email']) ?></div>
                        <div style="font-size:0.7rem; color:var(--muted)">Dodano: <?= date('d.m.Y', strtotime($admin['created_at'])) ?></div>
                    </td>
                    <td>
                        <?= (isset($admin['is_super']) && $admin['is_super']) ? '<span class="badge" style="background:var(--warn);color:#000">Super Admin</span>' : '<span class="badge" style="background:var(--primary-dim);color:var(--primary)">Sub-Admin</span>' ?>
                    </td>
                    <td style="max-width:250px">
                        <?php if(isset($admin['is_super']) && $admin['is_super']): ?>
                            <span style="color:var(--muted);font-size:0.75rem">Dostęp Totalny (Wszystko)</span>
                        <?php else: ?>
                            <?php if(empty($p)): ?>
                                <span style="color:var(--danger);font-size:0.75rem">Brak uprawnień</span>
                            <?php else: ?>
                                <div style="display:flex; flex-wrap:wrap; gap:4px">
                                    <?php foreach($p as $pk): if(isset($perm_list[$pk])): ?>
                                        <span style="font-size:9px; background:var(--surface-h); padding:2px 6px; border-radius:4px"><?= $perm_list[$pk] ?></span>
                                    <?php endif; endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button onclick='editAdmin(<?= json_encode($admin) ?>)' class="btn btn-g btn-sm"><i class="fa-solid fa-pen"></i></button>
                        <?php if(isset($me['id']) && $admin['id'] != $me['id']): ?>
                            <a href="users.php?delete=<?= $admin['id'] ?>" class="btn btn-d btn-sm" onclick="return confirm('Usunąć tego administratora?')"><i class="fa-solid fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <div class="card" style="border-top:4px solid var(--warn)">
        <div class="card-hd" id="form-title">Dodaj Administratora</div>
        <form method="POST">
            <input type="hidden" name="id" id="form-id" value="0">
            <div class="fg" style="margin-bottom:12px">
                <label>E-mail Logowania</label>
                <input type="email" name="email" id="form-email" required style="width:100%;padding:10px;background:var(--bg2);color:var(--text);border:1px solid var(--border);border-radius:6px">
            </div>
            <div class="fg" style="margin-bottom:12px">
                <label>Hasło (Zostaw puste, jeśli nie chcesz zmieniać)</label>
                <input type="password" name="password" id="form-pass" placeholder="********" style="width:100%;padding:10px;background:var(--bg2);color:var(--text);border:1px solid var(--border);border-radius:6px">
            </div>
            
            <div style="margin:20px 0; padding:15px; background:rgba(255,255,255,0.02); border-radius:8px">
                <label style="display:flex; align-items:center; gap:10px; margin-bottom:15px; cursor:pointer; font-weight:800; color:var(--warn)">
                    <input type="checkbox" name="is_super" id="form-super" onchange="togglePerms()"> Super Administrator (Wszystkie uprawnienia)
                </label>
                
                <div id="perms-container">
                    <label style="font-size:10px; color:var(--muted); text-transform:uppercase; letter-spacing:0.1em; display:block; margin-bottom:10px">Uprawnienia szczegółowe:</label>
                    <div style="display:grid; grid-template-columns:1fr; gap:8px">
                        <?php foreach($perm_list as $key => $name): ?>
                        <label style="display:flex; align-items:center; gap:10px; font-size:0.85rem; cursor:pointer">
                            <input type="checkbox" name="perms[]" value="<?= $key ?>" class="perm-check"> <?= $name ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-p" style="width:100%; justify-content:center"><i class="fa-solid fa-floppy-disk"></i> Zapisz Konto</button>
            <button type="button" onclick="location.href='users.php'" id="cancel-btn" style="width:100%; margin-top:8px; display:none" class="btn btn-d btn-sm">Anuluj edycję</button>
        </form>
    </div>
</div>

<script>
function togglePerms() {
    const isSuper = document.getElementById('form-super').checked;
    const container = document.getElementById('perms-container');
    container.style.opacity = isSuper ? '0.3' : '1';
    container.style.pointerEvents = isSuper ? 'none' : 'auto';
}

function editAdmin(admin) {
    document.getElementById('form-title').innerText = 'Edycja: ' + admin.email;
    document.getElementById('form-id').value = admin.id;
    document.getElementById('form-email').value = admin.email;
    document.getElementById('form-pass').required = false;
    document.getElementById('form-super').checked = (admin.is_super && admin.is_super == 1);
    document.getElementById('cancel-btn').style.display = 'block';
    
    // Reset checks
    const checks = document.querySelectorAll('.perm-check');
    checks.forEach(c => c.checked = false);
    
    // Apply perms
    if(admin.permissions) {
        const perms = JSON.parse(admin.permissions);
        checks.forEach(c => {
            if(perms.includes(c.value)) c.checked = true;
        });
    }
    
    togglePerms();
}
</script>

<?php include 'includes/footer.php'; ?>
