<?php
$userFullName = trim(($_SESSION['FirstName'] ?? '') . ' ' . ($_SESSION['LastName'] ?? ''));
if ($userFullName === '') { $userFullName = 'Kullanıcı'; }
$initials = mb_substr($_SESSION['FirstName'] ?? 'K', 0, 1) . mb_substr($_SESSION['LastName'] ?? 'U', 0, 1);
?>
<header class="sticky top-0 inset-x-0 z-30 border-b border-slate-200/80 bg-white/80 backdrop-blur-md transition-colors dark:border-slate-800/80 dark:bg-slate-950/80">
    <div class="flex h-16 w-full items-center justify-between gap-3 px-4 lg:px-8">
        
        <!-- Sol: Mobil Menü Açma & Başlık -->
        <div class="flex items-center gap-3">
            <button type="button" class="lg:hidden inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2.5 text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800" data-sidebar-open aria-label="Menü">
                <i class="ti ti-menu-2 text-lg"></i>
            </button>
            <div class="hidden sm:block">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Ultra Finans</span>
            </div>
        </div>

        <!-- Sağ Araçlar: Tema, Rakam Gizleme, Profil -->
        <div class="flex items-center gap-2.5">
            
            <!-- Tema Değiştirici (Dark / Light) -->
            <button type="button" id="themeToggleBtn" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-xs transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white" title="Tema Değiştir (Açık / Koyu)">
                <i class="ti ti-moon text-base dark:hidden"></i>
                <i class="ti ti-sun text-base hidden dark:block text-amber-400"></i>
            </button>

            <!-- Rakamları Gizle / Göster -->
            <a href="#" class="gizle inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-xs transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800" data-gizletur="rakamlar" title="Rakamları gizle/göster">
                <?php if (rakamlarGizli()) { ?>
                    <i class="ti ti-eye-off text-base text-amber-500"></i>
                <?php } else { ?>
                    <i class="ti ti-eye text-base text-emerald-600 dark:text-emerald-400"></i>
                <?php } ?>
            </a>

            <!-- Kullanıcı Menüsü -->
            <details class="relative">
                <summary class="list-none inline-flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 bg-white py-1.5 pl-2 pr-3 text-sm text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-emerald-500/10 font-bold text-xs text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                        <?= htmlspecialchars(mb_strtoupper($initials), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="hidden md:inline font-semibold text-xs text-slate-800 dark:text-slate-200"><?= htmlspecialchars($userFullName, ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="ti ti-chevron-down text-xs text-slate-400 dark:text-slate-500"></i>
                </summary>
                <div class="absolute right-0 mt-2 w-52 rounded-2xl border border-slate-200/90 bg-white p-1.5 shadow-xl transition-all dark:border-slate-800 dark:bg-slate-900">
                    <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800/80 mb-1">
                        <div class="text-xs font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($userFullName, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-[11px] text-slate-500 truncate dark:text-slate-400"><?= htmlspecialchars($_SESSION['Email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <a href="profil.php" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                        <i class="ti ti-user-cog text-slate-400"></i>
                        <span>Hesap & Şifre</span>
                    </a>
                    <a href="telegram_ayar.php" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                        <i class="ti ti-brand-telegram text-slate-400"></i>
                        <span>Telegram Bot</span>
                    </a>
                    <div class="my-1 border-t border-slate-100 dark:border-slate-800/80"></div>
                    <a href="logout.php" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40">
                        <i class="ti ti-logout"></i>
                        <span>Çıkış Yap</span>
                    </a>
                </div>
            </details>
        </div>
    </div>
</header>
