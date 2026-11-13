<?php
include("fonksiyonlar.php");

$stats = apiRequest('/istatistikler', 'GET', [], $_SESSION['Api_Token']);
if (($stats['code'] ?? 500) !== 200) {
    $stats = ["code" => 500, "data" => []];
}

$data = $stats['data'] ?? [];

$weekdayExpense = $data['weekday_expense'] ?? [];
$hourlyExpense = $data['hourly_expense'] ?? [];
$blackDay = $data['black_day'] ?? null;
$zenDay = $data['zen_day'] ?? null;
$weirdExpenses = $data['weird_expenses'] ?? [];
$impulseExpenses = $data['impulse_expenses'] ?? [];
$silentEnemy = $data['silent_enemy'] ?? null;
$salaryDurability = $data['salary_durability'] ?? null;

$dowNames = [1 => "Pazar", 2 => "Pazartesi", 3 => "Salı", 4 => "Çarşamba", 5 => "Perşembe", 6 => "Cuma", 7 => "Cumartesi"];
$worstDayName = "-";
if (!empty($weekdayExpense[0]["dow"])) {
    $worstDayName = $dowNames[(int)$weekdayExpense[0]["dow"]] ?? "-";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Para Davranışları - Ultra Finans</title>
    <?php include("head.php"); ?>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100" id="top">
<div class="min-h-screen lg:pl-64 flex flex-col">
    <?php include("menu.php"); ?>
    <?php include("ustmenu.php"); ?>

    <main class="w-full flex-1 px-4 py-6 lg:px-8 space-y-6">

        <!-- Başlık -->
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="ti ti-activity text-rose-600 dark:text-rose-400"></i>
                <span>Para Davranışları</span>
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                Harcama ritminiz, alışkanlıklarınız, gece kararları ve bütçe dayanıklılığı analizleri.
            </p>
        </div>

        <!-- 3 Ana Davranış Kartı: Kara Gün, Zen Gün, Sessiz Düşman -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            
            <!-- Kara Gün -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-2.5 text-rose-600 dark:text-rose-400">
                    <div class="grid h-10 w-10 place-items-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                        <i class="ti ti-flame text-lg"></i>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">En Yüksek Harcama</div>
                        <div class="text-sm font-extrabold text-slate-900 dark:text-white">Kara Gün</div>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-2xl font-black tabular-nums text-rose-600 dark:text-rose-400">
                        <?= !empty($blackDay['Total']) ? paraSpan($blackDay['Total']) : '-' ?>
                    </div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Tarih: <strong class="font-bold text-slate-800 dark:text-slate-200"><?= !empty($blackDay['d']) ? date("d.m.Y", strtotime($blackDay['d'])) : '-' ?></strong>
                    </div>
                </div>
            </div>

            <!-- Zen Gün -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-2.5 text-emerald-600 dark:text-emerald-400">
                    <div class="grid h-10 w-10 place-items-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                        <i class="ti ti-leaf text-lg"></i>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">En Düşük Harcama</div>
                        <div class="text-sm font-extrabold text-slate-900 dark:text-white">Zen Gün</div>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-2xl font-black tabular-nums text-emerald-600 dark:text-emerald-400">
                        <?= !empty($zenDay['Total']) ? paraSpan($zenDay['Total']) : '-' ?>
                    </div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Tarih: <strong class="font-bold text-slate-800 dark:text-slate-200"><?= !empty($zenDay['d']) ? date("d.m.Y", strtotime($zenDay['d'])) : '-' ?></strong>
                    </div>
                </div>
            </div>

            <!-- Sessiz Düşman -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-2.5 text-amber-600 dark:text-amber-400">
                    <div class="grid h-10 w-10 place-items-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400">
                        <i class="ti ti-ghost text-lg"></i>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">En Sık Harcanan Kategori</div>
                        <div class="text-sm font-extrabold text-slate-900 dark:text-white">Sessiz Düşman</div>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-lg font-black text-slate-900 dark:text-white truncate">
                        <?= !empty($silentEnemy['CategoryName']) ? htmlspecialchars((string)$silentEnemy['CategoryName'], ENT_QUOTES, 'UTF-8') : '-' ?>
                    </div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Toplam <strong class="font-bold text-slate-800 dark:text-slate-200"><?= (int)($silentEnemy['Cnt'] ?? 0) ?></strong> kez işlem yapıldı: 
                        <strong class="font-bold text-rose-600 dark:text-rose-400"><?= !empty($silentEnemy['Total']) ? paraSpan($silentEnemy['Total']) : '-' ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Haftalık Harcama Ritmi Grafiği -->
        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-4">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Haftanın Günlerine Göre Harcama Yoğunluğu</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Hangi günlerde daha çok para çıkışı olduğunu inceleyin.</p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-1.5 text-xs font-bold text-rose-600 dark:border-slate-800 dark:bg-slate-800 dark:text-rose-400">
                    Pik Gün: <?= $worstDayName ?>
                </div>
            </div>
            <div class="mt-4">
                <canvas id="weekdayChart" height="260"></canvas>
            </div>
        </section>

        <!-- 2 Sütun: Ani Kararlar (Gece Harcamaları) & Bu Neydi Şimdi? (Açıklamasızlar) -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            
            <!-- Ani Kararlar: Gece 22:00 - 05:00 -->
            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="grid h-9 w-9 place-items-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                            <i class="ti ti-moon-stars text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">Ani Kararlar (Gece Harcamaları)</h2>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">22:00 &mdash; 05:00 arası yapılan harcamalar</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 space-y-2.5 max-h-80 overflow-y-auto pr-1">
                    <?php if (empty($impulseExpenses)) { ?>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:bg-slate-800/40 dark:text-slate-400">
                            Gece saatlerinde kaydedilmiş belirgin harcama bulunamadı.
                        </div>
                    <?php } else {
                        foreach($impulseExpenses as $imp) { ?>
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-3 hover:bg-slate-50 dark:border-slate-800/60 dark:bg-slate-800/30 dark:hover:bg-slate-800/60">
                                <div>
                                    <div class="font-bold text-xs text-slate-800 dark:text-slate-200"><?= htmlspecialchars((string)($imp['Title'] ?? 'Gider'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-[11px] text-slate-400"><?= htmlspecialchars((string)($imp['CategoryName'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> &bull; <?= formatTarihSaat($imp['Date'] ?? '') ?></div>
                                </div>
                                <div class="font-black text-xs text-rose-600 dark:text-rose-400 tabular-nums">
                                    <?= paraSpan($imp['Amount'], ['date' => $imp['Date'], 'context' => 'movement']) ?>
                                </div>
                            </div>
                    <?php } } ?>
                </div>
            </section>

            <!-- Bu Neydi Şimdi?: Açıklamasız Büyük Harcamalar -->
            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="grid h-9 w-9 place-items-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400">
                            <i class="ti ti-help-circle text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">&ldquo;Bu Neydi Şimdi?&rdquo;</h2>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Açıklaması girilmemiş veya çok kısa olan büyük harcamalar</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 space-y-2.5 max-h-80 overflow-y-auto pr-1">
                    <?php if (empty($weirdExpenses)) { ?>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:bg-slate-800/40 dark:text-slate-400">
                            Tüm büyük harcamalarınız açıklamalı görünüyor, harika!
                        </div>
                    <?php } else {
                        foreach($weirdExpenses as $wrd) { ?>
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-3 hover:bg-slate-50 dark:border-slate-800/60 dark:bg-slate-800/30 dark:hover:bg-slate-800/60">
                                <div>
                                    <div class="font-bold text-xs text-slate-800 dark:text-slate-200"><?= htmlspecialchars((string)($wrd['Title'] ?? 'Gider'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-[11px] text-slate-400"><?= htmlspecialchars((string)($wrd['CategoryName'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> &bull; <?= formatTarihSaat($wrd['Date'] ?? '') ?></div>
                                </div>
                                <div class="font-black text-xs text-rose-600 dark:text-rose-400 tabular-nums">
                                    <?= paraSpan($wrd['Amount'], ['date' => $wrd['Date'], 'context' => 'movement']) ?>
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

<script>
$(document).ready(function() {
    var cColors = window.getChartColors();
    var weekdayData = <?= json_encode($weekdayExpense, JSON_UNESCAPED_UNICODE) ?>;
    var dowMap = {1: 'Pazar', 2: 'Pazartesi', 3: 'Salı', 4: 'Çarşamba', 5: 'Perşembe', 6: 'Cuma', 7: 'Cumartesi'};
    var wLabels = [];
    var wValues = [];
    
    for (var i = 1; i <= 7; i++) {
        wLabels.push(dowMap[i]);
        var found = weekdayData.find(function(x) { return Number(x.dow) === i; });
        wValues.push(found ? Number(found.Total || 0) : 0);
    }

    var wCtx = document.getElementById('weekdayChart');
    if (wCtx) {
        new Chart(wCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: wLabels,
                datasets: [{
                    label: 'Toplam Gider Tutarı',
                    data: wValues,
                    backgroundColor: 'rgba(244, 63, 94, 0.8)',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    xAxes: [{
                        gridLines: { display: false },
                        ticks: { fontColor: cColors.textColor, fontSize: 11 }
                    }],
                    yAxes: [{
                        gridLines: { color: cColors.gridColor },
                        ticks: {
                            fontColor: cColors.textColor,
                            fontSize: 11,
                            callback: function(v) { return Number(v).toLocaleString('tr-TR') + ' ₺'; }
                        }
                    }]
                }
            }
        });
    }
});
</script>
</body>
</html>
