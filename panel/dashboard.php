<?php
session_start(); require_once 'includes/auth.php'; require_once 'db.php';
$page_title = 'Dashboard'; $current_page = 'dashboard';

// Stats
$total_athletes = $pdo->prepare("SELECT COUNT(*) FROM athletes WHERE account_id=?");
$total_athletes->execute([$uid]); $total_athletes = $total_athletes->fetchColumn();

$total_results = $pdo->prepare("SELECT COUNT(*) FROM results WHERE account_id=?");
$total_results->execute([$uid]); $total_results = $total_results->fetchColumn();

$total_events = $pdo->prepare("SELECT COUNT(*) FROM events WHERE account_id=?");
$total_events->execute([$uid]); $total_events = $total_events->fetchColumn();

$total_devices = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE account_id=?");
$total_devices->execute([$uid]); $total_devices = $total_devices->fetchColumn();

// Recent results
$recent = $pdo->prepare("
    SELECT r.*, a.first_name, a.last_name, e.name as event_name
    FROM results r
    JOIN athletes a ON r.athlete_id=a.id
    LEFT JOIN events e ON r.event_id=e.id
    WHERE r.account_id=?
    ORDER BY r.created_at DESC LIMIT 10
");
$recent->execute([$uid]);
$recent_results = $recent->fetchAll();

// Device status
$devices_status = $pdo->prepare("SELECT * FROM devices WHERE account_id=? ORDER BY last_seen DESC LIMIT 5");
$devices_status->execute([$uid]);
$devices_list = $devices_status->fetchAll();

function fmt($ms) {
    $cs  = floor($ms / 10) % 100;
    $sec = floor($ms / 1000);
    $min = floor($sec / 60); $sec = $sec % 60;
    return $min > 0 ? sprintf('%d:%02d.%02d', $min, $sec, $cs) : sprintf('%d.%02d', $sec, $cs);
}
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<div class="ph">
  <div>
    <div class="ph-title">Dashboard</div>
    <div class="ph-sub">Witaj z powrotem, <?= htmlspecialchars($uname) ?> — <?= date('d.m.Y') ?></div>
  </div>
  <a href="measure.php" class="btn btn-p btn-lg"><i class="fa-solid fa-stopwatch"></i> Nowy pomiar</a>
</div>

<div class="stats-grid">
  <div class="stat g">
    <div class="stat-lbl">Zawodnicy</div>
    <div class="stat-val"><?= $total_athletes ?></div>
    <div class="stat-sub">zarejestrowanych profili</div>
  </div>
  <div class="stat b">
    <div class="stat-lbl">Wyniki</div>
    <div class="stat-val"><?= $total_results ?></div>
    <div class="stat-sub">zmierzonych czasów</div>
  </div>
  <div class="stat y">
    <div class="stat-lbl">Sesje</div>
    <div class="stat-val"><?= $total_events ?></div>
    <div class="stat-sub">treningów / zawodów</div>
  </div>
  <div class="stat <?= $total_devices>0?'g':'r' ?>">
    <div class="stat-lbl">Pachołki</div>
    <div class="stat-val"><?= $total_devices ?></div>
    <div class="stat-sub"><?= $total_devices>0?'urządzeń online':'brak urządzeń' ?></div>
  </div>
</div>

<div class="grid-2" style="gap:20px">
  <!-- Recent results -->
  <div class="card" style="grid-column:1/3">
    <div class="card-hd">
      <span class="card-title">Ostatnie wyniki</span>
      <a href="athletes.php" class="btn btn-g btn-sm"><i class="fa-solid fa-list"></i> Wszystkie</a>
    </div>
    <?php if(empty($recent_results)): ?>
    <div class="empty"><i class="fa-solid fa-stopwatch"></i><p>Brak wyników — zacznij od pomiaru!</p></div>
    <?php else: ?>
    <div class="tbl-wrap">
    <table>
      <tr><th>Zawodnik</th><th>Dystans</th><th>Czas</th><th>Sesja</th><th>Źródło</th><th>Data</th></tr>
      <?php foreach($recent_results as $r): ?>
      <tr>
        <td><strong><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></strong></td>
        <td><?= $r['distance_m'] ?>m</td>
        <td class="time-mono"><?= fmt($r['time_ms']) ?></td>
        <td><?= $r['event_name'] ? htmlspecialchars($r['event_name']) : '<span style="color:var(--muted)">—</span>' ?></td>
        <td><?= $r['source']==='device' ? '<span class="badge bdg-g">pachołek</span>' : '<span class="badge bdg-grey">ręczny</span>' ?></td>
        <td style="color:var(--muted);font-size:.8rem"><?= date('d.m H:i', strtotime($r['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Devices -->
  <div class="card">
    <div class="card-hd">
      <span class="card-title">Pachołki</span>
      <a href="devices.php" class="btn btn-g btn-sm"><i class="fa-solid fa-plus"></i> Dodaj</a>
    </div>
    <?php if(empty($devices_list)): ?>
    <div class="empty"><i class="fa-solid fa-satellite-dish"></i><p>Brak pachołków.<br>Dodaj pierwsze urządzenie.</p></div>
    <?php else: ?>
    <?php foreach($devices_list as $d): 
      $ago = $d['last_seen'] ? floor((time()-strtotime($d['last_seen']))/60) : null;
      $online = $ago !== null && $ago < 3;
    ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)">
      <div style="display:flex;align-items:center;gap:10px">
        <div style="width:8px;height:8px;border-radius:50%;background:<?= $online?'var(--green)':($d['status']==='pending'?'var(--warn)':'var(--muted)') ?>;flex-shrink:0"></div>
        <div>
          <div style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($d['name']) ?></div>
          <div style="font-size:10px;color:var(--muted)"><?= $online ? 'Online' : ($ago!==null?"{$ago}min temu":'Nigdy') ?></div>
        </div>
      </div>
      <span class="badge <?= $d['status']==='active'?'bdg-g':($d['status']==='pending'?'bdg-y':'bdg-grey') ?>"><?= $d['status'] ?></span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Quick actions -->
  <div class="card">
    <div class="card-hd"><span class="card-title">Szybkie akcje</span></div>
    <div style="display:flex;flex-direction:column;gap:10px">
      <a href="measure.php" class="btn btn-p" style="justify-content:center"><i class="fa-solid fa-stopwatch"></i> Zacznij pomiar</a>
      <a href="athletes.php?add=1" class="btn btn-g" style="justify-content:center"><i class="fa-solid fa-user-plus"></i> Dodaj zawodnika</a>
      <a href="events.php?add=1" class="btn btn-g" style="justify-content:center"><i class="fa-solid fa-calendar-plus"></i> Nowa sesja</a>
      <a href="devices.php?add=1" class="btn btn-g" style="justify-content:center"><i class="fa-solid fa-satellite-dish"></i> Zarejestruj pachołek</a>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
