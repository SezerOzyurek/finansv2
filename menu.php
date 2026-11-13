<?php
$current = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
$currentQuery = $_SERVER['QUERY_STRING'] ?? '';

function isActive($file, $queryMatch = null): bool
{
    global $current, $currentQuery;
    if ($current !== $file) { return false; }
    if ($queryMatch === null) { return true; }
    return strpos($currentQuery, $queryMatch) !== false;
}

function navLink(string $href, string $icon, string $label, bool $active = false): string
{
    $base = "group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-medium transition-all duration-150";
    $activeCls = "bg-slate-900 text-white font-semibold shadow-xs dark:bg-slate-800 dark:text-white";
    $inactiveCls = "text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-900 dark:hover:text-slate-200";
    
    $cls = $active ? $base . " " . $activeCls : $base . " " . $inactiveCls;
    $iconColor = $active ? "text-emerald-400" : "text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300";

    return '<a class="' . $cls . '" href="' . $href . '"><i class="' . $icon . ' ' . $iconColor . ' text-base transition-colors"></i><span class="truncate">' . $label . '</span></a>';
}

function sessionRemainingSeconds(): ?int
{
    $token = $_SESSION['Api_Token'] ?? '';
    if (!is_string($token) || $token === '') { return null; }

    $parts = explode('.', $token);
    if (count($parts) !== 3) { return null; }

    $payload = strtr($parts[1], '-_', '+/');
    $pad = strlen($payload) % 4;
    if ($pad > 0) { $payload .= str_repeat('=', 4 - $pad); }

    $decoded = base64_decode($payload, true);
    if (!is_string($decoded)) { return null; }

    $data = json_decode($decoded, true);
    if (!is_array($data) || !isset($data['exp'])) { return null; }

    return max(0, (int)$data['exp'] - time());
}

$remainingSeconds = sessionRemainingSeconds();
?>

<!-- Mobil Arka Plan Perdesi -->
<div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-slate-950/50 backdrop-blur-xs transition-opacity lg:hidden"></div>

<!-- Kenar Menüsü (Sidebar) -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 -translate-x-full overflow-y-auto overscroll-contain border-r border-slate-200/80 bg-white/95 text-slate-900 shadow-xl transition-transform lg:translate-x-0 dark:border-slate-800/80 dark:bg-slate-950/95 dark:text-slate-100" style="-webkit-overflow-scrolling: touch;">
    
    <!-- Logo Alanı -->
    <div class="flex h-16 items-center justify-between px-5 border-b border-slate-100 dark:border-slate-900/80">
        <a href="index.php" class="flex items-center gap-2.5">
            <div class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-500 text-white shadow-xs font-bold">
                <i class="ti ti-coins text-lg"></i>
            </div>
            <div class="leading-none">
                <div class="text-sm font-extrabold tracking-tight text-slate-900 dark:text-white">Ultra <span class="text-emerald-600 dark:text-emerald-400">Finans</span></div>
                <div class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 tracking-wide mt-0.5">KİŞİSEL PORTFÖY</div>
            </div>
        </a>
        <button type="button" class="lg:hidden rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-900" data-sidebar-close aria-label="Menüyü kapat">
            <i class="ti ti-x text-base"></i>
        </button>
    </div>

    <!-- Menü Öğeleri -->
    <nav class="flex min-h-[calc(100vh-4rem)] flex-col justify-between px-3 py-4">
        <div class="space-y-6">
            
            <!-- Ana Menü -->
            <div>
                <?= navLink('index.php', 'ti ti-layout-dashboard', 'Genel Bakış', isActive('index.php')) ?>
            </div>

            <!-- İşlemler & Hareketler -->
            <div>
                <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">İşlemler</div>
                <div class="space-y-1">
                    <?= navLink('hareket.php', 'ti ti-plus', 'Yeni Hareket Ekle', isActive('hareket.php')) ?>
                    <?= navLink('hareketler.php?type=1', 'ti ti-arrow-down-left', 'Gelirler', isActive('hareketler.php', 'type=1')) ?>
                    <?= navLink('hareketler.php?type=2', 'ti ti-arrow-up-right', 'Giderler', isActive('hareketler.php', 'type=2')) ?>
                </div>
            </div>

            <!-- Varlıklar & Hesaplar -->
            <div>
                <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Cüzdan & Kategoriler</div>
                <div class="space-y-1">
                    <?= navLink('hesaplar.php', 'ti ti-credit-card', 'Hesaplar / Kartlar', isActive('hesaplar.php')) ?>
                    <?= navLink('kategoriler.php?type=1', 'ti ti-folder-plus', 'Gelir Kategorileri', isActive('kategoriler.php', 'type=1')) ?>
                    <?= navLink('kategoriler.php?type=2', 'ti ti-folder-minus', 'Gider Kategorileri', isActive('kategoriler.php', 'type=2')) ?>
                </div>
            </div>

            <!-- Analiz & Raporlar -->
            <div>
                <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Analiz & Raporlar</div>
                <div class="space-y-1">
                    <?= navLink('rapor.php?type=1', 'ti ti-chart-line', 'Gelir Raporları', isActive('rapor.php', 'type=1')) ?>
                    <?= navLink('rapor.php?type=2', 'ti ti-chart-pie', 'Gider Raporları', isActive('rapor.php', 'type=2') || (isActive('rapor.php') && empty($_GET['type']))) ?>
                    <?= navLink('analiz.php', 'ti ti-wave-sine', 'Finans Nabzı', isActive('analiz.php')) ?>
                    <?= navLink('para_davranislari.php', 'ti ti-activity', 'Para Davranışları', isActive('para_davranislari.php')) ?>
                    <?= navLink('enler.php', 'ti ti-sparkles', "En'ler ve Şaşırtanlar", isActive('enler.php')) ?>
                </div>
            </div>

            <!-- Ayarlar -->
            <div>
                <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Sistem</div>
                <div class="space-y-1">
                    <?= navLink('profil.php', 'ti ti-user-cog', 'Hesap Ayarları', isActive('profil.php')) ?>
                    <?= navLink('telegram_ayar.php', 'ti ti-brand-telegram', 'Telegram Botu', isActive('telegram_ayar.php')) ?>
                </div>
            </div>
        </div>

        <!-- Alt Kısım: Oturum & Çıkış -->
        <div class="pt-4 border-t border-slate-100 dark:border-slate-900/80 space-y-3">
            <div class="rounded-xl border border-amber-200/60 bg-amber-50/60 p-2.5 text-[11px] leading-tight text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300">
                <div class="flex items-center gap-1.5 font-bold mb-1">
                    <i class="ti ti-hourglass-low text-xs"></i>
                    <span>Kalan Oturum</span>
                </div>
                <div class="text-[10px] opacity-80">
                    Süre: <span id="sessionCountdown" class="font-bold tabular-nums" data-remaining="<?= is_int($remainingSeconds) ? $remainingSeconds : ''; ?>"><?= is_int($remainingSeconds) ? $remainingSeconds . ' sn' : 'Aktif'; ?></span>
                </div>
            </div>

            <a href="logout.php" class="flex items-center justify-between rounded-xl px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30 transition-colors">
                <span class="flex items-center gap-2"><i class="ti ti-logout text-sm"></i>Çıkış Yap</span>
                <i class="ti ti-chevron-right text-xs opacity-60"></i>
            </a>
        </div>
    </nav>
</aside>

<script>
(function () {
    var el = document.getElementById("sessionCountdown");
    if (!el) { return; }

    var remaining = parseInt(el.getAttribute("data-remaining"), 10);
    if (isNaN(remaining)) { return; }
    var redirected = false;
    var timerId = null;

    function tick() {
        if (redirected) { return; }
        if (remaining <= 0) {
            el.textContent = "0 sn";
            redirected = true;
            if (timerId) { clearInterval(timerId); }
            window.location.replace("logout.php");
            return;
        }
        var mins = Math.floor(remaining / 60);
        var secs = remaining % 60;
        el.textContent = (mins > 0 ? mins + " dk " : "") + secs + " sn";
        remaining -= 1;
    }

    tick();
    timerId = setInterval(tick, 1000);
})();
</script>
