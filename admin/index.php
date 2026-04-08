<?php
session_start();
require_once 'db.php';

$checkUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$isFirstRun = ($checkUsers == 0);

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $pass  = $_POST['password'] ?? '';

    if ($isFirstRun) {
        $hashedPass = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        if ($stmt->execute([$email, $hashedPass])) {
            $success = 'Konto administratora utworzone! Zaloguj sie.';
            $isFirstRun = false;
        }
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['admin_email'] = $user['email'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Nieprawidlowy e-mail lub haslo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WikOS Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{background:#030303;color:#f1f5f9;font-family:'Space Grotesk',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh}
.box{background:#070707;border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:40px;width:100%;max-width:380px}
.logo{font-size:1.1rem;font-weight:800;font-style:italic;text-transform:uppercase;letter-spacing:-.02em;margin-bottom:28px;text-align:center}
.logo em{color:#10b981;font-style:normal}
h1{font-size:1.3rem;font-weight:800;font-style:italic;text-transform:uppercase;letter-spacing:-.02em;margin-bottom:6px}
p.sub{font-size:.8rem;color:#64748b;margin-bottom:24px}
label{display:block;font-size:9px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:#64748b;margin-bottom:5px}
.fg{margin-bottom:14px}
input{width:100%;background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.1);color:#f1f5f9;padding:11px 14px;border-radius:10px;font-size:.9rem;font-family:inherit;outline:none}
input:focus{border-color:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.12)}
.btn{width:100%;background:#10b981;color:#000;border:none;padding:12px;border-radius:10px;font-size:.85rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;cursor:pointer;font-family:inherit;margin-top:6px}
.btn:hover{background:#0ea570}
.alert{padding:10px 14px;border-radius:10px;font-size:.82rem;margin-bottom:14px}
.alert-r{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);color:#ef4444}
.alert-g{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#10b981}
</style>
</head>
<body>
<div class="box">
  <div class="logo">Wik<em>OS</em> Admin</div>
  <h1><?= $isFirstRun ? 'Pierwsze uruchomienie' : 'Logowanie' ?></h1>
  <p class="sub"><?= $isFirstRun ? 'Ustaw haslo administratora CMS.' : 'Panel zarzadzania strona glowna.' ?></p>

  <?php if ($error): ?><div class="alert alert-r"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-g"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <form method="POST">
    <div class="fg"><label>E-mail</label><input type="email" name="email" required></div>
    <div class="fg"><label>Haslo</label><input type="password" name="password" required></div>
    <button type="submit" class="btn"><?= $isFirstRun ? 'Ustaw haslo' : 'Zaloguj sie' ?></button>
  </form>
</div>
</body>
</html>
