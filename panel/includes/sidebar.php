<?php
// sidebar.php — wymaga: $uid, $uname, $current_page
$initials = strtoupper(mb_substr($uname, 0, 1));
$nav = [
    'dashboard' => ['icon'=>'fa-gauge',          'label'=>'Dashboard',      'href'=>'dashboard.php'],
    'athletes'  => ['icon'=>'fa-person-running',  'label'=>'Zawodnicy',      'href'=>'athletes.php'],
    'measure'   => ['icon'=>'fa-stopwatch',       'label'=>'Pomiar',         'href'=>'measure.php'],
    'events'    => ['icon'=>'fa-calendar-days',   'label'=>'Sesje',          'href'=>'events.php'],
    'devices'   => ['icon'=>'fa-satellite-dish',  'label'=>'Pachołki',       'href'=>'devices.php'],
    'settings'  => ['icon'=>'fa-sliders',         'label'=>'Ustawienia',     'href'=>'settings.php'],
];
?>
<aside class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-text">WIK<span>OS</span>.RUN</div>
    <div class="sb-badge">Timing Panel v1</div>
  </div>
  <nav class="sb-nav">
    <div class="sb-label">Nawigacja</div>
    <?php foreach($nav as $key => $item): ?>
    <a href="<?= $item['href'] ?>" class="sb-link <?= ($current_page??'')===$key?'active':'' ?>">
      <i class="fa-solid <?= $item['icon'] ?>"></i>
      <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
  </nav>
  <div class="sb-bottom">
    <div class="sb-user">
      <div class="sb-av"><?= $initials ?></div>
      <div>
        <div class="sb-uname"><?= htmlspecialchars($uname) ?></div>
        <div class="sb-urole">Trener / Operator</div>
      </div>
    </div>
    <a href="logout.php" class="sb-logout"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj</a>
  </div>
</aside>
<div class="main">
