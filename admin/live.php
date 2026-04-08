<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

$devices = $pdo->query("
    SELECT d.id, d.name, d.reg_code, d.last_seen, d.last_ip, acc.name as trainer_name,
    TIMESTAMPDIFF(SECOND, d.last_seen, NOW()) as offline_sec
    FROM devices d
    LEFT JOIN accounts acc ON d.account_id = acc.id
    WHERE d.status != 'pending'
    ORDER BY d.last_seen DESC
")->fetchAll();

// Handle AJAX refresh
if(isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode($devices);
    exit;
}

include 'includes/header.php';
?>
<div class="ph">
  <div class="ph-title">Radar Na Żywo (Live Monitor)</div>
  <div class="ph-sub">Śledzenie połączeń API Pachołków w czasie rzeczywistym</div>
</div>

<div class="card" style="border:1px solid var(--green-glow);box-shadow:0 0 30px rgba(16,185,129,0.1)">
  <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);padding-bottom:16px;margin-bottom:16px">
    <div style="font-weight:800;color:var(--green);display:flex;align-items:center;gap:12px">
      <div style="width:12px;height:12px;background:var(--green);border-radius:50%;box-shadow:0 0 10px var(--green);animation:pulse 1s infinite"></div>
      OSTATNIE PINGI SYSTEMOWE
    </div>
    <div style="font-family:'Fira Code',monospace;font-size:10px;color:var(--muted)">Auto-odświeżanie: 3s</div>
  </div>
  
  <div class="tbl-wrap" style="overflow-x:auto">
    <table id="radar-table">
      <thead>
        <tr><th>ID</th><th>Nazwa Pachołka</th><th>Trener</th><th>Status</th><th>Ostatni kontakt (Sekundy temu)</th><th>IP Zewnętrzne</th></tr>
      </thead>
      <tbody id="radar-body">
        <!-- Renderowane przez JS -->
      </tbody>
    </table>
  </div>
</div>

<style>
@keyframes pulse { 0% { transform: scale(0.9); opacity: 1; } 100% { transform: scale(1.5); opacity: 0; } }
</style>

<script>
function fetchRadar() {
    fetch('live.php?ajax=1')
    .then(r => r.json())
    .then(data => {
        const tbody = document.getElementById('radar-body');
        tbody.innerHTML = '';
        if(data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted)">Brak zarejestrowanych urządzeń.</td></tr>';
            return;
        }
        
        data.forEach(d => {
            let s = parseInt(d.offline_sec);
            let statusBadge = '<span class="badge" style="background:rgba(255,255,255,.05);color:var(--muted)">OFFLINE</span>';
            if(s !== null && !isNaN(s)) {
                if(s <= 5) statusBadge = '<span class="badge" style="background:rgba(16,185,129,.15);color:var(--green)">ONLINE / ACTIVE</span>';
                else if(s <= 15) statusBadge = '<span class="badge" style="background:rgba(245,158,11,.15);color:var(--warn)">LAG / STANDBY</span>';
            }
            
            let row = `
                <tr>
                    <td style="color:var(--muted)">#${d.id}</td>
                    <td style="font-weight:700">${d.name} <span style="color:var(--primary);font-size:.7rem;margin-left:4px">[${d.reg_code}]</span></td>
                    <td style="color:var(--muted)">${d.trainer_name || 'Brak'}</td>
                    <td>${statusBadge}</td>
                    <td style="font-family:'Fira Code',font-size:.8rem;color:${s<=5?'var(--green)':'var(--muted)'}">${isNaN(s) ? 'Nigdy' : s + ' s'}</td>
                    <td style="font-family:'Fira Code',font-size:.8rem">${d.last_ip || '---'}</td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    });
}
setInterval(fetchRadar, 3000);
fetchRadar();
</script>
<?php include 'includes/footer.php'; ?>
