<?php
include("fonksiyonlar.php");

$stats = apiRequest('/istatistikler', 'GET', [], $_SESSION['Api_Token']);
$data = ($stats['code'] ?? 500) === 200 ? ($stats['data'] ?? []) : [];

$blackDay = $data['black_day'] ?? null;
$zenDay = $data['zen_day'] ?? null;
$maxExpense = $data['max_expense'] ?? null;
$weird = $data['weird_expenses'] ?? [];
$impulse = $data['impulse_expenses'] ?? [];
$silentEnemy = $data['silent_enemy'] ?? null;

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>En'ler ve Şaşırtanlar</title>
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
                        <div class="mt-1 text-2xl font-extrabold tracking-tight">En'ler ve Şaşırtanlar</div>
                        <div class="mt-1 text-sm text-slate-600">Kara gün, zen günü, tek darbe ve ani kararlar.</div>
                    </div>
                    <div class="grid h-12 w-12 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                        <i class="ti ti-sparkles"></i>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Kara Gün</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">Hayatındaki en pahalı gün</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                            <i class="ti ti-moon-stars"></i>
                        </div>
                    </div>
                    <?php if ($blackDay) { ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="text-xs text-slate-500 tabular-nums"><?php echo h($blackDay['d'] ?? ''); ?></div>
                            <div class="mt-2 text-3xl font-extrabold tabular-nums text-rose-700"><?php echo para($blackDay['Total'] ?? 0); ?> ₺</div>
                        </div>
                    <?php } else { ?>
                        <div class="mt-4 text-sm text-slate-600">Veri yok.</div>
                    <?php } ?>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Zen Günü</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">En az harcadığın gün</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                            <i class="ti ti-leaf"></i>
                        </div>
                    </div>
                    <?php if ($zenDay) { ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="text-xs text-slate-500 tabular-nums"><?php echo h($zenDay['d'] ?? ''); ?></div>
                            <div class="mt-2 text-3xl font-extrabold tabular-nums text-emerald-700"><?php echo para($zenDay['Total'] ?? 0); ?> ₺</div>
                        </div>
                    <?php } else { ?>
                        <div class="mt-4 text-sm text-slate-600">Veri yok.</div>
                    <?php } ?>
                </section>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur xl:col-span-1">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Tek Darbe</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">Tek kalemde en can yakan</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                            <i class="ti ti-sword"></i>
                        </div>
                    </div>

                    <?php if ($maxExpense) { ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="text-xs text-slate-500 tabular-nums"><?php echo h($maxExpense['Date'] ?? ''); ?></div>
                            <div class="mt-2 text-3xl font-extrabold tabular-nums text-rose-700"><?php echo para($maxExpense['Amount'] ?? 0); ?> ₺</div>
                            <div class="mt-2 text-sm font-semibold text-slate-900 truncate" title="<?php echo h($maxExpense['Title'] ?? ''); ?>"><?php echo h($maxExpense['Title'] ?? ''); ?></div>
                            <div class="mt-1 text-xs text-slate-500"><?php echo h($maxExpense['CategoryName'] ?? ''); ?></div>
                            <a class="mt-3 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" href="hareketduzenle.php?type=gider&ID=<?php echo (int)($maxExpense['BillsId'] ?? 0); ?>">
                                <i class="ti ti-edit"></i>Detay
                            </a>
                        </div>
                    <?php } else { ?>
                        <div class="mt-4 text-sm text-slate-600">Veri yok.</div>
                    <?php } ?>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur xl:col-span-2">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Ani Karar</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">Gece harcamaları (22:00-05:00)</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                            <i class="ti ti-bolt"></i>
                        </div>
                    </div>

                    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tarih</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Başlık</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Kategori</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Tutar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($impulse as $r) { ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-3 py-2 whitespace-nowrap text-xs tabular-nums text-slate-600"><?php echo h($r['Date'] ?? ''); ?></td>
                                        <td class="px-3 py-2 text-sm font-semibold text-slate-900 truncate" title="<?php echo h($r['Title'] ?? ''); ?>"><?php echo h($r['Title'] ?? ''); ?></td>
                                        <td class="px-3 py-2 text-xs text-slate-600 truncate" title="<?php echo h($r['CategoryName'] ?? ''); ?>"><?php echo h($r['CategoryName'] ?? ''); ?></td>
                                        <td class="px-3 py-2 whitespace-nowrap text-right text-sm font-extrabold tabular-nums text-rose-700"><?php echo para($r['Amount'] ?? 0); ?> ₺</td>
                                    </tr>
                                <?php } ?>
                                <?php if (count($impulse) === 0) { ?>
                                    <tr><td colspan="4" class="px-3 py-4 text-sm text-slate-600">Veri yok.</td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Bu Neydi Şimdi?</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">Açıklaması zayıf ama pahalı</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                            <i class="ti ti-help"></i>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2">
                        <?php foreach ($weird as $r) { ?>
                            <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-900" title="<?php echo h($r['Title'] ?? ''); ?>"><?php echo h($r['Title'] ?? ''); ?></div>
                                    <div class="mt-1 text-xs text-slate-500 tabular-nums"><?php echo h($r['Date'] ?? ''); ?> | <?php echo h($r['CategoryName'] ?? ''); ?></div>
                                </div>
                                <div class="whitespace-nowrap text-sm font-extrabold tabular-nums text-rose-700"><?php echo para($r['Amount'] ?? 0); ?> ₺</div>
                            </div>
                        <?php } ?>
                        <?php if (count($weird) === 0) { ?>
                            <div class="text-sm text-slate-600">Veri yok.</div>
                        <?php } ?>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Sessiz Düşman</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">En çok tekrarlanan gider kategorisi</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                            <i class="ti ti-bug"></i>
                        </div>
                    </div>

                    <?php if ($silentEnemy) { ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="text-sm font-semibold text-slate-900"><?php echo h($silentEnemy['CategoryName'] ?? ''); ?></div>
                            <div class="mt-2 grid grid-cols-2 gap-3">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Adet</div>
                                    <div class="mt-1 text-xl font-extrabold tabular-nums text-slate-900"><?php echo (int)($silentEnemy['Cnt'] ?? 0); ?></div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Toplam</div>
                                    <div class="mt-1 text-xl font-extrabold tabular-nums text-rose-700"><?php echo para($silentEnemy['Total'] ?? 0); ?> ₺</div>
                                </div>
                            </div>
                            <a class="mt-4 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" href="hareketler.php?type=2&CategoryId=<?php echo (int)($silentEnemy['CategoryId'] ?? 0); ?>">
                                <i class="ti ti-list"></i>Hareketleri gör
                            </a>
                        </div>
                    <?php } else { ?>
                        <div class="mt-4 text-sm text-slate-600">Veri yok.</div>
                    <?php } ?>
                </section>
            </div>
        </main>

        <?php include("footer.php"); ?>
    </div>

    <?php include("scripts.php"); ?>
</body>
</html>
