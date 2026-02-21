<?php
include("fonksiyonlar.php");

$rapor = apiRequest('/rapor', 'GET', [], $_SESSION['Api_Token']);
$stats = apiRequest('/istatistikler', 'GET', [], $_SESSION['Api_Token']);

$r = (($rapor['code'] ?? 500) === 200) ? ($rapor['data'] ?? []) : [];
$s = (($stats['code'] ?? 500) === 200) ? ($stats['data'] ?? []) : [];

$totalIncome = (float)($r['toplam_gelir'] ?? 0);
$totalExpense = (float)($r['toplam_gider'] ?? 0);
$net = (float)($r['mevcut_durum'] ?? 0);

$incomeCats = $r['gelir_kategorileri'] ?? [];
$expenseCats = $r['gider_kategorileri'] ?? [];

// Sort cats desc
usort($incomeCats, function($a,$b){ return (float)($b['ToplamGelir'] ?? 0) <=> (float)($a['ToplamGelir'] ?? 0); });
usort($expenseCats, function($a,$b){ return (float)($b['ToplamGider'] ?? 0) <=> (float)($a['ToplamGider'] ?? 0); });

$topIncome = array_slice($incomeCats, 0, 8);
$topExpense = array_slice($expenseCats, 0, 8);

$monthly = $s['monthly'] ?? [];

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Yaşam Özeti</title>
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
                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Tek Sayfa</div>
                        <div class="mt-1 text-2xl font-extrabold tracking-tight">Yaşam Özeti</div>
                        <div class="mt-1 text-sm text-slate-600">Hayatının fişi.</div>
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
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Gelir Kaynakları</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">En çok getiren kategoriler</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700"><i class="ti ti-wallet"></i></div>
                    </div>

                    <div class="mt-4 space-y-2">
                        <?php foreach ($topIncome as $c) { $name=(string)($c['CategoryName'] ?? '-'); $val=(float)($c['ToplamGelir'] ?? 0); $pct = ($totalIncome>0)?min(100,($val/$totalIncome)*100):0; ?>
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <a class="truncate text-sm font-semibold text-slate-900 hover:underline" href="hareketler.php?type=1&CategoryId=<?php echo (int)($c['CategoryId'] ?? 0); ?>" title="<?php echo h($name); ?>"><?php echo h($name); ?></a>
                                    <div class="whitespace-nowrap text-sm font-extrabold tabular-nums text-emerald-700"><?php echo para($val); ?> ₺</div>
                                </div>
                                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100"><div class="h-1.5 rounded-full bg-emerald-600" style="width: <?php echo number_format($pct,2,'.',''); ?>%"></div></div>
                            </div>
                        <?php } ?>
                        <?php if (count($topIncome) === 0) { ?><div class="text-sm text-slate-600">Veri yok.</div><?php } ?>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Gider Delikleri</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">En çok eriten kategoriler</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700"><i class="ti ti-cash-banknote"></i></div>
                    </div>

                    <div class="mt-4 space-y-2">
                        <?php foreach ($topExpense as $c) { $name=(string)($c['CategoryName'] ?? '-'); $val=(float)($c['ToplamGider'] ?? 0); $pct = ($totalExpense>0)?min(100,($val/$totalExpense)*100):0; ?>
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <a class="truncate text-sm font-semibold text-slate-900 hover:underline" href="hareketler.php?type=2&CategoryId=<?php echo (int)($c['CategoryId'] ?? 0); ?>" title="<?php echo h($name); ?>"><?php echo h($name); ?></a>
                                    <div class="whitespace-nowrap text-sm font-extrabold tabular-nums text-rose-700"><?php echo para($val); ?> ₺</div>
                                </div>
                                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100"><div class="h-1.5 rounded-full bg-rose-600" style="width: <?php echo number_format($pct,2,'.',''); ?>%"></div></div>
                            </div>
                        <?php } ?>
                        <?php if (count($topExpense) === 0) { ?><div class="text-sm text-slate-600">Veri yok.</div><?php } ?>
                    </div>
                </section>
            </div>

            <div class="mt-6 rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Trend</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">Aylık gelir / gider / net</div>
                    </div>
                    <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700"><i class="ti ti-chart-line"></i></div>
                </div>
                <div class="mt-4">
                    <canvas id="monthlyNet2" height="140"></canvas>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs font-semibold text-slate-700">
                    <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-600"></span>Gelir (yeşil)</span>
                    <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-rose-600"></span>Gider (kırmızı)</span>
                    <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-slate-900"></span>Net (siyah)</span>
                </div>
            </div>
        </main>

        <?php include("footer.php"); ?>
    </div>

    <?php include("scripts.php"); ?>

    <script>
    (function () {
        var el = document.getElementById('monthlyNet2');
        if (!el) return;
        var rows = <?php echo json_encode(array_reverse($monthly), JSON_UNESCAPED_UNICODE); ?>;
        var labels = rows.map(r => r.ym);
        var income = rows.map(r => Number(r.income || 0));
        var expense = rows.map(r => Number(r.expense || 0));
        var net = rows.map(r => Number(r.income || 0) - Number(r.expense || 0));
        new Chart(el.getContext('2d'), {
            type: 'line',
            data: { labels: labels, datasets: [
                { label: 'Net', data: net, borderColor: 'rgba(15, 23, 42, 1)', backgroundColor: 'rgba(15, 23, 42, .06)', pointRadius: 2, fill: true, lineTension: .25 },
                { label: 'Gelir', data: income, borderColor: 'rgba(5, 150, 105, 1)', backgroundColor: 'rgba(5, 150, 105, .05)', pointRadius: 0, fill: false, lineTension: .25 },
                { label: 'Gider', data: expense, borderColor: 'rgba(225, 29, 72, 1)', backgroundColor: 'rgba(225, 29, 72, .05)', pointRadius: 0, fill: false, lineTension: .25 }
            ]},
            options: {
                legend: { display: false },
                scales: { xAxes: [{ gridLines: { display: false } }], yAxes: [{ ticks: { callback: function(v){ return v + ' ₺'; } } }] },
                tooltips: { callbacks: { label: function(t, d) { return d.datasets[t.datasetIndex].label + ': ' + t.yLabel + ' ₺'; } } }
            }
        });
    })();
    </script>
</body>
</html>
