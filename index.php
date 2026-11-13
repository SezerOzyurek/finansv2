<?php 
include("fonksiyonlar.php");

$gelirler = apiRequest('/gelirler', 'GET', ["start" => 0, "length" => 200, "orderkey" => "Date", "ordertype" => "DESC"], $_SESSION['Api_Token']);
$giderler = apiRequest('/giderler', 'GET', ["start" => 0, "length" => 200, "orderkey" => "Date", "ordertype" => "DESC"], $_SESSION['Api_Token']);
$rapor = apiRequest('/rapor', 'GET', [], $_SESSION['Api_Token']);

$currentMonth = date("Y-m");
$isPendingOutsideCurrentMonth = static function($item) use ($currentMonth) {
    $pending = (int)($item["Gerceklesmemis"] ?? 0) === 1;
    if (!$pending) { return false; }
    $dateValue = (string)($item["Date"] ?? "");
    $ts = strtotime($dateValue);
    if ($ts === false) { return false; }
    return date("Y-m", $ts) !== $currentMonth;
};

$gelirList = [];
foreach (($gelirler["data"]["list"] ?? []) as $gelir) {
    if ($isPendingOutsideCurrentMonth($gelir)) { continue; }
    $gelirList[] = $gelir;
    if (count($gelirList) >= 15) { break; }
}

$giderList = [];
foreach (($giderler["data"]["list"] ?? []) as $gider) {
    if ($isPendingOutsideCurrentMonth($gider)) { continue; }
    $giderList[] = $gider;
    if (count($giderList) >= 15) { break; }
}
$gelirCount = count($gelirList);
$giderCount = count($giderList);

$raporData = $rapor["data"] ?? [];
$mevcutDurum = (float)($raporData["mevcut_durum"] ?? 0);
$toplamGelir = (float)($raporData["toplam_gelir"] ?? ($gelirler["data"]["total"] ?? 0));
$toplamGider = (float)($raporData["toplam_gider"] ?? ($giderler["data"]["total"] ?? 0));
$isPositive = $mevcutDurum >= 0;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Ultra Finans - Kişisel Finans Yönetim Sistemi</title>
    <?php include("head.php"); ?>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100" id="top">
<div class="min-h-screen lg:pl-64 flex flex-col">
    <?php include("menu.php"); ?>
    <?php include("ustmenu.php"); ?>

    <!-- Tam Genişlikte İçerik Alanı -->
    <main class="w-full flex-1 px-3 py-4 lg:px-6 space-y-4">

        <!-- 1. KISIM: MEVCUT DURUM HERO KARTI -->
        <section class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                
                <!-- Bakiye ve Durum Bilgisi -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Net Kasa & Varlık Durumu</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-extrabold <?= $isPositive ? 'bg-emerald-500/15 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/15 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/30' ?>">
                            <span class="h-2 w-2 rounded-full <?= $isPositive ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
                            <?= $isPositive ? 'Pozitif Bakiye' : 'Bütçe Açığı' ?>
                        </span>
                    </div>

                    <div class="text-3xl sm:text-4xl font-black tracking-tight <?= $isPositive ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?>">
                        <?= paraSpan($mevcutDurum) ?>
                    </div>
                </div>

                <!-- Tek Buton: Hareket Ekle -->
                <div class="flex items-center">
                    <a href="hareket.php" class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-indigo-500 active:scale-[0.99] dark:bg-indigo-600 dark:hover:bg-indigo-500">
                        <i class="ti ti-plus text-base"></i>
                        <span>Hareket Ekle</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- 2. KISIM: ESKİ DÜZEN ÇERÇEVESİNDE YAN YANA İKİ AYRI TABLO -->
        <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            
            <!-- SOL TABLO: GELİRLER -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="grid h-10 w-10 place-items-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400 font-bold">
                                <i class="ti ti-arrow-down-left text-lg"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Gelirler</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Son <?= $gelirCount ?> gelir hareketi</p>
                            </div>
                        </div>
                        <a href="hareketler.php?type=1" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-500">
                            <i class="ti ti-wallet text-sm"></i>
                            <span>Tümü</span>
                        </a>
                    </div>

                    <div class="mt-4 -mx-2 sm:mx-0 overflow-x-auto rounded-2xl border border-slate-100 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-950/50">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-100/80 dark:bg-slate-800/90 text-slate-700 dark:text-slate-300 border-b border-slate-200/80 dark:border-slate-800">
                                <tr>
                                    <th class="w-28 px-3 py-2 text-left font-bold uppercase tracking-wider">Tarih</th>
                                    <th class="px-3 py-2 text-left font-bold uppercase tracking-wider">Başlık</th>
                                    <th class="w-28 px-3 py-2 text-right font-bold uppercase tracking-wider">Tutar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                                <?php foreach($gelirList as $gelir) { 
                                    $accName = $gelir["AccountName"] ?? "";
                                ?>
                                    <tr class="hover:bg-slate-100/70 dark:hover:bg-slate-800/60 transition-colors">
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-500 dark:text-slate-400 font-medium">
                                            <?= formatTarihSaat($gelir["Date"]); ?>
                                        </td>
                                        <td class="px-3 py-2 min-w-0">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <?php if((int)($gelir["Gerceklesmemis"] ?? 0) === 1) { ?>
                                                    <span class="inline-flex items-center text-emerald-600 dark:text-emerald-400" title="Gerçekleşmemiş">
                                                        <i class="ti ti-hourglass text-xs"></i>
                                                    </span>
                                                <?php } ?>
                                                <a class="truncate font-bold text-slate-900 hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400 transition-colors"
                                                   title="<?= htmlspecialchars((string)$gelir["Title"], ENT_QUOTES, "UTF-8"); ?>"
                                                   href="hareketduzenle.php?type=gelir&ID=<?= $gelir["AssetsId"]; ?>">
                                                    <?= htmlspecialchars((string)$gelir["Title"], ENT_QUOTES, "UTF-8"); ?>
                                                </a>
                                                <?php if (!empty($gelir["PhotoCount"])) { ?>
                                                    <span class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.2 text-[9px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300" title="Ekli Dosya">
                                                        <i class="ti ti-paperclip text-[9px]"></i>
                                                    </span>
                                                <?php } ?>
                                                <?php if (!empty($accName)) { ?>
                                                    <span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.2 text-[10px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700" title="Hesap: <?= htmlspecialchars((string)$accName, ENT_QUOTES, "UTF-8"); ?>">
                                                        <?= htmlspecialchars((string)$accName, ENT_QUOTES, "UTF-8"); ?>
                                                    </span>
                                                <?php } ?>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-right font-black tabular-nums text-emerald-700 dark:text-emerald-400 text-xs sm:text-sm">
                                            <?= paraSpan($gelir["Amount"], ["date" => date("Y-m-d", strtotime((string)$gelir["Date"])), "context" => "movement"]); ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php if (empty($gelirList)) { ?>
                                    <tr>
                                        <td colspan="3" class="px-4 py-6 text-center text-xs text-slate-500 dark:text-slate-400">
                                            Kayıtlı gelir hareketi bulunmuyor.
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot class="bg-slate-100/90 dark:bg-slate-800/90 border-t border-slate-200/80 dark:border-slate-800">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400" colspan="2">Toplam Gelir</th>
                                    <th class="px-3 py-2 text-right text-xs sm:text-sm font-black tabular-nums text-emerald-700 dark:text-emerald-400">
                                        <?= paraSpan($gelirler["data"]["total"] ?? 0); ?>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SAĞ TABLO: GİDERLER -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="grid h-10 w-10 place-items-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400 font-bold">
                                <i class="ti ti-arrow-up-right text-lg"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Giderler</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Son <?= $giderCount ?> gider hareketi</p>
                            </div>
                        </div>
                        <a href="hareketler.php?type=2" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-rose-500">
                            <i class="ti ti-arrow-up-right text-sm"></i>
                            <span>Tümü</span>
                        </a>
                    </div>

                    <div class="mt-4 -mx-2 sm:mx-0 overflow-x-auto rounded-2xl border border-slate-100 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-950/50">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-100/80 dark:bg-slate-800/90 text-slate-700 dark:text-slate-300 border-b border-slate-200/80 dark:border-slate-800">
                                <tr>
                                    <th class="w-28 px-3 py-2 text-left font-bold uppercase tracking-wider">Tarih</th>
                                    <th class="px-3 py-2 text-left font-bold uppercase tracking-wider">Başlık</th>
                                    <th class="w-28 px-3 py-2 text-right font-bold uppercase tracking-wider">Tutar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                                <?php foreach($giderList as $gider) { 
                                    $accName = $gider["AccountName"] ?? "";
                                ?>
                                    <tr class="hover:bg-slate-100/70 dark:hover:bg-slate-800/60 transition-colors">
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-500 dark:text-slate-400 font-medium">
                                            <?= formatTarihSaat($gider["Date"]); ?>
                                        </td>
                                        <td class="px-3 py-2 min-w-0">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <?php if((int)($gider["Gerceklesmemis"] ?? 0) === 1) { ?>
                                                    <span class="inline-flex items-center text-rose-600 dark:text-rose-400" title="Gerçekleşmemiş">
                                                        <i class="ti ti-hourglass text-xs"></i>
                                                    </span>
                                                <?php } ?>
                                                <a class="truncate font-bold text-slate-900 hover:text-rose-600 dark:text-white dark:hover:text-rose-400 transition-colors"
                                                   title="<?= htmlspecialchars((string)$gider["Title"], ENT_QUOTES, "UTF-8"); ?>"
                                                   href="hareketduzenle.php?type=gider&ID=<?= $gider["BillsId"]; ?>">
                                                    <?= htmlspecialchars((string)$gider["Title"], ENT_QUOTES, "UTF-8"); ?>
                                                </a>
                                                <?php if (!empty($gider["PhotoCount"])) { ?>
                                                    <span class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.2 text-[9px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300" title="Ekli Dosya">
                                                        <i class="ti ti-paperclip text-[9px]"></i>
                                                    </span>
                                                <?php } ?>
                                                <?php if (!empty($accName)) { ?>
                                                    <span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.2 text-[10px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700" title="Hesap: <?= htmlspecialchars((string)$accName, ENT_QUOTES, "UTF-8"); ?>">
                                                        <?= htmlspecialchars((string)$accName, ENT_QUOTES, "UTF-8"); ?>
                                                    </span>
                                                <?php } ?>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-right font-black tabular-nums text-rose-600 dark:text-rose-400 text-xs sm:text-sm">
                                            <?= paraSpan($gider["Amount"], ["date" => date("Y-m-d", strtotime((string)$gider["Date"])), "context" => "movement"]); ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php if (empty($giderList)) { ?>
                                    <tr>
                                        <td colspan="3" class="px-4 py-6 text-center text-xs text-slate-500 dark:text-slate-400">
                                            Kayıtlı gider hareketi bulunmuyor.
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot class="bg-slate-100/90 dark:bg-slate-800/90 border-t border-slate-200/80 dark:border-slate-800">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400" colspan="2">Toplam Gider</th>
                                    <th class="px-3 py-2 text-right text-xs sm:text-sm font-black tabular-nums text-rose-600 dark:text-rose-400">
                                        <?= paraSpan($giderler["data"]["total"] ?? 0); ?>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </section>

    </main>

    <?php include("footer.php"); ?>
</div>

<?php include("scripts.php"); ?>
</body>
</html>
