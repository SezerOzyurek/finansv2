<?php
$current = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
function isActive($file): bool
{
    global $current;
    return $current === $file;
}
function navLink(string $href, string $icon, string $label, bool $active = false): string
{
    $base = "group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition";
    $cls = $active
        ? $base." bg-white/10 text-white"
        : $base." text-slate-200 hover:bg-white/10 hover:text-white";
    return '<a class="'.$cls.'" href="'.$href.'"><i class="'.$icon.' text-[15px] opacity-90 group-hover:opacity-100"></i><span class="truncate">'.$label.'</span></a>';
}
?>

<div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-slate-950/60 backdrop-blur-sm lg:hidden"></div>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 -translate-x-full overflow-y-auto overscroll-contain border-r border-white/10 bg-gradient-to-b from-slate-950 to-slate-900 text-white shadow-2xl transition-transform lg:translate-x-0" style="-webkit-overflow-scrolling: touch;">
    <div class="flex h-16 items-center justify-between px-4">
        <a href="index.php" class="flex items-center gap-3">
            <div class="grid h-10 w-10 place-items-center rounded-2xl bg-white/10">
                <i class="ti ti-coins"></i>
            </div>
            <div class="leading-tight">
                <div class="text-sm font-semibold tracking-wide">Ultra</div>
                <div class="text-xs text-slate-300">Finans</div>
            </div>
        </a>
        <button type="button" class="lg:hidden rounded-xl p-2 hover:bg-white/10" data-sidebar-close aria-label="Menü kapat">
            <i class="ti ti-x"></i>
        </button>
    </div>

    <nav class="px-3 pb-6">
        <div class="mt-2">
            <?= navLink('index.php', 'ti ti-home', 'Anasayfa', isActive('index.php')) ?>
        </div>

        <div class="mt-6 px-3 text-[11px] font-semibold uppercase tracking-widest text-slate-400">İşlemler</div>
        <div class="mt-2 space-y-1">
            <?= navLink('hareket.php', 'ti ti-plus', 'Yeni Hareket', isActive('hareket.php')) ?>
            <?= navLink('hareketler.php?type=1', 'ti ti-wallet', 'Gelirler', isActive('hareketler.php')) ?>
            <?= navLink('hareketler.php?type=2', 'ti ti-cash-banknote', 'Giderler', isActive('hareketler.php')) ?>
        </div>

        <div class="mt-6 px-3 text-[11px] font-semibold uppercase tracking-widest text-slate-400">Kategoriler</div>
        <div class="mt-2 space-y-1">
            <?= navLink('kategoriler.php?type=1', 'ti ti-folder-plus', 'Gelir Kategorileri', isActive('kategoriler.php')) ?>
            <?= navLink('kategoriler.php?type=2', 'ti ti-folder-minus', 'Gider Kategorileri', isActive('kategoriler.php')) ?>
        </div>

        <div class="mt-6 px-3 text-[11px] font-semibold uppercase tracking-widest text-slate-400">Raporlar</div>
        <div class="mt-2 space-y-1">
            <?= navLink('yasam_ozeti.php', 'ti ti-receipt', 'Yaşam Özeti', isActive('yasam_ozeti.php')) ?>
            <?= navLink('para_davranislari.php', 'ti ti-activity', 'Para Davranışları', isActive('para_davranislari.php')) ?>
            <?= navLink('enler.php', 'ti ti-sparkles', 'En\'ler ve Şaşırtanlar', isActive('enler.php')) ?>
            <?= navLink('rapor.php?type=1', 'ti ti-chart-line', 'Gelir Raporları', isActive('rapor.php')) ?>
            <?= navLink('rapor.php?type=2', 'ti ti-chart-pie', 'Gider Raporları', isActive('rapor.php')) ?>
            <?= navLink('analiz.php', 'ti ti-wave-sine', 'Finans Nabzı', isActive('analiz.php')) ?>
        </div>

        <div class="mt-6">
            <div class="h-px bg-white/10"></div>
            <div class="mt-4 px-2">
                <a href="logout.php" class="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm font-medium text-slate-100 hover:bg-white/10">
                    <span class="flex items-center gap-3"><i class="ti ti-logout opacity-90"></i>Çıkış Yap</span>
                    <i class="ti ti-chevron-right text-slate-300"></i>
                </a>
            </div>
        </div>
    </nav>
</aside>
