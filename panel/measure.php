<?php
session_start(); require_once 'includes/auth.php'; require_once 'db.php';
$page_title = 'Pomiar'; $current_page = 'measure';

// Get data
$athletes_list = $pdo->prepare("SELECT id,first_name,last_name,photo FROM athletes WHERE account_id=? ORDER BY last_name,first_name");
$athletes_list->execute([$uid]); $athletes = $athletes_list->fetchAll();

$events_list = $pdo->prepare("SELECT id,name FROM events WHERE account_id=? ORDER BY date DESC LIMIT 20");
$events_list->execute([$uid]); $events = $events_list->fetchAll();

$devices_list = $pdo->prepare("SELECT * FROM devices WHERE account_id=? AND status!='pending' ORDER BY last_seen DESC");
$devices_list->execute([$uid]); $devices = $devices_list->fetchAll();

// Pre-selected athlete
$pre_athlete = isset($_GET['athlete']) ? (int)$_GET['athlete'] : 0;

// Get safe times from first device (or defaults)
$safe_times = ['60'=>5000,'100'=>8000,'200'=>18000,'400'=>40000,'custom'=>8000];
if(!empty($devices)) {
    $d = $devices[0];
    $safe_times = ['60'=>$d['safe_time_60'],'100'=>$d['safe_time_100'],'200'=>$d['safe_time_200'],'400'=>$d['safe_time_400'],'custom'=>$d['safe_time_custom']??8000];
}

// Handle manual save from measure
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='save_result') {
    $aid  = (int)$_POST['athlete_id'];
    $eid  = (int)($_POST['event_id']??0) ?: null;
    $dist = (int)$_POST['distance_m'];
    $ms   = (int)$_POST['time_ms'];
    $src  = $_POST['source'] ?? 'manual';
    $note = trim($_POST['notes']??'');
    if($aid && $dist && $ms > 0) {
        $pdo->prepare("INSERT INTO results (account_id,athlete_id,event_id,distance_m,time_ms,source,notes) VALUES (?,?,?,?,?,?,?)")
            ->execute([$uid,$aid,$eid,$dist,$ms,$src,$note]);
        echo json_encode(['ok'=>true]); exit;
    }
    echo json_encode(['ok'=>false]); exit;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<style>
.step{display:none}.step.active{display:block}
.dist-btn{background:var(--surface);border:2px solid var(--border);border-radius:var(--r);padding:20px;text-align:center;cursor:pointer;transition:all .2s;font-family:inherit;color:var(--text);width:100%}
.dist-btn:hover{border-color:var(--green);background:var(--green-dim)}
.dist-btn.selected{border-color:var(--green);background:var(--green-dim)}
.dist-btn .dist-val{font-size:1.6rem;font-weight:800;font-style:italic;letter-spacing:-.03em}
.dist-btn .dist-sub{font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.1em}
.dist-btn.selected .dist-sub{color:var(--green)}

.ath-btn{display:flex;align-items:center;gap:12px;background:var(--surface);border:2px solid var(--border);border-radius:var(--r);padding:12px 16px;cursor:pointer;transition:all .2s;font-family:inherit;color:var(--text);width:100%;text-align:left}
.ath-btn:hover{border-color:var(--green);background:var(--green-dim)}
.ath-btn.selected{border-color:var(--green);background:var(--green-dim)}

/* BIG TIMER */
.timer-screen{text-align:center;padding:32px 0}
.timer-label{font-size:9px;font-weight:700;letter-spacing:.3em;text-transform:uppercase;color:var(--muted);margin-bottom:20px}
.big-time{font-family:'Fira Code',monospace;font-size:5rem;font-weight:700;letter-spacing:-.03em;line-height:1;color:var(--green);text-shadow:0 0 40px rgba(16,185,129,0.4);transition:all .3s}
.big-time.waiting{color:var(--muted);text-shadow:none}
.big-time.finished{color:#fff;text-shadow:0 0 60px rgba(16,185,129,0.6);animation:pulse-result 1s ease-out}
@keyframes pulse-result{0%{transform:scale(1.15)}100%{transform:scale(1)}}
.big-ms{font-family:'Fira Code',monospace;font-size:2rem;color:var(--green);opacity:.7}
.safe-bar{height:4px;background:var(--border);border-radius:2px;margin:16px auto;max-width:400px;overflow:hidden}
.safe-bar-fill{height:100%;background:var(--warn);border-radius:2px;width:0%;transition:width .1s linear}
.safe-bar-fill.done{background:var(--green)}
.status-dot{width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:8px}
.status-dot.running{background:var(--green);animation:blink .8s ease-in-out infinite}
.status-dot.waiting{background:var(--warn);animation:blink 1.5s ease-in-out infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}

.step-header{display:flex;align-items:center;gap:10px;margin-bottom:20px}
.step-num{width:28px;height:28px;border-radius:50%;background:var(--green);color:#000;font-weight:800;font-size:.8rem;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.step-title-sm{font-size:.9rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted)}

// RESULT SAVED animation
.saved-flash{position:fixed;inset:0;background:rgba(16,185,129,.15);z-index:999;display:flex;align-items:center;justify-content:center;animation:flash-in .4s ease;pointer-events:none}
@keyframes flash-in{0%{opacity:0}50%{opacity:1}100%{opacity:0}}

/* MOBILE FIRST RWD */
.measure-layout { display: flex; flex-direction: column; gap: 20px; align-items: stretch; }
@media(min-width: 900px) {
    .measure-layout { display: grid; grid-template-columns: 360px 1fr; align-items: start; }
}

.sticky-timer { position: sticky; top: 20px; }
@media(max-width: 900px) {
    .sticky-timer { order: -1; z-index: 10; margin-bottom: 10px; }
}
</style>

<div class="ph">
  <div>
    <div class="ph-title"><i class="fa-solid fa-stopwatch"></i> Pomiary na żywo</div>
    <div class="ph-sub">Studio Sędziego. Oczekuj na wyniki ze sprzętu.</div>
  </div>
  <div id="session-badge" style="display:flex;align-items:center;gap:8px;font-size:.8rem;color:var(--muted)">
    <i class="fa-solid fa-circle-dot" style="color:var(--green)"></i>
    <span id="session-status">Gotowy do pomiaru</span>
  </div>
</div>

<div class="measure-layout">

<!-- LEFT: Config panel -->
<div>
  <!-- ATHLETE -->
  <div class="card" style="margin-bottom:16px">
    <div class="step-header">
      <div class="step-num">1</div>
      <div class="step-title-sm">Zawodnik</div>
    </div>
    <?php if(empty($athletes)): ?>
    <div class="empty" style="padding:20px"><i class="fa-solid fa-person-running"></i><p>Brak zawodników.<br><a href="athletes.php?add=1" style="color:var(--green)">Dodaj zawodnika</a></p></div>
    <?php else: ?>
    <div id="athlete-search-wrap" style="margin-bottom:10px">
      <input type="text" id="ath-search" placeholder="Szukaj zawodnika..." oninput="filterAthletes(this.value)" style="margin-bottom:8px">
    </div>
    <div id="ath-list" style="display:flex;flex-direction:column;gap:6px;max-height:240px;overflow-y:auto">
      <?php foreach($athletes as $a):
        $initials = strtoupper(mb_substr($a['first_name'],0,1).mb_substr($a['last_name'],0,1));
      ?>
      <button class="ath-btn <?= $pre_athlete==$a['id']?'selected':'' ?>" onclick="selectAthlete(<?= $a['id'] ?>,'<?= htmlspecialchars($a['first_name'].' '.$a['last_name'],ENT_QUOTES) ?>')" data-name="<?= htmlspecialchars(strtolower($a['first_name'].' '.$a['last_name']),ENT_QUOTES) ?>">
        <div class="av"><?php if($a['photo']): ?><img src="../uploads/athletes/<?= $a['photo'] ?>" alt=""><?php else: ?><?= $initials ?><?php endif; ?></div>
        <span style="font-weight:600;font-size:.9rem"><?= htmlspecialchars($a['first_name'].' '.$a['last_name']) ?></span>
      </button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- DISTANCE -->
  <div class="card" style="margin-bottom:16px">
    <div class="step-header">
      <div class="step-num">2</div>
      <div class="step-title-sm">Dystans</div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
      <?php foreach([60,100,200,400] as $d): ?>
      <button class="dist-btn <?= $d===100?'selected':'' ?>" onclick="selectDist(<?= $d ?>)" id="dist-<?= $d ?>">
        <div class="dist-val"><?= $d ?>m</div>
        <div class="dist-sub">safe <?= number_format($safe_times[$d]/1000,1) ?>s</div>
      </button>
      <?php endforeach; ?>
    </div>
    <div style="margin-top:10px; padding:10px; background:var(--bg); border:1px solid var(--border); border-radius:var(--rs)">
      <div style="display:flex;gap:12px; align-items:end">
        <div style="flex:1">
          <label>Własny dystans (m)</label>
          <input type="number" id="custom-dist" placeholder="np. 150" min="10" max="1000" oninput="if(this.value)selectDist(parseInt(this.value))">
        </div>
        <div style="flex:1">
          <label>Własny Safe-Time (s)</label>
          <input type="number" id="custom-safe" placeholder="Opcjonalny domyślny z ustawień..." min="1" max="120" oninput="updateCustomSafeTime(this.value)">
        </div>
      </div>
    </div>
  </div>

  <!-- SESSION -->
  <div class="card">
    <div class="step-header">
      <div class="step-num">3</div>
      <div class="step-title-sm">Sesja (opcjonalnie)</div>
    </div>
    <select id="event-select">
      <option value="">— Bez sesji —</option>
      <?php foreach($events as $ev): ?>
      <option value="<?= $ev['id'] ?>"><?= htmlspecialchars($ev['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<!-- RIGHT: Timing display -->
<div>
  <div class="card sticky-timer" style="text-align:center;padding:32px 16px;box-shadow:0 10px 40px rgba(0,0,0,0.4);border-color:var(--primary)">

    <!-- Athlete display -->
    <div id="athlete-display" style="margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border)">
      <div style="color:var(--muted);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.15em;margin-bottom:8px">Wybrany zawodnik</div>
      <div id="ath-name-display" style="font-size:1.3rem;font-weight:800">
        <?php if($pre_athlete): $pa=$pdo->prepare("SELECT first_name,last_name FROM athletes WHERE id=? AND account_id=?"); $pa->execute([$pre_athlete,$uid]); $pa=$pa->fetch(); echo htmlspecialchars($pa?$pa['first_name'].' '.$pa['last_name']:'—'); else: echo '—'; endif; ?>
      </div>
      <div id="dist-display" style="font-size:.9rem;color:var(--green);margin-top:4px;font-weight:600">100m</div>
    </div>

    <!-- Timer -->
    <div class="timer-screen">
      <div class="timer-label" id="timer-label">Oczekiwanie na start...</div>
      <div class="big-time waiting" id="big-time">--.-<span id="big-cs" class="big-ms">--</span></div>
    </div>

    <!-- Safe-time bar -->
    <div id="safe-wrap" style="display:none;margin:0 auto 24px;max-width:360px">
      <div style="font-size:9px;color:var(--warn);font-weight:700;letter-spacing:.2em;text-transform:uppercase;margin-bottom:6px">SAFE TIME</div>
      <div class="safe-bar"><div class="safe-bar-fill" id="safe-fill"></div></div>
      <div style="font-size:.75rem;color:var(--muted)" id="safe-label">Oczekiwanie...</div>
    </div>

    <!-- Status -->
    <div style="font-size:.85rem;color:var(--muted);margin-bottom:24px" id="status-line">
      <span class="status-dot waiting"></span>Czekam na wybór zawodnika i dystansu
    </div>

    <!-- Controls -->
    <div style="display:flex;flex-direction:column;gap:10px" id="controls">
      <button class="btn btn-p btn-lg" onclick="startManualTimer()" id="btn-start" style="justify-content:center" disabled>
        <i class="fa-solid fa-play"></i> Start (ręczny)
      </button>
      <button class="btn btn-g" onclick="stopManualTimer()" id="btn-stop" style="display:none;justify-content:center">
        <i class="fa-solid fa-stop"></i> Stop
      </button>
      <button class="btn btn-p" onclick="saveResult()" id="btn-save" style="display:none;justify-content:center">
        <i class="fa-solid fa-floppy-disk"></i> Zapisz wynik
      </button>
      <button class="btn btn-g" onclick="resetMeasure()" id="btn-reset" style="display:none;justify-content:center">
        <i class="fa-solid fa-rotate-right"></i> Nowy pomiar
      </button>
    </div>

    <!-- Manual time entry -->
    <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--border)">
      <div style="font-size:9px;color:var(--muted);font-weight:700;letter-spacing:.15em;text-transform:uppercase;margin-bottom:8px">Wpisz czas ręcznie</div>
      <div style="display:flex;gap:8px">
        <input type="text" id="manual-time" placeholder="10.45 lub 1:23.45" style="flex:1">
        <button class="btn btn-g" onclick="applyManualTime()"><i class="fa-solid fa-check"></i></button>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Save result modal (notes + confirm) -->
<div class="modal-bg" id="save-modal">
  <div class="modal">
    <button class="modal-close" onclick="closeSaveModal()">✕</button>
    <div class="modal-title">Zapisz wynik</div>
    <div id="save-summary" style="background:var(--green-dim);border:1px solid var(--green-glow);border-radius:var(--rs);padding:16px;margin-bottom:20px;font-family:'Fira Code',monospace;font-size:1.4rem;font-weight:700;text-align:center;color:var(--green)"></div>
    <div class="fg"><label>Notatki (opcjonalnie)</label><input type="text" id="save-notes" placeholder="np. wiatr +1.2 m/s"></div>
    <button class="btn btn-p" onclick="confirmSave()" style="width:100%;justify-content:center"><i class="fa-solid fa-floppy-disk"></i> Potwierdź zapis</button>
  </div>
</div>

<script>
let selectedAthlete = <?= $pre_athlete ?>, selectedAthleteN = '<?= $pre_athlete && isset($pa) ? htmlspecialchars($pa['first_name'].' '.$pa['last_name'],ENT_QUOTES) : '' ?>';
let selectedDist = 100;
let timerInterval = null, timerStart = null, timerMs = 0;
let safeTimeout = null;
<?php $st = json_encode($safe_times); ?>
const safeTimes = <?= $st ?>;

function selectAthlete(id, name) {
  selectedAthlete = id; selectedAthleteN = name;
  document.querySelectorAll('.ath-btn').forEach(b=>b.classList.remove('selected'));
  event.currentTarget.classList.add('selected');
  document.getElementById('ath-name-display').textContent = name;
  updateReadyState();
}

function selectDist(d) {
  selectedDist = d;
  document.querySelectorAll('.dist-btn').forEach(b=>b.classList.remove('selected'));
  const btn = document.getElementById('dist-'+d);
  if(btn) btn.classList.add('selected');
  document.getElementById('dist-display').textContent = d+'m';
  updateReadyState();
}

function updateCustomSafeTime(val) {
  if (val && parseInt(val) > 0) {
      safeTimes['custom_override'] = parseInt(val) * 1000;
  } else {
      delete safeTimes['custom_override'];
  }
}

function updateReadyState() {
  const ready = selectedAthlete && selectedDist;
  document.getElementById('btn-start').disabled = !ready;
  document.getElementById('status-line').innerHTML = ready
    ? '<span class="status-dot waiting"></span>Gotowy — kliknij Start'
    : '<span class="status-dot waiting"></span>Wybierz zawodnika i dystans';
}

function filterAthletes(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.ath-btn').forEach(b=>{
    b.style.display = b.dataset.name.includes(q) ? '' : 'none';
  });
}

// MANUAL TIMER
function startManualTimer() {
  timerStart = performance.now();
  timerMs = 0;
  document.getElementById('big-time').className = 'big-time';
  document.getElementById('timer-label').textContent = 'W TRAKCIE BIEGU...';
  document.getElementById('status-line').innerHTML = '<span class="status-dot running"></span>Bieg trwa';
  document.getElementById('btn-start').style.display = 'none';
  document.getElementById('btn-stop').style.display = 'flex';
  
  // Safe time bar
  const st = safeTimes['custom_override'] || safeTimes[selectedDist] || safeTimes['custom'] || 8000;
  document.getElementById('safe-wrap').style.display = 'block';
  document.getElementById('safe-fill').style.width = '0%';
  document.getElementById('safe-fill').className = 'safe-bar-fill';
  
  timerInterval = setInterval(()=>{
    timerMs = performance.now() - timerStart;
    updateBigTime(timerMs);
    // Safe time bar
    const pct = Math.min(100,(timerMs/st)*100);
    document.getElementById('safe-fill').style.width = pct+'%';
    document.getElementById('safe-label').textContent = timerMs < st ? `Safe time: ${((st-timerMs)/1000).toFixed(1)}s pozostało` : '✓ Dozwolone zatrzymanie';
    if(timerMs >= st) document.getElementById('safe-fill').className = 'safe-bar-fill done';
  }, 10);
}

function stopManualTimer() {
  clearInterval(timerInterval);
  document.getElementById('timer-label').textContent = 'WYNIK';
  document.getElementById('big-time').className = 'big-time finished';
  document.getElementById('btn-stop').style.display = 'none';
  document.getElementById('btn-save').style.display = 'flex';
  document.getElementById('btn-reset').style.display = 'flex';
  document.getElementById('status-line').innerHTML = '<span class="status-dot" style="background:var(--green)"></span>Gotowe!';
}

function updateBigTime(ms) {
  const cs = Math.floor(ms/10)%100;
  const sec = Math.floor(ms/1000);
  const min = Math.floor(sec/60);
  const s = sec%60;
  let timeStr;
  if(min > 0) timeStr = `${min}:${String(s).padStart(2,'0')}`;
  else timeStr = String(s);
  document.getElementById('big-time').innerHTML = timeStr+'.<span id="big-cs" class="big-ms">'+String(cs).padStart(2,'0')+'</span>';
}

function applyManualTime() {
  const raw = document.getElementById('manual-time').value.trim();
  let ms = 0;
  const m1 = raw.match(/^(\d+):(\d+)\.(\d{1,2})$/);
  const m2 = raw.match(/^(\d+)\.(\d{1,2})$/);
  if(m1) ms = (parseInt(m1[1])*60+parseInt(m1[2]))*1000 + parseInt(m1[3].padEnd(2,'0'))*10;
  else if(m2) ms = parseInt(m2[1])*1000 + parseInt(m2[2].padEnd(2,'0'))*10;
  if(ms > 0) {
    timerMs = ms;
    clearInterval(timerInterval);
    updateBigTime(ms);
    document.getElementById('big-time').className = 'big-time finished';
    document.getElementById('timer-label').textContent = 'WYNIK (ręczny)';
    document.getElementById('btn-start').style.display = 'none';
    document.getElementById('btn-stop').style.display = 'none';
    document.getElementById('btn-save').style.display = 'flex';
    document.getElementById('btn-reset').style.display = 'flex';
    document.getElementById('status-line').innerHTML = '<span class="status-dot" style="background:var(--green)"></span>Czas wpisany ręcznie';
  }
}

function saveResult() {
  if(!selectedAthlete || timerMs <= 0) return;
  const cs = Math.floor(timerMs/10)%100;
  const sec = Math.floor(timerMs/1000); const min=Math.floor(sec/60); const s=sec%60;
  const fmt = min>0?`${min}:${String(s).padStart(2,'0')}.${String(cs).padStart(2,'0')}`:`${s}.${String(cs).padStart(2,'0')}`;
  document.getElementById('save-summary').textContent = `${selectedAthleteN} — ${selectedDist}m — ${fmt}`;
  document.getElementById('save-modal').classList.add('open');
}

function closeSaveModal() { document.getElementById('save-modal').classList.remove('open'); }

function confirmSave() {
  const notes = document.getElementById('save-notes').value;
  const eid   = document.getElementById('event-select').value;
  fetch('measure.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`action=save_result&athlete_id=${selectedAthlete}&event_id=${eid}&distance_m=${selectedDist}&time_ms=${Math.round(timerMs)}&source=manual&notes=${encodeURIComponent(notes)}`
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.ok) {
      closeSaveModal();
      const f=document.createElement('div'); f.className='saved-flash';
      f.innerHTML='<div style="font-size:2rem;font-weight:800;color:var(--green)">✓ ZAPISANO</div>';
      document.body.appendChild(f); setTimeout(()=>f.remove(),600);
    }
  });
}

function resetMeasure() {
  clearInterval(timerInterval); timerMs=0; timerStart=null;
  document.getElementById('big-time').className='big-time waiting';
  document.getElementById('big-time').innerHTML='--.-<span id="big-cs" class="big-ms">--</span>';
  document.getElementById('timer-label').textContent='Oczekiwanie na start...';
  document.getElementById('btn-start').style.display=''; document.getElementById('btn-stop').style.display='none';
  document.getElementById('btn-save').style.display='none'; document.getElementById('btn-reset').style.display='none';
  document.getElementById('safe-wrap').style.display='none';
  document.getElementById('manual-time').value='';
  document.getElementById('save-notes').value='';
}

// Init
selectDist(100);
updateReadyState();
</script>
<?php include 'includes/footer.php'; ?>
