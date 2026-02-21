<header class="sticky top-0 inset-x-0 z-30 border-b border-slate-200/70 bg-white/80 backdrop-blur">
    <div class="flex h-16 w-full items-center gap-3 px-4 lg:px-8">
        <button type="button" class="lg:hidden inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 shadow-sm hover:bg-slate-50" data-sidebar-open aria-label="Menü">
            <i class="ti ti-menu-2"></i>
        </button>

        <div class="flex-1"></div>

        <div class="flex items-center gap-2">
            <a href="#" class="gizle inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm hover:bg-slate-50" data-gizletur="rakamlar" title="Rakamlari gizle/goster">
                <?php if (rakamlarGizli()) { ?>
                    <i class="ti ti-eye-off text-amber-600"></i>
                <?php } else { ?>
                    <i class="ti ti-eye text-emerald-700"></i>
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
