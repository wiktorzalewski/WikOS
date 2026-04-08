<?php
require_once '../admin/db.php';

// Get all accounts (public results - last 50 results)
$results = $pdo->query("
    SELECT r.id, r.athlete_id, r.distance_m, r.time_ms, r.source, r.created_at,
           a.first_name, a.last_name, a.club,
           acc.name as trainer_name,
           e.name as event_name
    FROM results r
    JOIN athletes a ON r.athlete_id = a.id
    JOIN accounts acc ON r.account_id = acc.id
    LEFT JOIN events e ON r.event_id = e.id
    ORDER BY r.created_at DESC
    LIMIT 100
")->fetchAll();

// PBs per athlete+distance
$pbs_raw = $pdo->query("
    SELECT athlete_id, distance_m, MIN(time_ms) as pb
    FROM results GROUP BY athlete_id, distance_m
")->fetchAll();
$pbs = [];
foreach ($pbs_raw as $p) $pbs[$p['athlete_id']][$p['distance_m']] = $p['pb'];

// Top athletes per distance
$top = [];
foreach ([60, 100, 200, 400] as $d) {
    $s = $pdo->prepare("
        SELECT a.id as athlete_id, a.first_name, a.last_name, a.club, MIN(r.time_ms) as best
        FROM results r JOIN athletes a ON r.athlete_id=a.id
        WHERE r.distance_m=? GROUP BY r.athlete_id ORDER BY best ASC LIMIT 5
    ");
    $s->execute([$d]);
    $top[$d] = $s->fetchAll();
}

// All athletes
$all_athletes = $pdo->query("
    SELECT a.*, COUNT(r.id) as result_count 
    FROM athletes a 
    LEFT JOIN results r ON a.id = r.athlete_id 
    GROUP BY a.id 
    ORDER BY a.last_name, a.first_name
")->fetchAll();

function fmt($ms) {
    if (!$ms) return '--';
    $cs = floor($ms / 10) % 100;
    $sec = floor($ms / 1000);
    $min = floor($sec / 60); $sec %= 60;
    return $min > 0 ? sprintf('%d:%02d.%02d', $min, $sec, $cs) : sprintf('%d.%02d', $sec, $cs);
}

$last_result = $results[0] ?? null;
$server_time = date('H:i:s');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WikOS.run — Wyniki na żywo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;600;700;800&family=Fira+Code:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#030303;--bg2:#070707;--surface:rgba(255,255,255,0.03);--border:rgba(255,255,255,0.07);--border-l:rgba(255,255,255,0.12);--green:#10b981;--green-dim:rgba(16,185,129,0.1);--green-glow:rgba(16,185,129,0.3);--text:#f1f5f9;--muted:#64748b;--warn:#f59e0b;--r:16px;--rs:10px}
html,body{min-height:100%}
body{background:var(--bg);color:var(--text);font-family:'Space Grotesk',system-ui,sans-serif;-webkit-font-smoothing:antialiased}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 50% 0%, rgba(16,185,129,0.06), transparent 60%);pointer-events:none;z-index:0}
.scan{position:fixed;width:100%;height:1px;background:rgba(16,185,129,0.06);animation:scan 10s linear infinite;z-index:1}
@keyframes scan{0%{top:-1%}100%{top:101%}}
nav{position:sticky;top:0;z-index:100;background:rgba(3,3,3,0.85);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);padding:14px 28px;display:flex;align-items:center;justify-content:space-between}
.logo{font-size:1.1rem;font-weight:900;font-style:italic;text-transform:uppercase;letter-spacing:-.04em}
.logo span{color:var(--green)}
.nav-info{display:flex;align-items:center;gap:16px}
.live-badge{display:flex;align-items:center;gap:6px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--green)}
.live-dot{width:6px;height:6px;background:var(--green);border-radius:50%;animation:blink .9s ease-in-out infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.2}}
.srv-time{font-family:'Fira Code',monospace;font-size:.8rem;color:var(--muted);letter-spacing:.05em}
.hero{padding:40px 28px;border-bottom:1px solid var(--border);background:var(--bg2);position:relative;z-index:10}
.hero-inner{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr auto;gap:28px;align-items:center}
.hero-label{font-size:9px;font-weight:700;letter-spacing:.3em;text-transform:uppercase;color:var(--muted);margin-bottom:10px}
.hero-name{font-size:2rem;font-weight:800;letter-spacing:-.03em;margin-bottom:4px}
.hero-event{font-size:.85rem;color:var(--muted)}
.hero-time{text-align:right}
.hero-dist{font-size:.8rem;color:var(--green);font-weight:700;text-transform:uppercase;letter-spacing:.1em;margin-bottom:4px}
.hero-result{font-family:'Fira Code',monospace;font-size:3.5rem;font-weight:700;color:var(--green);text-shadow:0 0 40px var(--green-glow);letter-spacing:-.03em}
main{max-width:1100px;margin:0 auto;padding:28px;position:relative;z-index:10}
.section-title{font-size:9px;font-weight:700;letter-spacing:.25em;text-transform:uppercase;color:var(--muted);margin-bottom:14px;margin-top:28px}
.ranking-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:32px}
.ranking-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:16px;overflow:hidden;position:relative}
.ranking-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--green)}
.rank-dist{font-size:1.2rem;font-weight:800;font-style:italic;color:var(--green);margin-bottom:10px}
.rank-row{display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border)}
.rank-row:last-child{border-bottom:none}
.rank-pos{width:18px;font-size:.7rem;font-weight:800;color:var(--muted);flex-shrink:0}
.rank-pos.gold{color:#f59e0b}
.rank-pos.silver{color:#94a3b8}
.rank-pos.bronze{color:#cd7c2f}
.rank-name{flex:1;font-size:.82rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rank-club{font-size:.7rem;color:var(--muted)}
.rank-time{font-family:'Fira Code',monospace;font-size:.9rem;font-weight:700;color:var(--green);flex-shrink:0}
.results-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow:hidden}
.results-card-hd{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.results-card-title{font-size:9px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--muted)}
table{width:100%;border-collapse:collapse;font-size:.85rem}
th{text-align:left;padding:8px 14px;font-size:9px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);background:var(--bg2);border-bottom:1px solid var(--border)}
td{padding:11px 14px;border-bottom:1px solid var(--border);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(255,255,255,0.02)}
.time-cell{font-family:'Fira Code',monospace;font-weight:700;font-size:1rem;color:var(--green)}
.new-row td{animation:highlight .8s ease-out forwards}
@keyframes highlight{0%{background:rgba(16,185,129,0.15)}100%{background:transparent}}
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:100px;font-size:10px;font-weight:700}
.bdg-g{background:var(--green-dim);color:var(--green)}
.bdg-grey{background:rgba(255,255,255,.05);color:var(--muted);border:1px solid var(--border)}
footer{padding:24px;text-align:center;border-top:1px solid var(--border);color:var(--muted);font-size:12px;margin-top:32px;line-height:1.6}
footer a{color:var(--green);text-decoration:none;font-weight:700}
.athlete-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; margin-bottom: 40px; }
.athlete-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 16px; text-align: center; text-decoration: none; color: var(--text); transition: all 0.2s; position: relative; overflow: hidden; }
.athlete-card:hover { border-color: var(--green); background: var(--green-dim); transform: translateY(-3px); }
.athlete-card .av { width: 64px; height: 64px; border-radius: 50%; margin: 0 auto 12px; border: 2px solid var(--border); overflow: hidden; background: var(--bg2); display: flex; align-items: center; justify-content: center; font-weight: 800; color: var(--muted); }
.athlete-card img { width: 100%; height: 100%; object-fit: cover; }
.athlete-card .name { font-size: 0.95rem; font-weight: 700; margin-bottom: 4px; display: block; }
.athlete-card .id-tag { font-family: 'Fira Code', monospace; font-size: 0.7rem; color: var(--green); opacity: 0.8; margin-bottom: 6px; display: block; }
.athlete-card .club { font-size: 0.75rem; color: var(--muted); margin-bottom: 8px; display: block; height: 1.2em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.athlete-card .stats { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--muted); letter-spacing: 0.1em; }
@media(max-width:640px){.hero-inner{grid-template-columns:1fr}.hero-time{text-align:left}.hero-result{font-size:2.5rem}.athlete-grid { grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; }}
</style>
</head>
<body>
<div class="scan"></div>
<nav>
  <div class="logo">WIK<span>OS</span>.RUN</div>
  <div class="nav-info">
    <div class="live-badge"><div class="live-dot"></div> LIVE</div>
    <div class="srv-time" id="clock"><?= $server_time ?></div>
  </div>
</nav>

<?php if ($last_result): ?>
<div class="hero">
  <div class="hero-inner">
    <div>
      <div class="hero-label">Ostatni zmierzony czas</div>
      <div class="hero-name"><?= htmlspecialchars($last_result['first_name'] . ' ' . $last_result['last_name']) ?></div>
      <div class="hero-event">
        <?= $last_result['club'] ? htmlspecialchars($last_result['club']) : '' ?>
        <?php if ($last_result['event_name']): ?>
          &mdash; <?= htmlspecialchars($last_result['event_name']) ?>
        <?php endif; ?>
        &mdash; <?= date('d.m.Y H:i', strtotime($last_result['created_at'])) ?>
      </div>
    </div>
    <div class="hero-time">
      <div class="hero-dist"><?= $last_result['distance_m'] ?>m</div>
      <div class="hero-result"><?= fmt($last_result['time_ms']) ?></div>
    </div>
  </div>
</div>
<?php endif; ?>

<main>
  <div class="section-title">Ranking — Rekordy dystansów</div>
  <div class="ranking-grid">
    <?php foreach ([60, 100, 200, 400] as $d): ?>
    <div class="ranking-card">
      <div class="rank-dist"><?= $d ?>m</div>
      <?php if (empty($top[$d])): ?>
      <div style="color:var(--muted);font-size:.8rem;padding:8px 0">Brak wyników</div>
      <?php else: ?>
      <?php foreach ($top[$d] as $i => $row):
        $posClass = $i===0?'gold':($i===1?'silver':($i===2?'bronze':''));
      ?>
      <div class="rank-row">
        <div class="rank-pos <?= $posClass ?>"><?= $i+1 ?></div>
        <div style="flex:1">
          <a href="athlete.php?id=<?= $row['athlete_id'] ?>" style="color:var(--text); text-decoration:none;" class="rank-name"><?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?></a>
          <?php if($row['club']): ?><div class="rank-club"><?= htmlspecialchars($row['club']) ?></div><?php endif; ?>
        </div>
        <div class="rank-time"><?= fmt($row['best']) ?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="section-title">Ostatnie wyniki</div>
  <div class="results-card">
    <div class="results-card-hd">
      <span class="results-card-title">Historia pomiarów (ostatnie 100)</span>
      <span style="font-size:.72rem;color:var(--muted)">Auto-odświeżanie co 15s</span>
    </div>
    <div style="overflow-x:auto">
    <table>
      <thead>
        <tr><th>#</th><th>Zawodnik</th><th>Dystans</th><th>Czas</th><th>Zrodło</th><th>Data</th></tr>
      </thead>
      <tbody>
        <?php foreach ($results as $i => $r): ?>
        <tr <?= $i===0?'class="new-row"':'' ?>>
          <td style="color:var(--muted);font-size:.75rem"><?= $r['id'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <a href="athlete.php?id=<?= $r['athlete_id'] ?>" style="font-weight:700; color:var(--text); text-decoration:none">
                <?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?>
              </a>
            </div>
          </td>
          <td style="font-weight:700"><?= $r['distance_m'] ?>m</td>
          <td class="time-cell"><?= fmt($r['time_ms']) ?></td>
          <td><?= $r['source']==='device' ? '<span class="badge bdg-g">pacholek</span>' : '<span class="badge bdg-grey">reczny</span>' ?></td>
          <td style="color:var(--muted);font-size:.78rem;white-space:nowrap"><?= date('H:i', strtotime($r['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</main>

<footer>
    <div>Twórcy systemu: <a href="https://www.instagram.com/wiktor8446/" target="_blank">Wiktor Zalewski</a> &amp; <a href="https://www.instagram.com/oskar_kulinskii/" target="_blank">Oskar Kuliński</a></div>
    <div>Kontakt w sprawie błędów: <a href="mailto:contact@wikos.run">contact@wikos.run</a></div>
</footer>

<script>
function tick() {
  const n = new Date();
  document.getElementById('clock').textContent = String(n.getHours()).padStart(2,'0') + ':' + String(n.getMinutes()).padStart(2,'0') + ':' + String(n.getSeconds()).padStart(2,'0');
}
setInterval(tick, 1000);
</script>
</body>
</html>
