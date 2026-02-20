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

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Ultra Finans Sistemi</title>
    <link href="https://fonts.googleapis.com/css?family=Manrope:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: { sans: ["Manrope", "ui-sans-serif", "system-ui", "Segoe UI", "Arial"] }
          }
        }
      }
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
            <div class="grid grid-cols-1 gap-6">
                <?php $positive = ($rapor["data"]["mevcut_durum"] ?? 0) > 0; ?>
                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Mevcut Durum</div>
                            <div class="mt-2 text-3xl font-extrabold tracking-tight <?php echo $positive ? 'text-emerald-700' : 'text-rose-700'; ?>">
                                <?php echo para($rapor["data"]["mevcut_durum"]); ?> ₺
                            </div>
                            <div class="mt-1 text-sm text-slate-500">Filtre: <?php echo $_SESSION['filtreBaslangic']; ?> - <?php echo $_SESSION['filtreBitis']; ?></div>
                        </div>
                        <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700">
                            <i class="ti ti-currency-lira text-slate-400"></i>
                            <span class="text-sm font-semibold"><?php echo $positive ? 'Pozitif' : 'Negatif'; ?></span>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-emerald-700">Gelirler</div>
                                <div class="mt-1 text-sm text-slate-600">Son <?php echo $gelirCount; ?> hareket</div>
                            </div>
                            <a href="hareketler.php?type=1" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700">
                                <i class="ti ti-wallet"></i>
                                <span>Tümü</span>
                            </a>
                        </div>

                        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <table class="min-w-full table-fixed divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="w-28 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tarih</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Başlık</th>
                                        <th class="w-32 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Tutar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach($gelirList as $gelir) { ?>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="px-4 py-3 whitespace-nowrap text-slate-600"><?php echo date("Y-m-d", strtotime($gelir["Date"])); ?></td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <?php if($gelir["Gerceklesmemis"] == 1) { ?>
                                                        <span class="inline-flex items-center text-emerald-600" aria-label="durum">
                                                            <i class="ti ti-hourglass"></i>
                                                        </span>
                                                    <?php } ?>
                                                    <a class="block min-w-0 flex-1 truncate font-semibold text-slate-900 hover:underline" title="<?php echo htmlspecialchars((string)$gelir["Title"], ENT_QUOTES, "UTF-8"); ?>" href="hareketduzenle.php?type=gelir&ID=<?php echo $gelir["AssetsId"]; ?>">
                                                        <?php echo $gelir["Title"]; ?>
                                                    </a>
                                                    <?php if (!empty($gelir["PhotoCount"])) { ?>
                                                        <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-extrabold text-slate-700" title="Ekli dosya var">
                                                            <i class="ti ti-paperclip text-slate-400"></i>Ek
                                                        </span>
                                                    <?php } ?>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-right font-extrabold text-slate-900"><?php echo para($gelir["Amount"]); ?> ₺</td>
                                        </tr>
                                    <?php } ?>
                                    <?php if (empty($gelirList)) { ?>
                                        <tr>
                                            <td colspan="3" class="px-4 py-4 text-center text-sm text-slate-500">Gosterilecek gelir hareketi yok.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                                <tfoot class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500" colspan="2">Toplam</th>
                                        <th class="px-4 py-3 text-right text-sm font-extrabold text-slate-900"><?php echo para($gelirler["data"]["total"]); ?> ₺</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-rose-700">Giderler</div>
                                <div class="mt-1 text-sm text-slate-600">Son <?php echo $giderCount; ?> hareket</div>
                            </div>
                            <a href="hareketler.php?type=2" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white shadow hover:bg-rose-700">
                                <i class="ti ti-arrow-down-right"></i>
                                <span>Tümü</span>
                            </a>
                        </div>

                        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <table class="min-w-full table-fixed divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="w-28 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tarih</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Başlık</th>
                                        <th class="w-32 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Tutar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach($giderList as $gider) { ?>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="px-4 py-3 whitespace-nowrap text-slate-600"><?php echo date("Y-m-d", strtotime($gider["Date"])); ?></td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <?php if($gider["Gerceklesmemis"] == 1) { ?>
                                                        <span class="inline-flex items-center text-rose-600" aria-label="durum">
                                                            <i class="ti ti-hourglass"></i>
                                                        </span>
                                                    <?php } ?>
                                                    <a class="block min-w-0 flex-1 truncate font-semibold text-slate-900 hover:underline" title="<?php echo htmlspecialchars((string)$gider["Title"], ENT_QUOTES, "UTF-8"); ?>" href="hareketduzenle.php?type=gider&ID=<?php echo $gider["BillsId"]; ?>">
                                                        <?php echo $gider["Title"]; ?>
                                                    </a>
                                                    <?php if (!empty($gider["PhotoCount"])) { ?>
                                                        <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-extrabold text-slate-700" title="Ekli dosya var">
                                                            <i class="ti ti-paperclip text-slate-400"></i>Ek
                                                        </span>
                                                    <?php } ?>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-right font-extrabold text-slate-900"><?php echo para($gider["Amount"]); ?> ₺</td>
                                        </tr>
                                    <?php } ?>
                                    <?php if (empty($giderList)) { ?>
                                        <tr>
                                            <td colspan="3" class="px-4 py-4 text-center text-sm text-slate-500">Gosterilecek gider hareketi yok.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                                <tfoot class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500" colspan="2">Toplam</th>
                                        <th class="px-4 py-3 text-right text-sm font-extrabold text-slate-900"><?php echo para($giderler["data"]["total"]); ?> ₺</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <?php include("footer.php"); ?>
    </div>

    <?php include("scripts.php"); ?>
</body>
</html>


