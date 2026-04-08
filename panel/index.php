<?php
session_start();
if (isset($_SESSION['account_id'])) { header('Location: dashboard.php'); exit; }
require_once 'db.php';

$error = ''; $success = ''; $mode = $_GET['mode'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode   = $_POST['mode'] ?? 'login';
    $email  = trim($_POST['email'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $name   = trim($_POST['name'] ?? '');

    if ($mode === 'register') {
        if (!$email || !$pass || !$name) {
            $error = 'Wypełnij wszystkie pola.';
        } elseif (strlen($pass) < 6) {
            $error = 'Hasło min. 6 znaków.';
        } else {
            $check = $pdo->prepare("SELECT id FROM accounts WHERE email=?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'Ten e-mail jest już zarejestrowany.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $ins  = $pdo->prepare("INSERT INTO accounts (email,password,name) VALUES (?,?,?)");
                $ins->execute([$email, $hash, $name]);
                $success = 'Konto założone! Możesz się teraz zalogować.';
                $mode = 'login';
            }
        }
    } else {
        $stmt = $pdo->prepare("SELECT * FROM accounts WHERE email=?");
        $stmt->execute([$email]);
        $acc = $stmt->fetch();
        if ($acc && password_verify($pass, $acc['password'])) {
            $_SESSION['account_id']   = $acc['id'];
            $_SESSION['account_name'] = $acc['name'];
            header('Location: dashboard.php'); exit;
        } else {
            $error = 'Nieprawidłowy e-mail lub hasło.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WikOS.run — Panel logowania</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;600;700;800&family=Fira+Code:wght@500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#030303;--bg2:#070707;--surface:rgba(255,255,255,0.03);--border:rgba(255,255,255,0.08);--green:#10b981;--green-dim:rgba(16,185,129,0.12);--green-glow:rgba(16,185,129,0.3);--text:#f1f5f9;--muted:#64748b;--danger:#ef4444;--r:18px;--rs:11px}
html,body{height:100%}
body{background:var(--bg);color:var(--text);font-family:'Space Grotesk',system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;-webkit-font-smoothing:antialiased}

/* BG FX */
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 80% 60% at 15% 50%, rgba(16,185,129,0.07) 0%, transparent 60%);pointer-events:none;z-index:0}
.scan{position:fixed;width:100%;height:2px;background:rgba(16,185,129,0.04);animation:scan 8s linear infinite;z-index:1}
@keyframes scan{0%{top:-2%}100%{top:102%}}

/* WRAPPER */
.wrap{position:relative;z-index:10;display:grid;grid-template-columns:1fr 1fr;min-height:100vh;width:100%}

/* LEFT — BRAND SIDE */
.brand{display:flex;flex-direction:column;justify-content:center;padding:60px 56px;border-right:1px solid var(--border);position:relative;overflow:hidden}
.brand::after{content:'';position:absolute;right:-80px;top:50%;transform:translateY(-50%);width:300px;height:300px;border-radius:50%;background:radial-gradient(circle,var(--green-glow),transparent 70%);opacity:.5;pointer-events:none}
.brand-logo{font-size:1.2rem;font-weight:800;font-style:italic;text-transform:uppercase;letter-spacing:-.03em;margin-bottom:48px}
.brand-logo em{color:var(--green);font-style:normal}
.brand-title{font-size:3.2rem;font-weight:800;font-style:italic;text-transform:uppercase;letter-spacing:-.04em;line-height:1.05;margin-bottom:20px}
.brand-title em{color:var(--green);font-style:normal}
.brand-sub{font-size:1rem;color:var(--muted);font-weight:400;line-height:1.7;max-width:360px}
.brand-features{margin-top:40px;display:flex;flex-direction:column;gap:12px}
.brand-feat{display:flex;align-items:center;gap:12px;font-size:.85rem;color:var(--muted)}
.brand-feat::before{content:'';width:6px;height:6px;background:var(--green);border-radius:50%;flex-shrink:0}

/* RIGHT — FORM SIDE */
.form-wrap{display:flex;flex-direction:column;justify-content:center;padding:60px 56px;background:var(--bg2)}
.form-title{font-size:1.6rem;font-weight:800;font-style:italic;text-transform:uppercase;letter-spacing:-.03em;margin-bottom:6px}
.form-sub{font-size:.85rem;color:var(--muted);margin-bottom:32px}
.tabs{display:flex;gap:4px;background:var(--surface);border-radius:var(--rs);padding:4px;border:1px solid var(--border);margin-bottom:28px}
.tab-btn{flex:1;padding:9px;border-radius:8px;border:none;background:transparent;color:var(--muted);font-family:inherit;font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;cursor:pointer;transition:all .15s}
.tab-btn.active{background:var(--green);color:#000}
.fg{margin-bottom:14px}
label{display:block;font-size:9px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:5px}
input{width:100%;background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.1);color:var(--text);padding:12px 14px;border-radius:var(--rs);font-size:.9rem;font-family:inherit;outline:none;transition:border-color .15s}
input:focus{border-color:var(--green);box-shadow:0 0 0 3px var(--green-dim)}
.btn-submit{width:100%;background:var(--green);color:#000;border:none;padding:13px;border-radius:var(--rs);font-size:.85rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;cursor:pointer;font-family:inherit;transition:all .15s;margin-top:6px}
.btn-submit:hover{background:#0ea570;box-shadow:0 4px 20px var(--green-glow)}
.alert{padding:11px 14px;border-radius:var(--rs);font-size:.82rem;margin-bottom:14px}
.alert-r{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);color:var(--danger)}
.alert-g{background:var(--green-dim);border:1px solid rgba(16,185,129,.3);color:var(--green)}
.form-footer{margin-top:32px;font-size:.78rem;color:var(--muted);text-align:center}
.form-footer a{color:var(--green);text-decoration:none;font-weight:600}

/* RESPONSIVE */
@media(max-width:768px){
  .wrap{grid-template-columns:1fr}
  .brand{display:none}
  .form-wrap{padding:40px 24px}
}
</style>
</head>
<body>
<div class="scan"></div>
<div class="wrap">
  <div class="brand">
    <div class="brand-logo">Wik<em>OS</em>.run</div>
    <h1 class="brand-title">Panel<br>Timing<br><em>System</em></h1>
    <p class="brand-sub">Profesjonalne narzędzie do zarządzania zawodnikami i analizy wyników biegowych.</p>
    <div class="brand-features">
      <div class="brand-feat">Pomiar czasu z dokładnością do setnych sekund</div>
      <div class="brand-feat">Profile zawodników z historią wyników</div>
      <div class="brand-feat">Live podgląd podczas biegu</div>
      <div class="brand-feat">Zarządzanie wieloma pachołkami</div>
    </div>
  </div>

  <div class="form-wrap">
    <div class="form-title"><?= $mode==='register' ? 'Rejestracja' : 'Logowanie' ?></div>
    <div class="form-sub">Witaj w systemie WikOS — zaloguj się do panelu operatora.</div>

    <div class="tabs">
      <button class="tab-btn <?= $mode==='login'?'active':'' ?>" onclick="setMode('login')">Logowanie</button>
      <button class="tab-btn <?= $mode==='register'?'active':'' ?>" onclick="setMode('register')">Rejestracja</button>
    </div>

    <?php if($error): ?><div class="alert alert-r"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-g"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="POST">
      <input type="hidden" name="mode" id="mode_field" value="<?= $mode ?>">
      <div class="fg" id="name_field" style="display:<?= $mode==='register'?'block':'none' ?>">
        <label>Imię i nazwisko</label>
        <input type="text" name="name" placeholder="Jan Kowalski" value="<?= htmlspecialchars($_POST['name']??'') ?>">
      </div>
      <div class="fg">
        <label>Adres e-mail</label>
        <input type="email" name="email" placeholder="trener@klub.pl" required value="<?= htmlspecialchars($_POST['email']??'') ?>">
      </div>
      <div class="fg">
        <label>Hasło</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-submit"><?= $mode==='register'?'Utwórz konto':'Zaloguj się' ?></button>
    </form>

    <div class="form-footer">
      Twórcy systemu: <a href="https://github.com/wiktorzalewski" target="_blank">Wiktor Zalewski</a> &amp; <a href="https://www.instagram.com/oskar_kulinskii/" target="_blank">Oskar Kuliński</a><br>
      Twórca strony: <a href="https://github.com/wiktorzalewski" target="_blank">Wiktor Zalewski</a><br>
      Kontakt w sprawie błędów: <a href="mailto:wiktorzalewski50@gmail.com">wiktorzalewski50@gmail.com</a>
    </div>
  </div>
</div>
<script>
function setMode(m){
  document.getElementById('mode_field').value=m;
  document.getElementById('name_field').style.display=m==='register'?'block':'none';
  document.querySelectorAll('.tab-btn').forEach((b,i)=>b.classList.toggle('active',i===(m==='login'?0:1)));
  document.querySelector('.form-title').textContent=m==='register'?'Rejestracja':'Logowanie';
  document.querySelector('.btn-submit').textContent=m==='register'?'Utwórz konto':'Zaloguj się';
}
</script>
</body>
</html>
