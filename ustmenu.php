<header class="sticky top-0 inset-x-0 z-30 border-b border-slate-200/70 bg-white/80 backdrop-blur">
    <div class="flex h-16 w-full items-center gap-3 px-4 lg:px-8">
        <button type="button" class="lg:hidden inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 shadow-sm hover:bg-slate-50" data-sidebar-open aria-label="Menü">
            <i class="ti ti-menu-2"></i>
        </button>

        <div class="flex-1"></div>

        <div class="flex items-center gap-2">
            <details class="relative">
                <summary class="list-none inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="ti ti-calendar"></i>
                    <span class="hidden sm:inline">Tarih</span>
                    <i class="ti ti-chevron-down text-slate-400"></i>
                </summary>
                <div class="absolute right-0 mt-2 w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white p-4 shadow-xl sm:w-[320px]">
                    <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Tarih Filtresi</div>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[11px] font-medium text-slate-600">Başlangıç</label>
                            <input type="date" id="baslangicTarihi" value="<?php echo $_SESSION['filtreBaslangic']; ?>" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-900 outline-none ring-0 focus:border-slate-300 focus:bg-white">
                        </div>
                        <div>
                            <label class="text-[11px] font-medium text-slate-600">Bitiş</label>
                            <input type="date" id="bitisTarihi" value="<?php echo $_SESSION['filtreBitis']; ?>" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-900 outline-none ring-0 focus:border-slate-300 focus:bg-white">
                        </div>
                    </div>
                    <button type="button" id="filtreUygula" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-slate-800">
                        <i class="ti ti-filter"></i>Filtrele
                    </button>
                </div>
            </details>

            <a href="#" class="gizle inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm hover:bg-slate-50" data-gizletur="rakamlar" title="Rakamları gizle/göster">
                <?php if (rakamlarGizli()) { ?>
                    <i class="ti ti-eye-off text-amber-600"></i>
                <?php } else { ?>
                    <i class="ti ti-eye text-emerald-700"></i>
                <?php } ?>
            </a>

            <a href="#" class="gizle inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm hover:bg-slate-50" data-gizletur="grafikler" title="Grafikleri gizle/göster">
                <?php if (grafiklerGizli()) { ?>
                    <i class="ti ti-chart-pie-off text-amber-600"></i>
                <?php } else { ?>
                    <i class="ti ti-chart-pie text-emerald-700"></i>
                <?php } ?>
            </a>

            <details class="relative">
                <summary class="list-none inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
                    <span class="hidden md:inline"><?php echo $_SESSION['FirstName']." ".$_SESSION['LastName']; ?></span>
                    <i class="ti ti-user-circle"></i>
                    <i class="ti ti-chevron-down text-slate-400"></i>
                </summary>
                <div class="absolute right-0 mt-2 w-48 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                    <a href="logout.php" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        <i class="ti ti-logout text-slate-400"></i>
                        <span>Çıkış Yap</span>
                    </a>
                </div>
            </details>
        </div>
    </div>
</header>
