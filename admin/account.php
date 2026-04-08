<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
$acc_q = $pdo->prepare("SELECT * FROM accounts WHERE id=?");
$acc_q->execute([$id]);
$account = $acc_q->fetch();

if(!$account) { header('Location: accounts.php'); exit; }

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['new_password'] ?? '';
    
    if($name && $email) {
        $pdo->prepare("UPDATE accounts SET name=?, email=? WHERE id=?")->execute([$name, $email, $id]);
        if(!empty($pass)) {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE accounts SET password=? WHERE id=?")->execute([$hash, $id]);
        }
        header("Location: account.php?id=$id&msg=saved"); exit;
    }
}
include 'includes/header.php';
?>
<div class="ph">
  <div style="display:flex;align-items:center;gap:16px">
    <a href="accounts.php" class="btn btn-g btn-sm"><i class="fa-solid fa-arrow-left"></i> Wróć</a>
    <div>
      <div class="ph-title">Konto Trenera #<?= $id ?></div>
    </div>
  </div>
</div>

<?php if(isset($_GET['msg'])): ?>
<div class="alert alert-g" style="padding:16px;background:var(--green-dim);color:var(--green);border-radius:var(--rs);margin-bottom:20px;font-weight:700">✓ Zmiany zapisane.</div>
<?php endif; ?>

<div class="card" style="max-width:600px">
  <div class="card-hd">Edycja Danych</div>
  <form method="POST">
    <div class="fg" style="margin-bottom:14px">
      <label>Imię i Nazwisko</label>
      <input type="text" name="name" value="<?= htmlspecialchars($account['name']) ?>" required style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:#fff;border-radius:6px">
    </div>
    <div class="fg" style="margin-bottom:14px">
      <label>Adres E-mail (Służy do logowania)</label>
      <input type="email" name="email" value="<?= htmlspecialchars($account['email']) ?>" required style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:#fff;border-radius:6px">
    </div>
    <div class="fg" style="margin-bottom:20px">
      <label>Nowe Hasło</label>
      <input type="text" name="new_password" placeholder="Wpisz aby zresetować. Zostaw puste by zachować obecne." style="width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);color:var(--warn);border-radius:6px">
    </div>
    <button type="submit" class="btn btn-p"><i class="fa-solid fa-floppy-disk"></i> Zapisz zmiany</button>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
