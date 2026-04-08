<?php
session_start(); require_once 'db.php';
if(!isset($_SESSION['admin_email'])) { header('Location: index.php'); exit; }

function getRam() {
    $r = @file_get_contents('/proc/meminfo');
    if(!$r) return ['used' => 0, 'total' => 0, 'pct' => 0];
    preg_match('/MemTotal:\s+(\d+) kB/', $r, $m1);
    preg_match('/MemAvailable:\s+(\d+) kB/', $r, $m2);
    $tot = intval($m1[1]??0);
    $av  = intval($m2[1]??0);
    $us  = $tot - $av;
    return [ 'used' => round($us/1024), 'total' => round($tot/1024), 'pct' => $tot>0 ? round(($us/$tot)*100) : 0 ];
}

function getTemp() {
    $r = @file_get_contents('/sys/class/thermal/thermal_zone0/temp');
    return $r ? round(intval($r)/1000, 1) : null;
}

function getDisk() {
    $df = disk_free_space("/");
    $dt = disk_total_space("/");
    $us = $dt - $df;
    return [ 'free' => round($df/(1024*1024*1024),1), 'total' => round($dt/(1024*1024*1024),1), 'pct'=> $dt>0 ? round(($us/$dt)*100) : 0 ];
}

$ram = getRam();
$temp = getTemp();
$disk = getDisk();
$load = sys_getloadavg();

include 'includes/header.php';
?>
<div class="ph">
  <div class="ph-title">Zdrowie Serwera (Health)</div>
  <div class="ph-sub">Diagnostyka główna środowiska Nginx / Raspberry Pi</div>
</div>

<div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px">
  
  <div class="card" style="text-align:center">
    <div style="font-size:10px;text-transform:uppercase;letter-spacing:.2em;color:var(--muted);margin-bottom:10px">RAM Usage</div>
    <div style="font-size:3rem;font-weight:800;font-family:'Fira Code',monospace;color:<?= $ram['pct']>80?'var(--danger)':'var(--green)' ?>"><?= $ram['pct'] ?>%</div>
    <div style="font-size:.8rem;color:var(--muted)"><?= $ram['used'] ?> MB / <?= $ram['total'] ?> MB</div>
  </div>

  <div class="card" style="text-align:center">
    <div style="font-size:10px;text-transform:uppercase;letter-spacing:.2em;color:var(--muted);margin-bottom:10px">CPU Temp (Soc)</div>
    <div style="font-size:3rem;font-weight:800;font-family:'Fira Code',monospace;color:<?= $temp>75?'var(--danger)':'var(--warn)' ?>">
      <?= $temp !== null ? $temp.'°C' : 'N/A' ?>
    </div>
    <div style="font-size:.8rem;color:var(--muted)">Raspberry Pi BCM</div>
  </div>

  <div class="card" style="text-align:center">
    <div style="font-size:10px;text-transform:uppercase;letter-spacing:.2em;color:var(--muted);margin-bottom:10px">Dysk (microSD)</div>
    <div style="font-size:3rem;font-weight:800;font-family:'Fira Code',monospace;color:<?= $disk['pct']>85?'var(--danger)':'var(--primary)' ?>"><?= $disk['pct'] ?>%</div>
    <div style="font-size:.8rem;color:var(--muted)">Wolne: <?= $disk['free'] ?> GB / <?= $disk['total'] ?> GB</div>
  </div>

  <div class="card" style="text-align:center">
    <div style="font-size:10px;text-transform:uppercase;letter-spacing:.2em;color:var(--muted);margin-bottom:10px">CPU Load (1m, 5m, 15m)</div>
    <div style="font-size:2rem;font-weight:800;font-family:'Fira Code',monospace;color:var(--text);margin-top:20px"><?= is_array($load)? implode(' — ', $load) : 'N/A' ?></div>
  </div>

</div>

<div class="card">
  <div class="card-hd">SQL Dump (1-Click Pełny Backup Bazy Danych)</div>
  <p style="font-size:.85rem;color:var(--muted);margin-bottom:16px">Narzędzie wykorzystuje podproces serwera do natychmiastowego eksportu całej zawartości systemu (konta, pliki systemowe, sprzęt, logi, pomiary, kody API). Plik otworzysz w każdym Excelu lub bazie MySQL.</p>
  <button class="btn btn-g" onclick="alert('Moduł instalatora eksportów (mysqldump z uprawnieniami exec) dostępny od systemu RPi-Live-Mode. Zainstaluj zip na RPi aby aktywować.')"><i class="fa-solid fa-download"></i> Pobierz Pełny Zrzut .SQL</button>
</div>
<?php include 'includes/footer.php'; ?>
