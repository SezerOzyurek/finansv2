<?php
include("fonksiyonlar.php");

$type = $_GET["type"] ?? null;
if ($type != 1 && $type != 2) {
    $type = 2; // Varsayılan: Gider Raporları
}
$type = (int)$type;
$isIncome = ($type === 1);

// API'den genel rapor verisini al
$rapor = apiRequest('/rapor', 'GET', [], $_SESSION['Api_Token']);

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

$totalKey = $isIncome ? "toplam_gelir" : "toplam_gider";
$catKey = $isIncome ? "gelir_kategorileri" : "gider_kategorileri";
$catField = $isIncome ? "ToplamGelir" : "ToplamGider";

$accentText = $isIncome ? "text-emerald-600 dark:text-emerald-400" : "text-rose-600 dark:text-rose-400";
$accentBg = $isIncome ? "bg-emerald-600 hover:bg-emerald-700" : "bg-rose-600 hover:bg-rose-700";
$accentSoft = $isIncome ? "bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400" : "bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400";
$accentBorder = $isIncome ? "border-emerald-500" : "border-rose-500";
$accentBarColor = $isIncome ? "bg-emerald-500" : "bg-rose-500";

$grandTotal = (float)($rapor["data"][$totalKey] ?? 0);
$grandCats = $rapor["data"][$catKey] ?? [];
$grandCount = is_countable($grandCats) ? count($grandCats) : 0;
sortByFieldDesc($grandCats, $catField);

// Özel Tarih Aralığı Kontrolü
$customStart = isset($_GET["custom_start"]) ? trim((string)$_GET["custom_start"]) : "";
$customEnd = isset($_GET["custom_end"]) ? trim((string)$_GET["custom_end"]) : "";
$customStartValue = $customStart !== "" ? $customStart : date('Y-m-01');
$customEndValue = $customEnd !== "" ? $customEnd : date('Y-m-d');

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

function renderReportCard(array $reportConfig, array $reportResponse, string $totalKey, string $catKey, string $catField, bool $isIncome, string $accentSoft, string $accentText, string $accentBarColor): void
{
    $total = (float)($reportResponse["data"][$totalKey] ?? 0);
    $cats = $reportResponse["data"][$catKey] ?? [];
    sortByFieldDesc($cats, $catField);
    ?>
    <section class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
        <div>
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                <div class="flex items-center gap-2.5">
                    <div class="grid h-10 w-10 place-items-center rounded-2xl <?php echo $accentSoft; ?>">
                        <i class="<?php echo htmlspecialchars((string)($reportConfig["icon"] ?? ""), ENT_QUOTES, "UTF-8"); ?> text-lg"></i>
                    </div>
                    <div>
                        <div class="text-sm font-extrabold tracking-tight text-slate-900 dark:text-white"><?php echo htmlspecialchars((string)($reportConfig["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 tabular-nums"><?php echo htmlspecialchars((string)($reportConfig["start"] ?? ""), ENT_QUOTES, "UTF-8"); ?> &mdash; <?php echo htmlspecialchars((string)($reportConfig["end"] ?? ""), ENT_QUOTES, "UTF-8"); ?></div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Toplam <?php echo $isIncome ? 'Gelir' : 'Gider'; ?></div>
                <div class="mt-1 text-2xl sm:text-3xl font-black tabular-nums <?php echo $accentText; ?>">
                    <?php echo paraSpan($total); ?>
                </div>
            </div>

            <div class="mt-4">
                <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2 dark:border-slate-800 dark:bg-slate-800/50">
                    <div class="text-xs font-bold text-slate-700 dark:text-slate-200">Kategori Dağılımı</div>
                    <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400"><?php echo count($cats); ?> kategori</div>
                </div>

                <div class="mt-3 max-h-72 space-y-3 overflow-y-auto pr-1">
                    <?php if (count($cats) === 0) { ?>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-3 text-xs text-slate-500 dark:border-slate-800 dark:bg-slate-800/40 dark:text-slate-400">
                            Bu dönemde herhangi bir hareket kaydı bulunmuyor.
                        </div>
                    <?php } ?>

                    <?php foreach ($cats as $c) {
                        $name = (string)($c["CategoryName"] ?? "-");
                        $val = (float)($c[$catField] ?? 0);
                        $pct = ($total > 0) ? min(100.0, ($val / $total) * 100.0) : 0.0;
                        $catId = (int)($c["CategoryId"] ?? 0);
                    ?>
                        <div class="rounded-xl border border-slate-100 bg-slate-50/40 p-2.5 transition hover:bg-slate-50 dark:border-slate-800/60 dark:bg-slate-800/30 dark:hover:bg-slate-800/60">
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <a href="hareketler.php?type=<?php echo (int)($isIncome ? 1 : 2); ?>&CategoryId=<?php echo $catId; ?>" class="font-bold text-slate-800 hover:underline dark:text-slate-200 truncate">
                                    <?php echo htmlspecialchars($name, ENT_QUOTES, "UTF-8"); ?>
                                </a>
                                <div class="shrink-0 text-right font-black tabular-nums <?php echo $accentText; ?>">
                                    <?php echo paraSpan($val); ?>
                                </div>
                            </div>
                            <div class="mt-1.5 flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                    <div class="h-full rounded-full <?php echo $accentBarColor; ?>" style="width: <?php echo number_format($pct, 1, '.', ''); ?>%"></div>
                                </div>
                                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 tabular-nums shrink-0"><?php echo number_format($pct, 1, ',', '.'); ?>%</span>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title><?php echo $isIncome ? 'Gelir Raporları' : 'Gider Raporları'; ?> - Ultra Finans</title>
    <?php include("head.php"); ?>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100" id="top">
<div class="min-h-screen lg:pl-64 flex flex-col">
    <?php include("menu.php"); ?>
    <?php include("ustmenu.php"); ?>

    <main class="w-full flex-1 px-3 py-4 lg:px-6 space-y-4">

        <!-- Başlık ve Sekme Seçimi -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                    <i class="<?php echo $isIncome ? 'ti ti-arrow-down-left text-emerald-600 dark:text-emerald-400' : 'ti ti-arrow-up-right text-rose-600 dark:text-rose-400'; ?>"></i>
                    <span><?php echo $isIncome ? 'Gelir Raporları' : 'Gider Raporları'; ?></span>
                </h1>
                <p class="mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Haftalık, aylık, yıllık ve özel tarih filtreli enflasyon/asgari ücret karşılıklı detaylı döküm.
                </p>
            </div>

            <!-- Gelir / Gider Sekme Butonları -->
            <div class="flex items-center gap-1.5 rounded-2xl border border-slate-200/90 bg-white p-1 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <a href="rapor.php?type=1" class="rounded-xl px-4 py-2 text-xs font-bold transition flex items-center gap-1.5 <?php echo $isIncome ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'; ?>">
                    <i class="ti ti-arrow-down-left text-sm"></i>
                    <span>Gelir Raporları</span>
                </a>
                <a href="rapor.php?type=2" class="rounded-xl px-4 py-2 text-xs font-bold transition flex items-center gap-1.5 <?php echo !$isIncome ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'; ?>">
                    <i class="ti ti-arrow-up-right text-sm"></i>
                    <span>Gider Raporları</span>
                </a>
            </div>
        </div>

        <!-- Üst Metrikler Özeti -->
        <div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Genel Özet</div>
                    <div class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                        <?php echo $isIncome ? 'Tüm Zamanların Gelir Dökümü' : 'Tüm Zamanların Gider Dökümü'; ?>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/60">
                        <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Toplam Tutar</div>
                        <div class="mt-1 text-xl font-black tabular-nums <?php echo $accentText; ?>"><?php echo paraSpan($grandTotal); ?></div>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/60">
                        <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Kategori Sayısı</div>
                        <div class="mt-1 text-xl font-black tabular-nums text-slate-900 dark:text-white"><?php echo (int)$grandCount; ?></div>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/60">
                        <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Net Durum</div>
                        <div class="mt-1 text-xl font-black tabular-nums <?php echo (($rapor["data"]["mevcut_durum"] ?? 0) >= 0) ? "text-emerald-600 dark:text-emerald-400" : "text-rose-600 dark:text-rose-400"; ?>">
                            <?php echo paraSpan($rapor["data"]["mevcut_durum"] ?? 0); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- İsteğe Bağlı Tarih Aralığı Filtresi -->
        <section class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <details class="group" <?php if ($customStart !== "" || $customEnd !== "") { echo "open"; } ?>>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-bold text-slate-900 dark:text-white">
                    <div class="flex items-center gap-2.5">
                        <div class="grid h-10 w-10 place-items-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                            <i class="ti ti-calendar-search text-lg"></i>
                        </div>
                        <div>
                            <div class="text-sm font-extrabold text-slate-900 dark:text-white">İsteğe Bağlı Tarih Aralığı İle Rapor Al</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">Belirlediğiniz iki tarih arasındaki gelir/gider dökümünü listeler.</div>
                        </div>
                    </div>
                    <span class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 transition group-open:rotate-180 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        <i class="ti ti-chevron-down"></i>
                    </span>
                </summary>

                <form method="GET" class="mt-5 flex flex-col gap-4 lg:flex-row lg:items-end">
                    <input type="hidden" name="type" value="<?php echo (int)$type; ?>">

                    <div class="flex-1">
                        <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Başlangıç Tarihi</label>
                        <input type="date" name="custom_start" value="<?php echo htmlspecialchars($customStartValue, ENT_QUOTES, "UTF-8"); ?>"
                               class="mt-1.5 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>

                    <div class="flex-1">
                        <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Bitiş Tarihi</label>
                        <input type="date" name="custom_end" value="<?php echo htmlspecialchars($customEndValue, ENT_QUOTES, "UTF-8"); ?>"
                               class="mt-1.5 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="rounded-2xl <?php echo $accentBg; ?> px-6 py-3 text-sm font-extrabold text-white shadow-xs transition active:scale-[0.99]">
                            Raporu Göster
                        </button>
                        <?php if ($customStart !== "" || $customEnd !== "") { ?>
                            <a href="rapor.php?type=<?php echo (int)$type; ?>" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                Temizle
                            </a>
                        <?php } ?>
                    </div>
                </form>

                <?php if ($customRangeCard && $customRangeResponse) { ?>
                    <div class="mt-6 border-t border-slate-100 pt-6 dark:border-slate-800">
                        <?php renderReportCard($customRangeCard, $customRangeResponse, $totalKey, $catKey, $catField, $isIncome, $accentSoft, $accentText, $accentBarColor); ?>
                    </div>
                <?php } ?>
            </details>
        </section>

        <!-- 4 Dönemsel Rapor Kartı: Haftalık, Aylık, Yıllık, Tüm Zamanlar -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <?php foreach ($periods as $p) {
                $resp = apiRequest('/rapor', 'GET', ["baslangictarihi" => $p["start"], "bitistarihi" => $p["end"]], $_SESSION['Api_Token']);
                renderReportCard($p, $resp, $totalKey, $catKey, $catField, $isIncome, $accentSoft, $accentText, $accentBarColor);
            } ?>
        </div>

        <!-- Grafikler Alanı: Kategori Dağılımı -->
        <div class="w-full">
            
            <!-- Kategori Dağılımı Çubuk Grafiği -->
            <section class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Grafik Analizi</div>
                        <div class="text-sm font-extrabold text-slate-900 dark:text-white">Kategori Dağılımı (Tüm Zamanlar)</div>
                    </div>
                    <div class="grid h-10 w-10 place-items-center rounded-2xl bg-slate-50 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        <i class="ti ti-chart-bar text-lg"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <?php if ($isIncome) { ?>
                        <canvas id="gelirBarGrafigi" height="300"></canvas>
                    <?php } else { ?>
                        <canvas id="giderBarGrafigi" height="300"></canvas>
                    <?php } ?>
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

    <?php if ($isIncome) { ?>
        // Gelir Bar Grafiği
        var gelirData = <?php echo json_encode($rapor["data"]["gelir_kategorileri"] ?? [], JSON_UNESCAPED_UNICODE); ?>;
        var gLabels = [];
        var gValues = [];
        for (var i = 0; i < gelirData.length; i++) {
            gLabels.push(gelirData[i].CategoryName);
            gValues.push(Number(gelirData[i].ToplamGelir || 0));
        }

        var gCtx = document.getElementById('gelirBarGrafigi');
        if (gCtx) {
            new Chart(gCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: gLabels,
                    datasets: [{
                        label: 'Toplam Gelir',
                        data: gValues,
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderRadius: 6
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
    <?php } else { ?>
        // Gider Bar Grafiği
        var giderData = <?php echo json_encode($rapor["data"]["gider_kategorileri"] ?? [], JSON_UNESCAPED_UNICODE); ?>;
        var giLabels = [];
        var giValues = [];
        for (var i = 0; i < giderData.length; i++) {
            giLabels.push(giderData[i].CategoryName);
            giValues.push(Number(giderData[i].ToplamGider || 0));
        }

        var giCtx = document.getElementById('giderBarGrafigi');
        if (giCtx) {
            new Chart(giCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: giLabels,
                    datasets: [{
                        label: 'Toplam Gider',
                        data: giValues,
                        backgroundColor: 'rgba(244, 63, 94, 0.8)',
                        borderRadius: 6
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
    <?php } ?>
});
</script>
</body>
</html>
