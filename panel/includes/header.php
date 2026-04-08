<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="description" content="WikOS.run — Panel operatora systemu pomiaru czasu dla lekkoatletow">
<meta name="author" content="Wiktor Zalewski">
<meta name="theme-color" content="#030303">
<title><?= htmlspecialchars($page_title ?? 'Panel') ?> — WikOS</title>
<link rel="icon" type="image/png" href="favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#030303;--bg2:#070707;
  --surface:rgba(255,255,255,0.03);--surface-h:rgba(255,255,255,0.06);
  --border:rgba(255,255,255,0.07);--border-l:rgba(255,255,255,0.13);
  --green:#10b981;--green-dim:rgba(16,185,129,0.12);--green-glow:rgba(16,185,129,0.25);
  --text:#f1f5f9;--muted:#64748b;--dim:#1e293b;
  --danger:#ef4444;--warn:#f59e0b;--info:#3b82f6;
  --sw:240px;--r:16px;--rs:10px;
}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:'Space Grotesk',system-ui,sans-serif;min-height:100vh;line-height:1.6;-webkit-font-smoothing:antialiased}
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-track{background:var(--bg)}::-webkit-scrollbar-thumb{background:var(--border-l);border-radius:3px}
.layout{display:flex;min-height:100vh}
.hamburger{display:none;position:fixed;top:12px;left:12px;z-index:300;background:var(--bg2);border:1px solid var(--border-l);border-radius:var(--rs);padding:10px 12px;cursor:pointer;color:var(--text);font-size:1rem;line-height:1;align-items:center;justify-content:center}
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:90;backdrop-filter:blur(2px)}
.overlay.open{display:block}
.sidebar{width:var(--sw);background:var(--bg2);border-right:1px solid var(--border);position:fixed;top:0;left:0;bottom:0;display:flex;flex-direction:column;z-index:100;overflow-y:auto;transition:transform .25s ease}
.sb-logo{padding:24px 20px 18px;border-bottom:1px solid var(--border)}
.sb-logo-text{font-size:1.05rem;font-weight:900;letter-spacing:-.04em;font-style:italic;text-transform:uppercase;color:#fff}
.sb-logo-text span{color:var(--green)}
.sb-badge{font-size:9px;color:var(--muted);font-weight:700;letter-spacing:.15em;text-transform:uppercase;margin-top:3px}
.sb-nav{flex:1;padding:12px 10px}
.sb-label{font-size:9px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--dim);padding:10px 8px 3px;margin-top:6px}
.sb-link{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:var(--rs);color:var(--muted);text-decoration:none;font-size:.85rem;font-weight:500;transition:all .15s ease;margin-bottom:2px;touch-action:manipulation}
.sb-link:hover{background:var(--surface-h);color:var(--text)}
.sb-link.active{background:var(--green-dim);color:var(--green);border:1px solid rgba(16,185,129,.2)}
.sb-link i{width:16px;text-align:center;font-size:.78rem}
.sb-bottom{padding:14px 10px;border-top:1px solid var(--border)}
.sb-user{display:flex;align-items:center;gap:9px;padding:7px 8px}
.sb-av{width:30px;height:30px;background:var(--green-dim);border:1px solid var(--green-glow);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--green);flex-shrink:0}
.sb-uname{font-size:.78rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.sb-urole{font-size:9px;color:var(--muted)}
.sb-logout{display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:var(--rs);color:var(--danger);text-decoration:none;font-size:.78rem;font-weight:600;transition:all .15s;margin-top:3px}
.sb-logout:hover{background:rgba(239,68,68,.1)}
.main{margin-left:var(--sw);flex:1;padding:32px;min-height:100vh}
.ph{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px}
.ph-title{font-size:1.5rem;font-weight:800;font-style:italic;text-transform:uppercase;letter-spacing:-.03em}
.ph-sub{font-size:.78rem;color:var(--muted);margin-top:2px}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px}
.stat{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:18px;transition:all .2s}
.stat:hover{border-color:var(--border-l);background:var(--surface-h)}
.stat-lbl{font-size:9px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
.stat-val{font-size:1.7rem;font-weight:800;letter-spacing:-.04em;line-height:1}
.stat-sub{font-size:10px;color:var(--muted);margin-top:5px}
.stat.g{border-left:2px solid var(--green)}.stat.g .stat-val{color:var(--green)}
.stat.r{border-left:2px solid var(--danger)}.stat.r .stat-val{color:var(--danger)}
.stat.y{border-left:2px solid var(--warn)}.stat.y .stat-val{color:var(--warn)}
.stat.b{border-left:2px solid var(--info)}.stat.b .stat-val{color:var(--info)}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:22px}
.card-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px}
.card-title{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.2em;color:var(--muted)}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.tbl-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
table{width:100%;border-collapse:collapse;font-size:.85rem}
th{text-align:left;padding:8px 14px;font-size:9px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border)}
td{padding:11px 14px;border-bottom:1px solid var(--border);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:var(--surface-h)}
.time-mono{font-family:'Fira Code',monospace;font-weight:600;color:var(--green);font-size:.95rem}
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:var(--rs);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;cursor:pointer;border:none;transition:all .15s;text-decoration:none;font-family:inherit;white-space:nowrap;touch-action:manipulation}
.btn-p{background:var(--green);color:#000}.btn-p:hover{background:#0ea570;transform:translateY(-1px);box-shadow:0 4px 18px var(--green-glow)}
.btn-g{background:var(--surface);color:var(--muted);border:1px solid var(--border)}.btn-g:hover{background:var(--surface-h);color:var(--text);border-color:var(--border-l)}
.btn-d{background:rgba(239,68,68,.1);color:var(--danger);border:1px solid rgba(239,68,68,.2)}.btn-d:hover{background:rgba(239,68,68,.2)}
.btn-sm{padding:5px 11px;font-size:.7rem}.btn-lg{padding:13px 26px;font-size:.9rem}
.fg{margin-bottom:14px}
label{display:block;font-size:9px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:5px}
input,select,textarea{width:100%;background:rgba(0,0,0,.5);border:1px solid var(--border-l);color:var(--text);padding:9px 13px;border-radius:var(--rs);font-size:16px;font-family:inherit;transition:border-color .15s;outline:none}
input:focus,select:focus,textarea:focus{border-color:var(--green);box-shadow:0 0 0 3px var(--green-dim)}
select option{background:#111}
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:100px;font-size:10px;font-weight:700}
.bdg-g{background:var(--green-dim);color:var(--green)}.bdg-r{background:rgba(239,68,68,.1);color:var(--danger)}
.bdg-y{background:rgba(245,158,11,.1);color:var(--warn)}.bdg-b{background:rgba(59,130,246,.1);color:var(--info)}
.bdg-grey{background:var(--surface);color:var(--muted);border:1px solid var(--border)}
.av{width:38px;height:38px;border-radius:50%;background:var(--green-dim);border:1px solid var(--green-glow);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:var(--green);flex-shrink:0;overflow:hidden}
.av img{width:100%;height:100%;object-fit:cover}
.alert{padding:12px 16px;border-radius:var(--rs);font-size:.85rem;margin-bottom:16px}
.alert-g{background:var(--green-dim);border:1px solid rgba(16,185,129,.3);color:var(--green)}
.alert-r{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.3);color:var(--danger)}
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:200;align-items:center;justify-content:center;padding:16px}
.modal-bg.open{display:flex}
.modal{background:var(--bg2);border:1px solid var(--border-l);border-radius:var(--r);padding:24px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;position:relative}
.modal-title{font-size:1rem;font-weight:800;font-style:italic;text-transform:uppercase;letter-spacing:-.02em;margin-bottom:20px}
.modal-close{position:absolute;top:14px;right:14px;background:none;border:none;color:var(--muted);cursor:pointer;font-size:1.1rem}
.modal-close:hover{color:var(--text)}
.empty{text-align:center;padding:40px 24px;color:var(--muted)}
.empty i{font-size:2.5rem;margin-bottom:12px;display:block;opacity:.4}
.empty p{font-size:.9rem}
.divider{height:1px;background:var(--border);margin:20px 0}
.panel-footer{padding:24px 0 12px;border-top:1px solid var(--border);margin-top:32px;text-align:center;color:var(--muted);font-size:.72rem;line-height:2}
.panel-footer a{color:var(--green);text-decoration:none;font-weight:600}
/* RESPONSIVE */
@media(max-width:768px){
  .hamburger{display:flex}
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .main{margin-left:0;padding:16px;padding-top:56px}
  .stats-grid{grid-template-columns:1fr 1fr}
  .grid-2,.grid-3{grid-template-columns:1fr}
  .ph{flex-direction:column;align-items:flex-start}
  .ph-title{font-size:1.2rem}
  .tbl-wrap table{min-width:560px}
  .btn-lg{padding:11px 18px;font-size:.8rem}
}
@media(max-width:400px){.stats-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<button class="hamburger" id="hamburger" aria-label="Menu">
  <i class="fa-solid fa-bars"></i>
</button>
<div class="overlay" id="overlay" onclick="closeSidebar()"></div>
<div class="layout">
