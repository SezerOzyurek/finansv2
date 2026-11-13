<?php
include("fonksiyonlar.php");

$stats = apiRequest('/istatistikler', 'GET', [], $_SESSION['Api_Token']);
if (($stats['code'] ?? 500) !== 200) {
    $stats = ["code" => 500, "data" => []];
}

$data = $stats['data'] ?? [];

$maxIncome = $data['max_income'] ?? null;
$maxExpense = $data['max_expense'] ?? null;
$blackDay = $data['black_day'] ?? null;
$zenDay = $data['zen_day'] ?? null;
$topIncomeCats = $data['top_income_categories'] ?? [];
$topExpenseCats = $data['top_expense_categories'] ?? [];
$silentEnemy = $data['silent_enemy'] ?? null;
$monthly = $data['monthly'] ?? [];

$bestMonth = null;
if (is_array($monthly) && count($monthly) > 0) {
    foreach ($monthly as $m) {
        $inc = (float)($m['income'] ?? 0);
        $exp = (float)($m['expense'] ?? 0);
        $mNet = $inc - $exp;
        if ($bestMonth === null || $mNet > (float)($bestMonth['net'] ?? -INF)) {
            $bestMonth = ["ym" => (string)($m['ym'] ?? ''), "net" => $mNet];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title>En'ler ve Şaşırtanlar - Ultra Finans</title>
    <?php include("head.php"); ?>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100" id="top">
<div class="min-h-screen lg:pl-64 flex flex-col">
    <?php include("menu.php"); ?>
    <?php include("ustmenu.php"); ?>

    <main class="w-full flex-1 px-3 py-4 lg:px-6 space-y-4">

        <!-- Başlık -->
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="ti ti-sparkles text-amber-500 dark:text-amber-400"></i>
                <span>En'ler ve Şaşırtanlar</span>
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                Finans geçmişinizdeki rekor harcamalar, en yüksek gelirler ve sıra dışı istatistikler. Tutarların üzerine gelerek asgari ücret ve enflasyon karşılıklarını inceleyebilirsiniz.
            </p>
        </div>

        <!-- 2 Büyük Rekortmen Kartı: En Çok Harcanan Para & En Yüksek Gelir -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            
            <!-- En Çok Harcanan Para -->
            <section class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                                <i class="ti ti-arrow-up-right text-2xl font-bold"></i>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-rose-600 dark:text-rose-400">Tek Seferde En Büyük Gider</div>
                                <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">En Çok Harcanan Para</h2>
                            </div>
                        </div>
                        <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                            Gider Rekoru
                        </span>
                    </div>

                    <div class="mt-6">
                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-400">Tutar (Asgari Ücret / Enflasyon Detayı İçin Üzerine Gelin)</div>
                        <div class="mt-1 text-3xl sm:text-4xl font-black text-rose-600 dark:text-rose-400 tabular-nums">
                            <?php if ($maxExpense && !empty($maxExpense['Amount'])) { ?>
                                <?= paraSpan($maxExpense['Amount'], ['date' => $maxExpense['Date'] ?? null, 'context' => 'movement']) ?>
                            <?php } else { ?>
                                -
                            <?php } ?>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3 rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/40 text-xs">
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Başlık</div>
                            <div class="mt-1 font-extrabold text-slate-800 dark:text-slate-200 truncate">
                                <?= !empty($maxExpense['Title']) ? htmlspecialchars((string)$maxExpense['Title'], ENT_QUOTES, 'UTF-8') : '-' ?>
                            </div>
                        </div>
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Kategori</div>
                            <div class="mt-1 font-extrabold text-slate-800 dark:text-slate-200 truncate">
                                <?= !empty($maxExpense['CategoryName']) ? htmlspecialchars((string)$maxExpense['CategoryName'], ENT_QUOTES, 'UTF-8') : '-' ?>
                            </div>
                        </div>
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Tarih</div>
                            <div class="mt-1 font-extrabold text-slate-800 dark:text-slate-200 tabular-nums">
                                <?= !empty($maxExpense['Date']) ? formatTarihSaat($maxExpense['Date']) : '-' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- En Yüksek Gelir -->
            <section class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                                <i class="ti ti-arrow-down-left text-2xl font-bold"></i>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Tek Seferde En Büyük Gelir</div>
                                <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">En Yüksek Gelir</h2>
                            </div>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                            Gelir Rekoru
                        </span>
                    </div>

                    <div class="mt-6">
                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-400">Tutar (Asgari Ücret / Enflasyon Detayı İçin Üzerine Gelin)</div>
                        <div class="mt-1 text-3xl sm:text-4xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                            <?php if ($maxIncome && !empty($maxIncome['Amount'])) { ?>
                                <?= paraSpan($maxIncome['Amount'], ['date' => $maxIncome['Date'] ?? null, 'context' => 'movement']) ?>
                            <?php } else { ?>
                                -
                            <?php } ?>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3 rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/40 text-xs">
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Başlık</div>
                            <div class="mt-1 font-extrabold text-slate-800 dark:text-slate-200 truncate">
                                <?= !empty($maxIncome['Title']) ? htmlspecialchars((string)$maxIncome['Title'], ENT_QUOTES, 'UTF-8') : '-' ?>
                            </div>
                        </div>
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Kategori</div>
                            <div class="mt-1 font-extrabold text-slate-800 dark:text-slate-200 truncate">
                                <?= !empty($maxIncome['CategoryName']) ? htmlspecialchars((string)$maxIncome['CategoryName'], ENT_QUOTES, 'UTF-8') : '-' ?>
                            </div>
                        </div>
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Tarih</div>
                            <div class="mt-1 font-extrabold text-slate-800 dark:text-slate-200 tabular-nums">
                                <?= !empty($maxIncome['Date']) ? formatTarihSaat($maxIncome['Date']) : '-' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- 4 Öne Çıkan Rekor Kartı -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            
            <!-- Kara Gün -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="grid h-10 w-10 place-items-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                        <i class="ti ti-flame text-lg"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">En Masraflı Gün</div>
                        <div class="text-sm font-extrabold text-slate-900 dark:text-white"><?= !empty($blackDay['d']) ? date("d.m.Y", strtotime($blackDay['d'])) : '-' ?></div>
                    </div>
                </div>
                <div class="mt-3 text-xl font-black text-rose-600 dark:text-rose-400 tabular-nums">
                    <?= !empty($blackDay['Total']) ? paraSpan($blackDay['Total'], ['date' => $blackDay['d'] ?? null, 'context' => 'movement']) : '-' ?>
                </div>
            </div>

            <!-- Zen Gün -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="grid h-10 w-10 place-items-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                        <i class="ti ti-leaf text-lg"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">En Sakin Gün</div>
                        <div class="text-sm font-extrabold text-slate-900 dark:text-white"><?= !empty($zenDay['d']) ? date("d.m.Y", strtotime($zenDay['d'])) : '-' ?></div>
                    </div>
                </div>
                <div class="mt-3 text-xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                    <?= !empty($zenDay['Total']) ? paraSpan($zenDay['Total'], ['date' => $zenDay['d'] ?? null, 'context' => 'movement']) : '-' ?>
                </div>
            </div>

            <!-- En Bereketli Ay -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="grid h-10 w-10 place-items-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                        <i class="ti ti-calendar text-lg"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">En Bereketli Ay</div>
                        <div class="text-sm font-extrabold text-slate-900 dark:text-white"><?= $bestMonth ? formatAyYil($bestMonth['ym']) : '-' ?></div>
                    </div>
                </div>
                <div class="mt-3 text-xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                    <?= $bestMonth ? paraSpan($bestMonth['net']) : '-' ?>
                </div>
            </div>

            <!-- Sessiz Düşman -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="grid h-10 w-10 place-items-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400">
                        <i class="ti ti-ghost text-lg"></i>
                    </div>
                    <div class="truncate">
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Sessiz Düşman</div>
                        <div class="text-sm font-extrabold text-slate-900 dark:text-white truncate"><?= !empty($silentEnemy['CategoryName']) ? htmlspecialchars((string)$silentEnemy['CategoryName'], ENT_QUOTES, 'UTF-8') : '-' ?></div>
                    </div>
                </div>
                <div class="mt-3 text-xl font-black text-amber-600 dark:text-amber-400 tabular-nums">
                    <?= !empty($silentEnemy['Total']) ? paraSpan($silentEnemy['Total']) : '-' ?>
                </div>
            </div>
        </div>

        <!-- Rekortmen Kategoriler -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            
            <!-- En Çok Harcanan Kategoriler -->
            <section class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="grid h-9 w-9 place-items-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                            <i class="ti ti-folder-minus text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">En Çok Harcama Yapılan Kategoriler</h2>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Tüm zamanların lider gider kalemleri</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 space-y-2.5">
                    <?php if (empty($topExpenseCats)) { ?>
                        <div class="text-xs text-slate-400 py-3">Veri bulunamadı.</div>
                    <?php } else {
                        foreach(array_slice($topExpenseCats, 0, 5) as $idx => $cat) { ?>
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-3 hover:bg-slate-50 dark:border-slate-800/60 dark:bg-slate-800/30 dark:hover:bg-slate-800/60">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid h-6 w-6 place-items-center rounded-lg bg-slate-200 text-xs font-black text-slate-700 dark:bg-slate-700 dark:text-slate-200"><?= $idx + 1 ?></span>
                                    <span class="font-bold text-xs text-slate-800 dark:text-slate-200"><?= htmlspecialchars((string)($cat['CategoryName'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="font-black text-xs text-rose-600 dark:text-rose-400 tabular-nums">
                                    <?= paraSpan($cat['Total']) ?>
                                </div>
                            </div>
                    <?php } } ?>
                </div>
            </section>

            <!-- En Çok Gelir Getiren Kategoriler -->
            <section class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="grid h-9 w-9 place-items-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                            <i class="ti ti-folder-plus text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">En Çok Gelir Getiren Kategoriler</h2>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Tüm zamanların lider gelir kalemleri</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 space-y-2.5">
                    <?php if (empty($topIncomeCats)) { ?>
                        <div class="text-xs text-slate-400 py-3">Veri bulunamadı.</div>
                    <?php } else {
                        foreach(array_slice($topIncomeCats, 0, 5) as $idx => $cat) { ?>
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-3 hover:bg-slate-50 dark:border-slate-800/60 dark:bg-slate-800/30 dark:hover:bg-slate-800/60">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid h-6 w-6 place-items-center rounded-lg bg-slate-200 text-xs font-black text-slate-700 dark:bg-slate-700 dark:text-slate-200"><?= $idx + 1 ?></span>
                                    <span class="font-bold text-xs text-slate-800 dark:text-slate-200"><?= htmlspecialchars((string)($cat['CategoryName'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="font-black text-xs text-emerald-600 dark:text-emerald-400 tabular-nums">
                                    <?= paraSpan($cat['Total']) ?>
                                </div>
                            </div>
                    <?php } } ?>
                </div>
            </section>
        </div>

    </main>

    <?php include("footer.php"); ?>
</div>

<?php include("scripts.php"); ?>
</body>
</html>
