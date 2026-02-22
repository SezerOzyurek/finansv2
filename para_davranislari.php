<?php
include("fonksiyonlar.php");

$stats = apiRequest('/istatistikler', 'GET', [], $_SESSION['Api_Token']);
$data = ($stats['code'] ?? 500) === 200 ? ($stats['data'] ?? []) : [];

$totalIncome = (float)($data['total_income'] ?? 0);
$totalExpense = (float)($data['total_expense'] ?? 0);
$net = (float)($data['net'] ?? 0);
$savingsRate = (float)($data['savings_rate'] ?? 0);

$pulse = $data['daily_pulse']['list'] ?? [];
$hourly = $data['hourly_expense'] ?? [];
$salary = $data['salary_durability'] ?? null;
$topExpenseCats = $data['top_expense_categories'] ?? [];

$goldenHours = array_slice($hourly, 0, 5);

function hourRangeLabel(int $h): string {
    $h1 = ($h + 1) % 24;
    return str_pad((string)$h, 2, '0', STR_PAD_LEFT).":00-".str_pad((string)$h1, 2, '0', STR_PAD_LEFT).":00";
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Para Davranışları</title>
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
                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Rapor</div>
                        <div class="mt-1 text-2xl font-extrabold tracking-tight">Para Davranışları</div>
                        <div class="mt-1 text-sm text-slate-600">Günlük refleksler, altın saatler ve dayanıklılık.</div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Tasarruf</div>
                            <div class="mt-1 text-xl font-extrabold tabular-nums text-slate-900"><?php echo number_format($savingsRate * 100, 1, ',', '.'); ?>%</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Net</div>
                            <div class="mt-1 text-xl font-extrabold tabular-nums <?php echo ($net >= 0) ? 'text-emerald-700' : 'text-rose-700'; ?>"><?php echo para($net); ?> ₺</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Gider</div>
                            <div class="mt-1 text-xl font-extrabold tabular-nums text-rose-700"><?php echo para($totalExpense); ?> ₺</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6">
                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Günlük Nabız</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">Son 1 ay gider ritmi</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                            <i class="ti ti-heart-rate-monitor"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <canvas id="pulseChart" height="130"></canvas>
                    </div>
                </section>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Altın Saatler</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">Paranın en hızlı eridiği saatler</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                            <i class="ti ti-clock-hour-4"></i>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <?php foreach ($goldenHours as $h) { ?>
                            <?php $hour = (int)($h['h'] ?? 0); $val = (float)($h['Total'] ?? 0); ?>
                            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="text-sm font-extrabold tabular-nums text-slate-900"><?php echo hourRangeLabel($hour); ?></div>
                                <div class="text-sm font-extrabold tabular-nums text-rose-700"><?php echo para($val); ?> ₺</div>
                            </div>
                        <?php } ?>
                        <?php if (count($goldenHours) === 0) { ?>
                            <div class="text-sm text-slate-600">Veri yok.</div>
                        <?php } ?>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Maaş Dayanıklılık</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">Son gelir kaç günde buharlaştı?</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                            <i class="ti ti-battery-2"></i>
                        </div>
                    </div>

                    <?php if (!empty($salary)) { ?>
                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Gelir</div>
                                <div class="mt-1 text-xl font-extrabold tabular-nums text-slate-900"><?php echo para($salary['income_amount'] ?? 0); ?> ₺</div>
                                <div class="mt-1 text-xs text-slate-500 tabular-nums"><?php echo htmlspecialchars($salary['income_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Sonuç</div>
                                <?php if (!empty($salary['burn_date'])) { ?>
                                    <div class="mt-1 text-xl font-extrabold tabular-nums text-rose-700"><?php echo (int)($salary['days'] ?? 0); ?> gün</div>
                                    <div class="mt-1 text-xs text-slate-500 tabular-nums"><?php echo htmlspecialchars($salary['burn_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php } else { ?>
                                    <div class="mt-1 text-xl font-extrabold tabular-nums text-emerald-700">Dayanıyor</div>
                                    <div class="mt-1 text-xs text-slate-500">Harcanan: <?php echo para($salary['spent_since'] ?? 0); ?> ₺</div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            Bu rapor, en son gelir hareketinden sonra yapılan giderlerin bu geliri ne kadar sürede tükettiğini yaklaşık hesaplar.
                        </div>
                    <?php } else { ?>
                        <div class="mt-4 text-sm text-slate-600">Veri yok.</div>
                    <?php } ?>
                </section>
            </div>

            <div class="mt-6 rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Sessiz Düşman</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">En çok para emen kategori (gider)</div>
                    </div>
                    <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                        <i class="ti ti-bug"></i>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <?php foreach (array_slice($topExpenseCats, 0, 6) as $c) { ?>
                        <?php $name = (string)($c['CategoryName'] ?? '-'); $val = (float)($c['Total'] ?? 0); $pctVal = ($totalExpense > 0) ? min(100.0, ($val / $totalExpense) * 100.0) : 0.0; ?>
                        <div class="rounded-3xl border border-slate-200 bg-white p-5">
                            <div class="truncate text-sm font-semibold text-slate-900" title="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="mt-2 text-lg font-extrabold tabular-nums text-rose-700"><?php echo para($val); ?> ₺</div>
                            <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-1.5 rounded-full bg-rose-600" style="width: <?php echo number_format($pctVal, 2, '.', ''); ?>%"></div>
                            </div>
                            <div class="mt-2 text-xs text-slate-500"><?php echo number_format($pctVal, 1, ',', '.'); ?>%</div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </main>

        <?php include("footer.php"); ?>
    </div>

    <?php include("scripts.php"); ?>

    <script>
    (function () {
        var el = document.getElementById('pulseChart');
        if (!el) return;
        var pulseRows = <?php echo json_encode($pulse, JSON_UNESCAPED_UNICODE); ?>;
        var labels = pulseRows.map(r => r.d);
        var vals = pulseRows.map(r => Number(r.Total || 0));
        new Chart(el.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Gider',
                    data: vals,
                    borderColor: 'rgba(225, 29, 72, 1)',
                    backgroundColor: 'rgba(225, 29, 72, .06)',
                    pointRadius: 0,
                    fill: true,
                    lineTension: .25
                }]
            },
            options: {
                legend: { display: false },
                scales: {
                    xAxes: [{ gridLines: { display: false }, ticks: { maxTicksLimit: 7 } }],
                    yAxes: [{ ticks: { callback: function(v) { return v + ' ₺'; } } }]
                },
                tooltips: { callbacks: { label: function(t) { return t.yLabel + ' ₺'; } } }
            }
        });
    })();
    </script>
</body>
</html>
