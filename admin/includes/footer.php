<div style="margin-top:50px; padding-top:30px; border-top:1px solid var(--border); text-align:center; font-family:'Space Grotesk', sans-serif;">
  <div style="color:var(--muted); font-size:12px; line-height:1.6; letter-spacing:0.02em;">
    <div>Twórcy systemu: <a href="https://www.instagram.com/wiktor8446/" target="_blank" style="color:#10b981; text-decoration:none; font-weight:700;">Wiktor Zalewski</a> &amp; <a href="https://www.instagram.com/oskar_kulinskii/" target="_blank" style="color:#10b981; text-decoration:none; font-weight:700;">Oskar Kuliński</a></div>
    <div>Twórca strony: <a href="https://github.com/wiktorzalewski" target="_blank" style="color:#10b981; text-decoration:none; font-weight:700;">Wiktor Zalewski</a></div>
    <div>Kontakt w sprawie błędów: <a href="mailto:wiktorzalewski50@gmail.com" style="color:#10b981; text-decoration:none; font-weight:700;">wiktorzalewski50@gmail.com</a></div>
  </div>
</div>
</div><!-- main -->
</div><!-- layout -->
<script>
// Hamburger toggle
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const sidebar = document.querySelector('.sidebar');
    if(hamburger && sidebar) {
        hamburger.addEventListener('click', function() {
            sidebar.style.transform = sidebar.style.transform === 'translateX(0%)' ? 'translateX(-100%)' : 'translateX(0%)';
        });
    }
});
</script>
</body>
</html>
