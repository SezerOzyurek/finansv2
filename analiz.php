<?php
include("fonksiyonlar.php");

$stats = apiRequest('/istatistikler', 'GET', [], $_SESSION['Api_Token']);
if (($stats['code'] ?? 500) !== 200) {
    // Fallback: keep the UI alive.
    $stats = ["code" => 500, "data" => []];
}

$data = $stats['data'] ?? [];

$totalIncome = (float)($data['total_income'] ?? 0);
$totalExpense = (float)($data['total_expense'] ?? 0);
$net = (float)($data['net'] ?? 0);
$savingsRate = (float)($data['savings_rate'] ?? 0);

$maxIncome = $data['max_income'] ?? null;

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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Finans Nabzı</title>
    <link href="https://fonts.googleapis.com/css?family=Manrope:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { fontFamily: { sans: ["Manrope", "ui-sans-serif", "system-ui", "Segoe UI", "Arial"] } } } }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
      body { font-family: Manrope, ui-sans-serif, system-ui, Segoe UI, Arial, sans-serif; }
      .app-bg {
        background:
          radial-gradient(900px 400px at 20% 0%, rgba(15, 23, 42, .08), transparent 60%),
          radial-gradient(900px 400px at 80% 10%, rgba(2, 132, 199, .10), transparent 55%),
          linear-gradient(#f8fafc, #f8fafc);
      }
    </style>
</head>
<body class="app-bg text-slate-900" id="top">
    <div class="min-h-screen lg:pl-64 flex flex-col">
        <?php include("menu.php"); ?>
        <?php include("ustmenu.php"); ?>

        <main class="w-full flex-1 px-4 py-6 lg:px-8">
            <div class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Analiz</div>
                        <div class="mt-1 text-2xl font-extrabold tracking-tight">Finans Nabzı</div>
                        <div class="mt-1 text-sm text-slate-600">Elindeki verilerden çıkarılabilen "vay be" istatistikleri.</div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Gelir</div>
                            <div class="mt-1 text-xl font-extrabold tabular-nums text-emerald-700"><?php echo para($totalIncome); ?> ₺</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Gider</div>
                            <div class="mt-1 text-xl font-extrabold tabular-nums text-rose-700"><?php echo para($totalExpense); ?> ₺</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Net</div>
                            <div class="mt-1 text-xl font-extrabold tabular-nums <?php echo ($net >= 0) ? 'text-emerald-700' : 'text-rose-700'; ?>"><?php echo para($net); ?> ₺</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Tasarruf Oranı</div>
                            <div class="mt-1 text-xl font-extrabold tabular-nums text-slate-900"><?php echo number_format($savingsRate * 100, 1, ',', '.'); ?>%</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Rekor</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">En büyük tek seferlik hareketler</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                            <i class="ti ti-trophy"></i>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="flex items-center justify-between">
                                <div class="text-xs font-semibold uppercase tracking-widest text-emerald-700">En büyük gelir</div>
                                <i class="ti ti-arrow-up-right text-emerald-700"></i>
                            </div>
                            <?php if ($maxIncome) { ?>
                                <div class="mt-2 text-sm font-extrabold tabular-nums text-slate-900"><?php echo para($maxIncome['Amount'] ?? 0); ?> ₺</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900 truncate" title="<?php echo htmlspecialchars($maxIncome['Title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($maxIncome['Title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="mt-1 text-xs text-slate-500 tabular-nums"><?php echo htmlspecialchars($maxIncome['Date'] ?? '', ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars($maxIncome['CategoryName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php } else { ?>
                                <div class="mt-2 text-sm text-slate-600">Veri yok.</div>
                            <?php } ?>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="flex items-center justify-between">
                                <div class="text-xs font-semibold uppercase tracking-widest text-slate-700">En iyi ay</div>
                                <i class="ti ti-chart-line text-slate-700"></i>
                            </div>
                            <?php if ($bestMonth) { ?>
                                <div class="mt-2 text-sm font-extrabold tabular-nums text-slate-900"><?php echo htmlspecialchars($bestMonth['ym'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="mt-1 text-2xl font-extrabold tabular-nums <?php echo ((float)($bestMonth['net'] ?? 0) >= 0) ? 'text-emerald-700' : 'text-rose-700'; ?>"><?php echo para($bestMonth['net'] ?? 0); ?> ₺</div>
                                <div class="mt-2 text-xs text-slate-500 tabular-nums">Gelir: <?php echo para($bestMonth['income'] ?? 0); ?> ₺ | Gider: <?php echo para($bestMonth['expense'] ?? 0); ?> ₺</div>
                            <?php } else { ?>
                                <div class="mt-2 text-sm text-slate-600">Veri yok.</div>
                            <?php } ?>
                        </div>
                    </div>
                </section>

                <!-- Davranış raporları Para Davranışları sayfasına taşındı. -->
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Gider ritmi</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">Hangi gün daha çok harcanıyor?</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                            <i class="ti ti-calendar-stats"></i>
                        </div>
                    </div>

                    <?php if ($worstDay) { ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">En pahalı gün</div>
                            <div class="mt-1 text-2xl font-extrabold tabular-nums text-slate-900"><?php echo dowName($worstDay['dow'] ?? 0); ?></div>
                            <div class="mt-1 text-sm text-slate-600">Toplam: <span class="font-extrabold tabular-nums text-rose-700"><?php echo para($worstDay['Total'] ?? 0); ?> ₺</span></div>
                        </div>
                    <?php } else { ?>
                        <div class="mt-4 text-sm text-slate-600">Veri yok.</div>
                    <?php } ?>

                    <div class="mt-4 grid grid-cols-1 gap-2">
                        <?php foreach ($weekday as $w) { ?>
                            <?php
                                $val = (float)($w['Total'] ?? 0);
                                $pctw = ($totalExpense > 0) ? min(100.0, ($val / $totalExpense) * 100.0) : 0.0;
                            ?>
                            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2">
                                <div class="w-24 text-xs font-semibold text-slate-700"><?php echo dowName($w['dow'] ?? 0); ?></div>
                                <div class="flex-1">
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full bg-rose-600" style="width: <?php echo number_format($pctw, 2, '.', ''); ?>%"></div>
                                    </div>
                                </div>
                                <div class="w-28 text-right text-xs font-extrabold tabular-nums text-slate-900"><?php echo para($val); ?> ₺</div>
                            </div>
                        <?php } ?>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Trend</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">Aylık net durum</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                            <i class="ti ti-chart-line"></i>
                        </div>
                    </div>

                    <div class="mt-4">
                        <canvas id="monthlyNet" height="220"></canvas>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-xs font-semibold text-slate-700">
                        <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-600"></span>Gelir (yeşil)</span>
                        <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-rose-600"></span>Gider (kırmızı)</span>
                        <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-slate-900"></span>Net (siyah)</span>
                    </div>
                </section>
            </div>
        </main>

        <?php include("footer.php"); ?>
    </div>

    <?php include("scripts.php"); ?>

    <script>
    (function () {
        var el = document.getElementById('monthlyNet');
        if (!el) return;

        var rows = <?php echo json_encode(array_reverse($monthly), JSON_UNESCAPED_UNICODE); ?>;
        var labels = rows.map(r => r.ym);
        var income = rows.map(r => Number(r.income || 0));
        var expense = rows.map(r => Number(r.expense || 0));
        var net = rows.map(r => Number(r.income || 0) - Number(r.expense || 0));

        new Chart(el.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Net', data: net, borderColor: 'rgba(15, 23, 42, 1)', backgroundColor: 'rgba(15, 23, 42, .06)', pointRadius: 2, fill: true, lineTension: .25 },
                    { label: 'Gelir', data: income, borderColor: 'rgba(5, 150, 105, 1)', backgroundColor: 'rgba(5, 150, 105, .05)', pointRadius: 0, fill: false, lineTension: .25 },
                    { label: 'Gider', data: expense, borderColor: 'rgba(225, 29, 72, 1)', backgroundColor: 'rgba(225, 29, 72, .05)', pointRadius: 0, fill: false, lineTension: .25 }
                ]
            },
            options: {
                legend: { display: false },
                scales: {
                    xAxes: [{ gridLines: { display: false } }],
                    yAxes: [{ ticks: { callback: function(v) { return v + ' ₺'; } } }]
                },
                tooltips: { callbacks: { label: function(t, d) { return d.datasets[t.datasetIndex].label + ': ' + t.yLabel + ' ₺'; } } }
            }
        });
    })();
    </script>
</body>
</html>


