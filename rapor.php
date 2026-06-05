<?php
include("fonksiyonlar.php");

if (empty($_POST)) {
    $type = $_GET["type"] ?? null;
}

if ($type != 1 && $type != 2) {
    header("Location: index.php");
    exit;
}

$rapor = apiRequest('/rapor', 'GET', [], $_SESSION['Api_Token']);
$zaman_akisi = apiRequest('/zaman_akisi', 'GET', ["baslangictarihi" => date("Y-m-d", strtotime("-3 months")), "bitistarihi" => date("Y-m-d")], $_SESSION['Api_Token']);

$periods = [
    [
        "key" => "weekly",
        "title" => "Haftalık",
        "icon" => "ti ti-calendar-week",
        "start" => date('Y-m-d', strtotime('monday this week')),
        "end" => date('Y-m-d', strtotime('sunday this week')),
    ],
    [
        "key" => "monthly",
        "title" => "Aylık",
        "icon" => "ti ti-calendar-month",
        "start" => date('Y-m-01'),
        "end" => date('Y-m-t'),
    ],
    [
        "key" => "yearly",
        "title" => "Yıllık",
        "icon" => "ti ti-calendar-time",
        "start" => date('Y-01-01'),
        "end" => date('Y-12-31'),
    ],
    [
        "key" => "all",
        "title" => "Tüm Zamanlar",
        "icon" => "ti ti-timeline",
        "start" => '2014-01-01',
        "end" => date('Y-12-31'),
    ],
];

function sortByFieldDesc(array &$rows, string $field): void
{
    usort($rows, function ($a, $b) use ($field) {
        $av = (float)($a[$field] ?? 0);
        $bv = (float)($b[$field] ?? 0);
        return $bv <=> $av;
    });
}

function renderReportCard(array $reportConfig, array $reportResponse, string $totalKey, string $catKey, string $catField, bool $isIncome, string $accentSoft, string $accentText, string $accentBg): void
{
    $total = (float)($reportResponse["data"][$totalKey] ?? 0);
    $cats = $reportResponse["data"][$catKey] ?? [];
    sortByFieldDesc($cats, $catField);
    ?>
    <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <div class="grid h-9 w-9 place-items-center rounded-2xl <?php echo $accentSoft; ?>">
                        <i class="<?php echo htmlspecialchars((string)($reportConfig["icon"] ?? ""), ENT_QUOTES, "UTF-8"); ?>"></i>
                    </div>
                    <div>
                        <div class="text-sm font-extrabold tracking-tight text-slate-900"><?php echo htmlspecialchars((string)($reportConfig["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></div>
                        <div class="text-xs text-slate-500 tabular-nums"><?php echo htmlspecialchars((string)($reportConfig["start"] ?? ""), ENT_QUOTES, "UTF-8"); ?> - <?php echo htmlspecialchars((string)($reportConfig["end"] ?? ""), ENT_QUOTES, "UTF-8"); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5">
            <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Toplam</div>
            <div class="mt-1 text-3xl font-extrabold tabular-nums <?php echo $accentText; ?>"><?php echo para($total); ?> ₺</div>
        </div>

        <div class="mt-4">
            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-3 py-2">
                <div class="text-xs font-semibold text-slate-700">Tüm Kategoriler</div>
                <div class="text-[11px] text-slate-500"><?php echo count($cats); ?> kategori</div>
            </div>

            <div class="mt-3 max-h-96 space-y-3 overflow-y-auto pr-1">
                <?php if (count($cats) === 0) { ?>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">Bu dönemde veri yok.</div>
                <?php } ?>

                <?php foreach ($cats as $c) { ?>
                    <?php
                        $name = (string)($c["CategoryName"] ?? "-");
                        $val = (float)($c[$catField] ?? 0);
                        $pct = ($total > 0) ? min(100.0, ($val / $total) * 100.0) : 0.0;
                        $catId = (int)($c["CategoryId"] ?? 0);
                        $catHref = $isIncome
                            ? ("hareketler.php?type=1&CategoryId=".$catId)
                            : ("hareketler.php?type=2&CategoryId=".$catId);
                    ?>
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <a class="truncate text-sm font-semibold text-slate-900 hover:underline" href="<?php echo htmlspecialchars($catHref, ENT_QUOTES, "UTF-8"); ?>" title="<?php echo htmlspecialchars($name, ENT_QUOTES, "UTF-8"); ?>">
                                    <?php echo htmlspecialchars($name, ENT_QUOTES, "UTF-8"); ?>
                                </a>
                            </div>
                            <div class="whitespace-nowrap text-sm font-extrabold tabular-nums text-slate-900"><?php echo para($val); ?> ₺</div>
                        </div>
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-1.5 rounded-full <?php echo $accentBg; ?>" style="width: <?php echo number_format($pct, 2, '.', ''); ?>%"></div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Rapor</title>
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
            <?php
                $isIncome = ((int)$type === 1);
                $accentText = $isIncome ? "text-emerald-700" : "text-rose-700";
                $accentBg = $isIncome ? "bg-emerald-600" : "bg-rose-600";
                $accentSoft = $isIncome ? "bg-emerald-50 text-emerald-700" : "bg-rose-50 text-rose-700";
                $totalKey = $isIncome ? "toplam_gelir" : "toplam_gider";
                $catKey = $isIncome ? "gelir_kategorileri" : "gider_kategorileri";
                $catField = $isIncome ? "ToplamGelir" : "ToplamGider";

                $grandTotal = (float)($rapor["data"][$totalKey] ?? 0);
                $grandCount = count($rapor["data"][$catKey] ?? []);
                $customStart = trim((string)($_GET["custom_start"] ?? ""));
                $customEnd = trim((string)($_GET["custom_end"] ?? ""));
                $defaultCustomStart = date('Y-m-d', strtotime('-1 month'));
                $defaultCustomEnd = date('Y-m-d');
                $customStartValue = $customStart !== "" ? $customStart : $defaultCustomStart;
                $customEndValue = $customEnd !== "" ? $customEnd : $defaultCustomEnd;
                $customRangeCard = null;
                $customRangeResponse = null;

                if ($customStart !== "" && $customEnd !== "") {
                    $startDate = DateTime::createFromFormat('Y-m-d', $customStart);
                    $endDate = DateTime::createFromFormat('Y-m-d', $customEnd);
                    $startValid = $startDate && $startDate->format('Y-m-d') === $customStart;
                    $endValid = $endDate && $endDate->format('Y-m-d') === $customEnd;

                    if ($startValid && $endValid && $customStart <= $customEnd) {
                        $customRangeCard = [
                            "key" => "custom",
                            "title" => "Özel Tarih Aralığı",
                            "icon" => "ti ti-calendar-search",
                            "start" => $customStart,
                            "end" => $customEnd,
                        ];
                        $customRangeResponse = apiRequest('/rapor', 'GET', [
                            "baslangictarihi" => $customStart,
                            "bitistarihi" => $customEnd
                        ], $_SESSION['Api_Token']);
                    }
                }
            ?>

            <div class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Analiz</div>
                        <div class="mt-1 text-2xl font-extrabold tracking-tight">
                            <?php if ($isIncome) { ?>Gelir Raporları<?php } else { ?>Gider Raporları<?php } ?>
                        </div>
                        <div class="mt-1 text-sm text-slate-600">Aynı veriler, daha temiz bir rapor görünümü.</div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Toplam</div>
                            <div class="mt-1 text-xl font-extrabold tabular-nums <?php echo $accentText; ?>"><?php echo para($grandTotal); ?> ₺</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Kategori</div>
                            <div class="mt-1 text-xl font-extrabold tabular-nums text-slate-900"><?php echo (int)$grandCount; ?></div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Mevcut Durum</div>
                            <div class="mt-1 text-xl font-extrabold tabular-nums <?php echo (($rapor["data"]["mevcut_durum"] ?? 0) >= 0) ? "text-emerald-700" : "text-rose-700"; ?>"><?php echo para($rapor["data"]["mevcut_durum"] ?? 0); ?> ₺</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-4">
                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur md:col-span-2 xl:col-span-2 2xl:col-span-4">
                    <details class="group" <?php echo $customRangeCard ? "open" : ""; ?>>
                        <summary class="flex cursor-pointer list-none items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="grid h-11 w-11 place-items-center rounded-2xl <?php echo $accentSoft; ?>">
                                    <i class="ti ti-calendar-search text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-extrabold tracking-tight text-slate-900">Özel Tarih Aralığı</div>
                                    <div class="mt-1 text-xs text-slate-500">İki tarih seçildikten sonra aynı görünümde rapor kartı oluşur.</div>
                                </div>
                            </div>
                            <div class="flex h-9 w-9 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition group-open:rotate-180">
                                <i class="ti ti-chevron-down"></i>
                            </div>
                        </summary>

                        <form method="GET" class="mt-5 flex flex-col gap-4 lg:flex-row lg:items-end">
                            <input type="hidden" name="type" value="<?php echo (int)$type; ?>">

                            <label class="block flex-1">
                                <div class="mb-2 text-[11px] font-semibold uppercase tracking-widest text-slate-500">Başlangıç</div>
                                <input type="date" name="custom_start" value="<?php echo htmlspecialchars($customStartValue, ENT_QUOTES, "UTF-8"); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400">
                            </label>

                            <label class="block flex-1">
                                <div class="mb-2 text-[11px] font-semibold uppercase tracking-widest text-slate-500">Bitiş</div>
                                <input type="date" name="custom_end" value="<?php echo htmlspecialchars($customEndValue, ENT_QUOTES, "UTF-8"); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400">
                            </label>

                            <div class="flex gap-3">
                                <button type="submit" class="rounded-2xl <?php echo $accentBg; ?> px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:opacity-95">Raporu Göster</button>
                                <?php if ($customStart !== "" || $customEnd !== "") { ?>
                                    <a href="rapor.php?type=<?php echo (int)$type; ?>" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Temizle</a>
                                <?php } ?>
                            </div>
                        </form>

                        <?php if ($customRangeCard && $customRangeResponse) { ?>
                            <div class="mt-6 border-t border-slate-200 pt-6">
                                <?php renderReportCard($customRangeCard, $customRangeResponse, $totalKey, $catKey, $catField, $isIncome, $accentSoft, $accentText, $accentBg); ?>
                            </div>
                        <?php } ?>
                    </details>
                </section>

                <?php foreach ($periods as $p) { ?>
                    <?php $resp = apiRequest('/rapor', 'GET', ["baslangictarihi" => $p["start"], "bitistarihi" => $p["end"]], $_SESSION['Api_Token']); ?>
                    <?php renderReportCard($p, $resp, $totalKey, $catKey, $catField, $isIncome, $accentSoft, $accentText, $accentBg); ?>
                <?php } ?>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Grafik</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900">Kategori Dağılımı (Tüm Zamanlar)</div>
                            </div>
                            <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                                <i class="ti ti-chart-bar"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <?php if ($isIncome) { ?>
                                <canvas id="gelirBarGrafigi" height="320"></canvas>
                            <?php } else { ?>
                                <canvas id="giderBarGrafigi" height="320"></canvas>
                            <?php } ?>
                        </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Anlık Durum</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900">Son 3 Ay Bakiye</div>
                            </div>
                            <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                                <i class="ti ti-chart-line"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <canvas id="anlikDurumGrafigi" width="400" height="200"></canvas>
                        </div>
                </section>
            </div>
        </main>

        <?php include("footer.php"); ?>
    </div>

    <?php include("scripts.php"); ?>

    <script>
    function rastgeleRenk() { return `hsl(${Math.floor(Math.random() * 360)}, 70%, 60%)`; }

        <?php if($type == 2) { ?>
        var giderBarGrafigi = document.getElementById("giderBarGrafigi").getContext("2d");

        var kategoriler = [<?php foreach(($rapor["data"]["gider_kategorileri"] ?? []) as $giderKategori) { ?>"<?php echo $giderKategori["CategoryName"]; ?>",<?php } ?>];
        var fiyatlar = [<?php foreach(($rapor["data"]["gider_kategorileri"] ?? []) as $giderKategori) { ?>"<?php echo $giderKategori["ToplamGider"]; ?>",<?php } ?>];
        var renkler = kategoriler.map(() => rastgeleRenk());

        new Chart(giderBarGrafigi, {
            type: 'bar',
            data: {
                labels: kategoriler,
                datasets: [{
                    label: "Kategoriler",
                    backgroundColor: renkler,
                    data: fiyatlar
                }]
            },
            options: {
                legend: { display: false },
                scales: {
                    xAxes: [{ gridLines: { display: false } }],
                    yAxes: [{ ticks: { beginAtZero: true } }]
                }
            }
        });
        <?php } ?>

        <?php if($type == 1) { ?>
        var gelirBarGrafigi = document.getElementById("gelirBarGrafigi").getContext("2d");

        var kategoriler = [<?php foreach(($rapor["data"]["gelir_kategorileri"] ?? []) as $gelirKategori) { ?>"<?php echo $gelirKategori["CategoryName"]; ?>",<?php } ?>];
        var fiyatlar = [<?php foreach(($rapor["data"]["gelir_kategorileri"] ?? []) as $gelirKategori) { ?>"<?php echo $gelirKategori["ToplamGelir"]; ?>",<?php } ?>];
        var renkler = kategoriler.map(() => rastgeleRenk());

        new Chart(gelirBarGrafigi, {
            type: 'bar',
            data: {
                labels: kategoriler,
                datasets: [{
                    label: "Kategoriler",
                    backgroundColor: renkler,
                    data: fiyatlar
                }]
            },
            options: {
                legend: { display: false },
                scales: {
                    xAxes: [{ gridLines: { display: false } }],
                    yAxes: [{ ticks: { beginAtZero: true } }]
                }
            }
        });
        <?php } ?>

        var anlikDurumGrafigi = document.getElementById("anlikDurumGrafigi").getContext("2d");
        var tarihEtiketleri = [<?php foreach(($zaman_akisi["data"] ?? []) as $akisData) { ?>"<?php echo date("Y-m-d", strtotime($akisData["tarih"])); ?>",<?php } ?>];
        var bakiye = [<?php foreach(($zaman_akisi["data"] ?? []) as $akisData) { ?>"<?php echo $akisData["bakiye"]; ?>",<?php } ?>];

        new Chart(anlikDurumGrafigi, {
            type: 'line',
            data: {
                labels: tarihEtiketleri,
                datasets: [{
                    label: "Bakiye",
                    lineTension: 0.25,
                    backgroundColor: "rgba(2, 132, 199, 0.06)",
                    borderColor: "rgba(2, 132, 199, 1)",
                    pointRadius: 2,
                    pointBackgroundColor: "rgba(2, 132, 199, 1)",
                    pointBorderColor: "rgba(2, 132, 199, 1)",
                    fill: true,
                    data: bakiye
                }]
            },
            options: {
                legend: { display: false },
                scales: {
                    xAxes: [{ gridLines: { display: false } }],
                    yAxes: [{ ticks: { callback: function(v) { return v + ' ₺'; } } }]
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return tooltipItem.yLabel + ' ₺';
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
