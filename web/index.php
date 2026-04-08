<?php
/**
 * WikOS.run - Official Landing Page
 * Core Module: Web Gateway
 */

// Database connection initialization
// require_once __DIR__ . '/../admin/db.php'; 

/* 
// For GitHub repository demonstration:
$stmt = $pdo->query("SELECT * FROM site_content");
$content = [];
while($row = $stmt->fetch()) { 
    $content[$row['section_id']] = $row; 
}
*/

function showText($text) {
    return nl2br(htmlspecialchars($text ?? ''));
}
?>
<!DOCTYPE html>
<html lang="pl" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= showText($content['navbar']['section_title'] ?? 'WikOS') ?> | Timing System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes gradient-x {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        .animate-gradient-x {
            background-size: 200% 200%;
            animation: gradient-x 3s ease infinite;
        }
    </style>
</head>
<body class="bg-[#030303] text-white font-sans antialiased overflow-x-hidden">

    <nav class="fixed w-full z-50 top-0 bg-[#030303]/80 backdrop-blur-xl border-b border-white/5">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="text-xl font-black italic uppercase tracking-tighter">
                <span class="text-white">WIK</span><span class="text-emerald-500">OS</span><span class="text-white">.RUN</span>
            </div>
            <div class="flex items-center gap-4 sm:gap-6 text-[10px] sm:text-xs font-bold uppercase tracking-widest text-slate-400">
                <span class="hidden lg:inline"><?= showText($content['navbar']['description'] ?? '') ?></span>
                <a href="/results/" class="hover:text-white transition whitespace-nowrap">Wyniki Live</a>
                <a href="/panel/" class="bg-emerald-500/20 text-emerald-500 hover:bg-emerald-500 hover:text-white transition px-4 py-2 rounded-full whitespace-nowrap"><i class="fa-solid fa-lock mr-2"></i> Zaloguj</a>
            </div>
        </div>
    </nav>

    <section class="relative min-h-screen flex items-center justify-center px-6 pt-20">
        <div class="relative z-10 text-center max-w-4xl mx-auto">
            <h1 class="text-5xl md:text-8xl font-black italic uppercase tracking-tighter mb-8 leading-none drop-shadow-2xl">
                <?= showText($content['hero']['section_title'] ?? '') ?>
            </h1>
            <p class="text-lg md:text-2xl text-slate-400 font-light max-w-2xl mx-auto leading-relaxed mb-8">
                <?= showText($content['hero']['description'] ?? '') ?>
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
               <a href="mailto:contact@wikos.run" class="bg-gradient-to-r from-emerald-500 to-emerald-400 text-black font-black uppercase tracking-widest text-sm px-8 py-4 rounded-full hover:scale-105 transition-transform"><i class="fa-solid fa-envelope mr-2"></i> Złóż zamówienie / Wycena</a>
               <a href="#ecosystem" class="text-slate-400 hover:text-white text-sm uppercase tracking-widest font-bold px-8 py-4">Dowiedz się więcej</a>
            </div>
        </div>
    </section>

    <section class="py-24 px-6 bg-white/[0.02] border-y border-white/5 relative">
        <div class="max-w-7xl mx-auto text-center">
            <h2 class="text-xs font-bold uppercase tracking-[0.3em] text-emerald-500 mb-4">Core</h2>
            <h3 class="text-4xl md:text-5xl font-black italic uppercase tracking-tight mb-8">
                <?= showText($content['ecosystem']['section_title'] ?? '') ?>
            </h3>
            <p class="text-slate-400 max-w-3xl mx-auto text-lg leading-relaxed mb-12">
                <?= showText($content['ecosystem']['description'] ?? '') ?>
            </p>
        </div>
    </section>

    <footer class="py-20 px-6 border-t border-white/5 bg-black text-center relative overflow-hidden">
        <div class="relative z-10 space-y-8">
            <div style="color:#64748b; font-size:12px; line-height:1.8; letter-spacing:0.02em;">
                <div>Twórcy systemu: <a href="https://www.instagram.com/wiktor8446/" target="_blank" style="color:#10b981; text-decoration:none; font-weight:700;">Wiktor Zalewski</a> &amp; <a href="https://www.instagram.com/oskar_kulinskii/" target="_blank" style="color:#10b981; text-decoration:none; font-weight:700;">Oskar Kuliński</a></div>
                <div>Kontakt w sprawie błędów: <a href="mailto:contact@wikos.run" style="color:#10b981; text-decoration:none; font-weight:700;">contact@wikos.run</a></div>
                <div class="mt-4 opacity-50 uppercase tracking-[0.2em] text-[10px]">System WikOS.run &copy; <?= date('Y') ?></div>
            </div>
        </div>
    </footer>

</body>
</html>
