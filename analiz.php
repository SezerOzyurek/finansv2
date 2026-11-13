<?php
include("fonksiyonlar.php");

$stats = apiRequest('/istatistikler', 'GET', [], $_SESSION['Api_Token']);
if (($stats['code'] ?? 500) !== 200) {
    $stats = ["code" => 500, "data" => []];
}

$data = $stats['data'] ?? [];

$totalIncome = (float)($data['total_income'] ?? 0);
$totalExpense = (float)($data['total_expense'] ?? 0);
$net = (float)($data['net'] ?? 0);
$savingsRate = (float)($data['savings_rate'] ?? 0);

$maxIncome = $data['max_income'] ?? null;
$maxExpense = $data['max_expense'] ?? null;

$topIn = $data['top_income_categories'] ?? [];
$topOut = $data['top_expense_categories'] ?? [];
$weekday = $data['weekday_expense'] ?? [];
$monthly = $data['monthly'] ?? [];

function dowName($dow): string {
    $map = [1=>"Pazar",2=>"Pazartesi",3=>"Salı",4=>"Çarşamba",5=>"Perşembe",6=>"Cuma",7=>"Cumartesi"]; 
    $d = (int)$dow;
    return $map[$d] ?? "-";
}

$worstDay = null;
if (is_array($weekday) && count($weekday) > 0) {
    $worstDay = $weekday[0];
}

$bestMonth = null;
if (is_array($monthly) && count($monthly) > 0) {
    foreach ($monthly as $m) {
        $income = (float)($m['income'] ?? 0);
        $expense = (float)($m['expense'] ?? 0);
        $mNet = $income - $expense;
        if ($bestMonth === null || $mNet > (float)($bestMonth['net'] ?? -INF)) {
            $bestMonth = ["ym" => (string)($m['ym'] ?? ''), "net" => $mNet, "income" => $income, "expense" => $expense];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Finans Nabzı - Ultra Finans</title>
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
                <i class="ti ti-wave-sine text-indigo-600 dark:text-indigo-400"></i>
                <span>Finans Nabzı</span>
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                Verilerinizden derlenen tasarruf oranları, bütçe dengesi ve finansal trendler.
            </p>
        </div>

        <!-- Üst KPI Kartları -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Toplam Gelir</div>
                        <div class="mt-1 text-2xl font-black tabular-nums text-emerald-600 dark:text-emerald-400"><?php echo paraSpan($totalIncome); ?></div>
                    </div>
                    <div class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                        <i class="ti ti-arrow-down-left text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Toplam Gider</div>
                        <div class="mt-1 text-2xl font-black tabular-nums text-rose-600 dark:text-rose-400"><?php echo paraSpan($totalExpense); ?></div>
                    </div>
                    <div class="grid h-11 w-11 place-items-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                        <i class="ti ti-arrow-up-right text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Net Birikim</div>
                        <div class="mt-1 text-2xl font-black tabular-nums <?php echo ($net >= 0) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'; ?>"><?php echo paraSpan($net); ?></div>
                    </div>
                    <div class="grid h-11 w-11 place-items-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                        <i class="ti ti-wallet text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Tasarruf Oranı</div>
                        <div class="mt-1 text-2xl font-black tabular-nums text-slate-900 dark:text-white"><?php echo number_format($savingsRate * 100, 1, ',', '.'); ?>%</div>
                    </div>
                    <div class="grid h-11 w-11 place-items-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400">
                        <i class="ti ti-percentage text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Öne Çıkan Tespitler: En Masraflı Gün & En Bereketli Ay -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start gap-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                        <i class="ti ti-calendar-event text-xl"></i>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Haftanın En Masraflı Günü</div>
                        <div class="mt-1 text-xl font-black text-slate-900 dark:text-white">
                            <?php echo $worstDay ? dowName($worstDay['dow']) : 'Yeterli veri yok'; ?>
                        </div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Bu günde toplam harcama: <strong class="font-bold text-rose-600 dark:text-rose-400"><?php echo $worstDay ? paraSpan($worstDay['Total']) : '-'; ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start gap-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                        <i class="ti ti-sparkles text-xl"></i>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">En Bereketli Ay</div>
                        <div class="mt-1 text-xl font-black text-slate-900 dark:text-white">
                            <?php echo $bestMonth ? htmlspecialchars($bestMonth['ym'], ENT_QUOTES, 'UTF-8') : 'Yeterli veri yok'; ?>
                        </div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Net artı bakiye: <strong class="font-bold text-emerald-600 dark:text-emerald-400"><?php echo $bestMonth ? paraSpan($bestMonth['net']) : '-'; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aylık Net Akış Tablosu -->
        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-4">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Son 12 Ayın Nakit Akış Trendi</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Aylık bazda gelir, gider ve net tasarruf dengesi.</p>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-100 bg-slate-50/50 p-2 dark:border-slate-800 dark:bg-slate-950/50">
                <table class="min-w-full text-xs" cellspacing="0">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400">
                            <th class="py-3 px-4 text-left font-bold">Dönem</th>
                            <th class="py-3 px-4 text-right font-bold">Gelir</th>
                            <th class="py-3 px-4 text-right font-bold">Gider</th>
                            <th class="py-3 px-4 text-right font-bold">Net Bakiye</th>
                            <th class="py-3 px-4 text-right font-bold">Tasarruf %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-semibold">
                        <?php if (empty($monthly)) { ?>
                            <tr><td colspan="5" class="py-4 text-center text-slate-400">Veri bulunamadı.</td></tr>
                        <?php } else { 
                            foreach($monthly as $m) {
                                $inc = (float)($m['income'] ?? 0);
                                $exp = (float)($m['expense'] ?? 0);
                                $mNet = $inc - $exp;
                                $rate = ($inc > 0) ? round(($mNet / $inc) * 100, 1) : 0;
                        ?>
                            <tr class="hover:bg-slate-100/70 dark:hover:bg-slate-800/60 transition-colors">
                                <td class="py-3 px-4 font-bold text-slate-900 dark:text-white"><?= htmlspecialchars((string)($m['ym'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-3 px-4 text-right tabular-nums text-emerald-600 dark:text-emerald-400"><?= paraSpan($inc) ?></td>
                                <td class="py-3 px-4 text-right tabular-nums text-rose-600 dark:text-rose-400"><?= paraSpan($exp) ?></td>
                                <td class="py-3 px-4 text-right tabular-nums font-extrabold <?= ($mNet >= 0) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?>"><?= paraSpan($mNet) ?></td>
                                <td class="py-3 px-4 text-right tabular-nums font-bold <?= ($rate >= 0) ? 'text-slate-700 dark:text-slate-300' : 'text-rose-500' ?>"><?= $rate ?>%</td>
                            </tr>
                        <?php } } ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <?php include("footer.php"); ?>
</div>

<?php include("scripts.php"); ?>
</body>
</html>
