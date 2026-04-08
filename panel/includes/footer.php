<div class="panel-footer" style="padding-top:40px; border-top:1px solid rgba(255,255,255,0.05)">
  <div style="color:var(--muted); font-size:12px; line-height:1.6; letter-spacing:0.02em; margin-bottom:30px;">
    <div>Twórcy systemu: <a href="https://www.instagram.com/wiktor8446/" target="_blank" style="color:#10b981; text-decoration:none; font-weight:700;">Wiktor Zalewski</a> &amp; <a href="https://www.instagram.com/oskar_kulinskii/" target="_blank" style="color:#10b981; text-decoration:none; font-weight:700;">Oskar Kuliński</a></div>
    <div>Kontakt: <a href="mailto:contact@wikos.run" style="color:#10b981; text-decoration:none; font-weight:700;">contact@wikos.run</a></div>
  </div>

  <?php 
    try {
      $sponsors = $pdo->query("SELECT * FROM sponsors WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll();
    } catch(Exception $e){ $sponsors = []; }
    if(count($sponsors) > 0):
  ?>
  <div style="padding-top:30px; border-top:1px dashed rgba(255,255,255,0.1); max-width:600px; margin:0 auto">
    <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.3em; color:var(--muted); margin-bottom:20px; opacity:0.6">Projekt dofinansowany przez</div>
    <div style="display:flex; flex-wrap:wrap; justify-content:center; gap:25px; align-items:center">
      <?php foreach($sponsors as $s): ?>
        <a href="<?= htmlspecialchars($s['website_url'] ?: '#') ?>" target="_blank" title="<?= htmlspecialchars($s['name']) ?>" style="display:block; transition:all 0.4s cubic-bezier(0.4, 0, 0.2, 1); filter:grayscale(1) brightness(0.8); opacity:0.4;" onmouseover="this.style.filter='none';this.style.opacity='1';this.style.transform='scale(1.05)'" onmouseout="this.style.filter='grayscale(1) brightness(0.8)';this.style.opacity='0.4';this.style.transform='scale(1)'">
          <img src="https://panel.lo48.pl/uploads/sponsors/<?= $s['logo_path'] ?>" alt="<?= htmlspecialchars($s['name']) ?>" style="height:30px; max-width:130px; object-fit:contain">
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
</div><!-- .main -->
</div><!-- .layout -->
<script>
(function(){
  var hb=document.getElementById('hamburger'),
      sb=document.querySelector('.sidebar'),
      ov=document.getElementById('overlay');
  if(hb&&sb){
    hb.addEventListener('click',function(){sb.classList.toggle('open');ov.classList.toggle('open');});
  }
  window.closeSidebar=function(){sb.classList.remove('open');ov.classList.remove('open');};
  // close sidebar on link click (mobile)
  document.querySelectorAll('.sb-link').forEach(function(l){l.addEventListener('click',function(){if(window.innerWidth<=768)closeSidebar();});});
})();
</script>
</body>
</html>
