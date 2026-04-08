<?php
session_start(); require_once 'includes/auth.php'; require_once 'db.php';

$id = (int)($_GET['id'] ?? 0);
$a = $pdo->prepare("SELECT * FROM athletes WHERE id=? AND account_id=?");
$a->execute([$id, $uid]); $athlete = $a->fetch();
if(!$athlete) { header('Location: athletes.php'); exit; }

$page_title = $athlete['first_name'].' '.$athlete['last_name'];
$current_page = 'athletes';

// Delete result
if(isset($_GET['del_result']) && is_numeric($_GET['del_result'])) {
    $pdo->prepare("DELETE FROM results WHERE id=? AND account_id=?")->execute([$_GET['del_result'],$uid]);
    header("Location: athlete.php?id=$id"); exit;
}

// Manual result add
if($_SERVER['REQUEST_METHOD']==='POST') {
    $dist = (int)$_POST['distance_m'];
    // Parse time: support "10.45" or "1:23.45" format
    $raw  = trim($_POST['time_raw']);
    $ms   = 0;
    if(preg_match('/^(\d+):(\d+)\.(\d{1,2})$/', $raw, $m)) {
        $ms = ($m[1]*60+$m[2])*1000 + (int)str_pad($m[3],2,'0')*10;
    } elseif(preg_match('/^(\d+)\.(\d{1,2})$/', $raw, $m)) {
        $ms = $m[1]*1000 + (int)str_pad($m[2],2,'0')*10;
    }
    $eid  = (int)($_POST['event_id']??0) ?: null;
    $note = trim($_POST['notes']??'');
    if($ms > 0 && $dist > 0) {
        $pdo->prepare("INSERT INTO results (account_id,athlete_id,event_id,distance_m,time_ms,source,notes) VALUES (?,?,?,?,?,'manual',?)")
            ->execute([$uid,$id,$eid,$dist,$ms,$note]);
        header("Location: athlete.php?id=$id&msg=added"); exit;
    }
}

// All results
$results = $pdo->prepare("
    SELECT r.*, e.name as event_name FROM results r
    LEFT JOIN events e ON r.event_id=e.id
    WHERE r.athlete_id=? ORDER BY r.created_at DESC
");
$results->execute([$id]); $all_results = $results->fetchAll();

// PBs per distance
$distances = [60,100,200,400];
$pbs = [];
foreach($distances as $d) {
    $s = $pdo->prepare("SELECT MIN(time_ms) FROM results WHERE athlete_id=? AND distance_m=?");
    $s->execute([$id,$d]); $pbs[$d] = $s->fetchColumn();
}

// Chart data — last 15 results for each distance (for Chart.js)
$chart_data = [];
foreach([100,200,400] as $d) {
    $s = $pdo->prepare("SELECT time_ms, DATE_FORMAT(created_at,'%d.%m') as lbl FROM results WHERE athlete_id=? AND distance_m=? ORDER BY created_at ASC LIMIT 20");
    $s->execute([$id,$d]);
    $rows = $s->fetchAll();
    $chart_data[$d] = $rows;
}

// Events list for form
$events_list = $pdo->prepare("SELECT id,name FROM events WHERE account_id=? ORDER BY date DESC LIMIT 20");
$events_list->execute([$uid]); $events_list=$events_list->fetchAll();

function fmt($ms){if(!$ms)return '—';$cs=$ms/10%100;$sec=floor($ms/1000);$min=floor($sec/60);$sec%=60;return $min>0?sprintf('%d:%02d.%02d',$min,$sec,$cs):sprintf('%d.%02d',$sec,$cs);}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<div class="ph">
  <div style="display:flex;align-items:center;gap:16px">
    <a href="athletes.php" class="btn btn-g btn-sm"><i class="fa-solid fa-arrow-left"></i></a>
    <div class="av" style="width:52px;height:52px;font-size:17px;flex-shrink:0">
      <?php if($athlete['photo']): ?><img src="uploads/athletes/<?= $athlete['photo'] ?>" alt=""><?php else: ?><?= strtoupper(mb_substr($athlete['first_name'],0,1).mb_substr($athlete['last_name'],0,1)) ?><?php endif; ?>
    </div>
    <div>
      <div class="ph-title"><?= htmlspecialchars($athlete['first_name'].' '.$athlete['last_name']) ?></div>
      <div class="ph-sub"><?= $athlete['club']?htmlspecialchars($athlete['club']):'Brak klubu' ?><?= $athlete['birth_year']?" · ur. {$athlete['birth_year']}":''; ?> · <?= count($all_results) ?> wyników</div>
    </div>
  </div>
  <a href="measure.php?athlete=<?= $id ?>" class="btn btn-p"><i class="fa-solid fa-stopwatch"></i> Zacznij pomiar</a>
</div>

<?php if(isset($_GET['msg'])): ?>
<div class="alert alert-g" style="margin-bottom:16px">✓ Wynik dodany pomyślnie.</div>
<?php endif; ?>

<!-- PBs -->
<div class="stats-grid" style="margin-bottom:20px">
  <?php foreach($distances as $d): ?>
  <div class="stat <?= $pbs[$d]?'g':'' ?>">
    <div class="stat-lbl">PB <?= $d ?>m</div>
    <div class="stat-val" style="font-size:1.4rem;font-family:'Fira Code',monospace"><?= fmt($pbs[$d]) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="grid-2" style="gap:20px;margin-bottom:20px">
  <!-- Chart -->
  <div class="card">
    <div class="card-hd">
      <span class="card-title">Postęp czasów</span>
      <select id="chart-dist" class="btn btn-g btn-sm" style="padding:4px 8px;width:auto">
        <option value="100">100m</option><option value="200">200m</option><option value="400">400m</option>
      </select>
    </div>
    <div style="position:relative; height:180px; width:100%;">
      <canvas id="myChart"></canvas>
    </div>
  </div>

  <!-- Add manual result -->
  <div class="card">
    <div class="card-hd"><span class="card-title">Dodaj wynik ręcznie</span></div>
    <form method="POST">
      <div class="fg">
        <label>Dystans</label>
        <select name="distance_m">
          <option value="60">60m</option><option value="100">100m</option>
          <option value="200">200m</option><option value="400">400m</option>
          <option value="800">800m</option><option value="300">300m</option>
        </select>
      </div>
      <div class="fg">
        <label>Czas (np. 10.45 lub 1:23.45)</label>
        <input type="text" name="time_raw" placeholder="10.45" required pattern="\d+(\.\d{1,2}|\:\d{2}\.\d{1,2})">
      </div>
      <div class="fg">
        <label>Sesja (opcjonalnie)</label>
        <select name="event_id">
          <option value="">— Brak sesji —</option>
          <?php foreach($events_list as $ev): ?>
          <option value="<?= $ev['id'] ?>"><?= htmlspecialchars($ev['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fg">
        <label>Notatki</label>
        <input type="text" name="notes" placeholder="np. wiatr +1.2 m/s">
      </div>
      <button type="submit" class="btn btn-p"><i class="fa-solid fa-plus"></i> Dodaj</button>
    </form>
  </div>
</div>

<!-- Results table -->
<div class="card">
  <div class="card-hd"><span class="card-title">Wszystkie wyniki</span></div>
  <?php if(empty($all_results)): ?>
  <div class="empty"><i class="fa-solid fa-stopwatch"></i><p>Brak zmierzonych czasów.</p></div>
  <?php else: ?>
  <div class="tbl-wrap">
  <table>
    <tr><th>Dystans</th><th>Czas</th><th>PB?</th><th>Sesja</th><th>Źródło</th><th>Data</th><th></th></tr>
    <?php foreach($all_results as $r):
      $is_pb = $pbs[$r['distance_m']] == $r['time_ms'];
    ?>
    <tr>
      <td><?= $r['distance_m'] ?>m</td>
      <td class="time-mono"><?= fmt($r['time_ms']) ?></td>
      <td><?= $is_pb ? '<span class="badge bdg-y">★ PB</span>' : '' ?></td>
      <td style="color:var(--muted);font-size:.82rem"><?= $r['event_name']?htmlspecialchars($r['event_name']):'—' ?></td>
      <td><?= $r['source']==='device'?'<span class="badge bdg-g">pachołek</span>':'<span class="badge bdg-grey">ręczny</span>' ?></td>
      <td style="color:var(--muted);font-size:.8rem"><?= date('d.m.Y H:i',strtotime($r['created_at'])) ?></td>
      <td><a href="athlete.php?id=<?= $id ?>&del_result=<?= $r['id'] ?>" class="btn btn-d btn-sm" onclick="return confirm('Usunąć?')"><i class="fa-solid fa-trash"></i></a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const chartData = <?= json_encode($chart_data) ?>;
let myChart;
function buildChart(dist) {
  const rows = chartData[dist] || [];
  const labels = rows.map(r=>r.lbl);
  const data   = rows.map(r=>r.time_ms/1000);
  if(myChart) myChart.destroy();
  const ctx = document.getElementById('myChart').getContext('2d');
  myChart = new Chart(ctx, {
    type:'line',
    data:{
      labels,
      datasets:[{
        label: dist+'m',
        data,
        borderColor:'#10b981',
        backgroundColor:'rgba(16,185,129,0.08)',
        pointBackgroundColor:'#10b981',
        tension:0.3, fill:true, pointRadius:4
      }]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      plugins:{legend:{display:false},tooltip:{callbacks:{label:v=>{
        const s=v.raw; const min=Math.floor(s/60); const sec=(s%60).toFixed(2);
        return min>0?`${min}:${sec.padStart(5,'0')}`:`${sec}s`;
      }}}},
      scales:{
        x:{ticks:{color:'#64748b'},grid:{color:'rgba(255,255,255,0.04)'}},
        y:{ticks:{color:'#64748b',callback:v=>`${v}s`},grid:{color:'rgba(255,255,255,0.04)'}}
      }
    }
  });
}
buildChart(100);
document.getElementById('chart-dist').addEventListener('change', e=>buildChart(e.target.value));
</script>
<?php include 'includes/footer.php'; ?>
