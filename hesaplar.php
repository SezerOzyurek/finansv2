<?php 
include("fonksiyonlar.php");

$action = $_GET['action'] ?? false;
$accountId = $_GET['ID'] ?? false;

if (empty($_POST)) 
{ 
	if ($action === "edit" && $accountId) {
		$mevcutHesap = apiRequest('/hesaplar', 'GET', ["AccountId" => $accountId], $_SESSION['Api_Token']);
	}

	$hesaplarResp = apiRequest('/hesaplar', 'GET', [], $_SESSION['Api_Token']);
	$hesapData = (isset($hesaplarResp["data"]) && is_array($hesaplarResp["data"])) ? $hesaplarResp["data"] : [];
	$hesapCount = isset($hesapData["count"]) ? (int)$hesapData["count"] : 0;
	$hesapList = (isset($hesapData["list"]) && is_array($hesapData["list"])) ? $hesapData["list"] : [];
	$defaultAccount = $hesapData["default_account"] ?? null;
}

if (isset($_POST['yeniHesap'])) 
{
	$yanit = apiRequest('/hesaplar', 'POST', $_POST, $_SESSION['Api_Token']);
	echo json_encode($yanit, JSON_UNESCAPED_UNICODE);
	exit;
} 

if (isset($_POST['hesapGuncelle'])) 
{
	$yanit = apiRequest('/hesaplar', 'PATCH', $_POST, $_SESSION['Api_Token']);
	echo json_encode($yanit, JSON_UNESCAPED_UNICODE);
	exit;
} 

if (isset($_POST['hesapSil'])) 
{
	$yanit = apiRequest('/hesaplar', 'DELETE', $_POST, $_SESSION['Api_Token']);
	echo json_encode($yanit, JSON_UNESCAPED_UNICODE);
	exit;
} 
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Hesaplar ve Kartlar</title>
    <link href="https://fonts.googleapis.com/css?family=Manrope:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { fontFamily: { sans: ["Manrope", "ui-sans-serif", "system-ui", "Segoe UI", "Arial"] } } } }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
      body { font-family: Manrope, ui-sans-serif, system-ui, Segoe UI, Arial, sans-serif; }
      .app-bg {
        background:
          radial-gradient(900px 400px at 20% 0%, rgba(15, 23, 42, .08), transparent 60%),
          radial-gradient(900px 400px at 80% 10%, rgba(2, 132, 199, .10), transparent 55%),
          linear-gradient(#f8fafc, #f8fafc);
      }
      table.dataTable { border-collapse: collapse !important; table-layout: fixed !important; width: 100% !important; }
      table.dataTable thead th { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: rgb(100 116 139); background: rgb(248 250 252); white-space: nowrap; }
      table.dataTable thead th, table.dataTable tbody td { padding: 8px 12px !important; }
      table.dataTable tbody td { font-size: 13px; line-height: 1.25; border-top: 1px solid rgb(241 245 249); vertical-align: middle; }
      table.dataTable tbody tr:hover td { background: rgb(248 250 252); }
      .dataTables_wrapper .dataTables_filter input { border: 1px solid rgb(226 232 240); border-radius: 10px; padding: 6px 8px; font-size: 12px; }
      .dataTables_wrapper .dataTables_length select { border: 1px solid rgb(226 232 240); border-radius: 10px; padding: 4px 8px; font-size: 12px; }
      .dataTables_wrapper .dataTables_info { color: rgb(100 116 139); font-size: 12px; padding-top: 10px; }
      .dataTables_wrapper .dataTables_paginate { padding-top: 10px; }
      .dataTables_wrapper .dataTables_paginate .paginate_button { border: 1px solid rgb(226 232 240) !important; border-radius: 12px; padding: 6px 10px !important; margin-left: 6px; }
      .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: rgb(15 23 42) !important; color: #fff !important; border-color: rgb(15 23 42) !important; }
      .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: rgb(241 245 249) !important; color: rgb(15 23 42) !important; }
    </style>
</head>
<body class="app-bg text-slate-900" id="top">
    <div class="min-h-screen lg:pl-64 flex flex-col">
        <?php include("menu.php"); ?>
        <?php include("ustmenu.php"); ?>

        <main class="w-full flex-1 px-4 py-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Tanımlamalar</div>
                    <div class="mt-1 text-2xl font-extrabold tracking-tight">Hesaplar ve Kartlar</div>
                    <div class="mt-1 text-sm text-slate-600">Kredi kartları, banka hesapları veya nakit cüzdanlarınızı yönetin.</div>
                </div>
                <?php if ($defaultAccount) { ?>
                    <div class="flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50/80 px-4 py-2.5 text-xs font-bold text-emerald-800 shadow-sm">
                        <i class="ti ti-star-filled text-emerald-600"></i>
                        <span>Varsayılan: <?php echo htmlspecialchars((string)$defaultAccount["AccountName"], ENT_QUOTES, "UTF-8"); ?></span>
                    </div>
                <?php } ?>
            </div>

            <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                <?php if ($action !== "edit") { ?>
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Hesap Ekle</div>
                            <div class="mt-1 text-sm text-slate-600">Yeni kart, banka hesabı veya nakit cüzdan tanımlayın</div>
                        </div>
                        <div class="grid h-11 w-11 place-items-center rounded-2xl bg-slate-50 text-slate-700">
                            <i class="ti ti-credit-card"></i>
                        </div>
                    </div>

                    <form id="hesapEkleForm" class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Hesap / Kart Adı</label>
                            <input type="text" name="AccountName" placeholder="Örn: Garanti Bonus, İş Bankası Maaş, Nakit" required
                                   class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-400">
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Hesap Türü</label>
                            <select name="AccountType" required class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-400">
                                <option value="Kredi Kartı" selected>Kredi Kartı</option>
                                <option value="Banka Kartı">Banka Kartı / Vadesiz</option>
                                <option value="Nakit">Nakit</option>
                                <option value="Diğer">Diğer</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Varsayılan Hesap</label>
                            <select name="IsDefault" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-400">
                                <option value="-1" selected>Hayır (-1)</option>
                                <option value="1">Evet, Varsayılan Yap (1)</option>
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Tanım / Not (Opsiyonel)</label>
                            <input type="text" name="Description" placeholder="Örn: Ay sonu ekstre kesim tarihi 15 olan kartım"
                                   class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-400">
                        </div>

                        <div class="sm:col-span-3">
                            <button class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-extrabold text-white shadow hover:bg-slate-800" type="submit">
                                <i class="ti ti-device-floppy"></i>Hesap Kaydet
                            </button>
                        </div>
                    </form>
                <?php } else { 
                    $editItem = $mevcutHesap["data"]["list"][0] ?? null;
                    if (!$editItem) { ?>
                        <div class="text-sm text-rose-600">Düzenlenecek hesap bulunamadı. <a href="hesaplar.php" class="underline">Geri Dön</a></div>
                    <?php } else { ?>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-amber-700">Hesap Düzenle</div>
                                <div class="mt-1 text-sm text-slate-600">"<?php echo htmlspecialchars((string)$editItem["AccountName"], ENT_QUOTES, "UTF-8"); ?>" bilgilerini güncelle</div>
                            </div>
                            <div class="grid h-11 w-11 place-items-center rounded-2xl bg-amber-50 text-amber-700">
                                <i class="ti ti-edit"></i>
                            </div>
                        </div>

                        <form id="hesapGuncelleForm" class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <input type="hidden" name="AccountId" value="<?php echo htmlspecialchars((string)$editItem["AccountId"], ENT_QUOTES, "UTF-8"); ?>">
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Hesap / Kart Adı</label>
                                <input type="text" name="AccountName" value="<?php echo htmlspecialchars((string)$editItem["AccountName"], ENT_QUOTES, "UTF-8"); ?>" required
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-400">
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Hesap Türü</label>
                                <select name="AccountType" required class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-400">
                                    <option value="Kredi Kartı" <?php if ($editItem["AccountType"] === "Kredi Kartı") { ?>selected<?php } ?>>Kredi Kartı</option>
                                    <option value="Banka Kartı" <?php if ($editItem["AccountType"] === "Banka Kartı") { ?>selected<?php } ?>>Banka Kartı / Vadesiz</option>
                                    <option value="Nakit" <?php if ($editItem["AccountType"] === "Nakit") { ?>selected<?php } ?>>Nakit</option>
                                    <option value="Diğer" <?php if ($editItem["AccountType"] === "Diğer") { ?>selected<?php } ?>>Diğer</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Varsayılan Hesap</label>
                                <select name="IsDefault" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-400">
                                    <option value="-1" <?php if ((int)$editItem["IsDefault"] !== 1) { ?>selected<?php } ?>>Hayır (-1)</option>
                                    <option value="1" <?php if ((int)$editItem["IsDefault"] === 1) { ?>selected<?php } ?>>Evet, Varsayılan Yap (1)</option>
                                </select>
                            </div>
                            <div class="sm:col-span-3">
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Tanım / Not (Opsiyonel)</label>
                                <input type="text" name="Description" value="<?php echo htmlspecialchars((string)($editItem["Description"] ?? ""), ENT_QUOTES, "UTF-8"); ?>"
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-400">
                            </div>

                            <div class="sm:col-span-3 flex gap-3">
                                <button class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-extrabold text-white shadow hover:bg-slate-800" type="submit">
                                    <i class="ti ti-device-floppy"></i>Değişiklikleri Kaydet
                                </button>
                                <a href="hesaplar.php" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Vazgeç</a>
                            </div>
                        </form>
                    <?php } ?>
                <?php } ?>
            </section>

            <section class="mt-6 rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Kayıtlı Hesaplar</div>
                        <div class="mt-1 text-sm text-slate-600">Toplam <?php echo $hesapCount; ?> hesap/kart kayıtlı</div>
                    </div>
                    <?php if ($action === "edit") { ?>
                        <a href="hesaplar.php" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <i class="ti ti-plus text-slate-400"></i>Yeni Hesap Ekle
                        </a>
                    <?php } ?>
                </div>

                <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-3">
                    <table class="min-w-full text-sm" id="hesaplarTablosu" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="w-12 text-center">Durum</th>
                                <th>Hesap / Kart Adı</th>
                                <th>Tür</th>
                                <th>Açıklama / Tanım</th>
                                <th class="text-right">Gider Toplamı</th>
                                <th class="text-right">Gelir Toplamı</th>
                                <th class="text-center w-28">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hesapList as $item) { 
                                $isDef = ((int)($item["IsDefault"] ?? -1) === 1);
                                $accId = (int)$item["AccountId"];
                                $typeBadgeCls = "bg-slate-50 border-slate-200 text-slate-700";
                                $typeIcon = "ti ti-credit-card";
                                if ($item["AccountType"] === "Nakit") {
                                    $typeBadgeCls = "bg-amber-50 border-amber-200 text-amber-800";
                                    $typeIcon = "ti ti-cash";
                                } elseif ($item["AccountType"] === "Banka Kartı") {
                                    $typeBadgeCls = "bg-blue-50 border-blue-200 text-blue-800";
                                    $typeIcon = "ti ti-building-bank";
                                }
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <?php if ($isDef) { ?>
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700" title="Varsayılan Hesap">
                                                <i class="ti ti-star-filled text-sm"></i>
                                            </span>
                                        <?php } else { ?>
                                            <button type="button" class="setDefaultBtn inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 text-slate-400 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-600"
                                                    data-account-id="<?php echo $accId; ?>" title="Varsayılan Yap">
                                                <i class="ti ti-star text-sm"></i>
                                            </button>
                                        <?php } ?>
                                    </td>
                                    <td class="font-bold text-slate-900">
                                        <div class="flex items-center gap-2">
                                            <span><?php echo htmlspecialchars((string)$item["AccountName"], ENT_QUOTES, "UTF-8"); ?></span>
                                            <?php if ($isDef) { ?>
                                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold text-emerald-700 border border-emerald-200">Varsayılan</span>
                                            <?php } ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center gap-1 rounded-xl border px-2.5 py-1 text-xs font-semibold <?php echo $typeBadgeCls; ?>">
                                            <i class="<?php echo $typeIcon; ?>"></i>
                                            <?php echo htmlspecialchars((string)$item["AccountType"], ENT_QUOTES, "UTF-8"); ?>
                                        </span>
                                    </td>
                                    <td class="text-slate-600 max-w-xs truncate" title="<?php echo htmlspecialchars((string)($item["Description"] ?? ""), ENT_QUOTES, "UTF-8"); ?>">
                                        <?php echo htmlspecialchars((string)($item["Description"] ?? "-"), ENT_QUOTES, "UTF-8"); ?>
                                    </td>
                                    <td class="text-right font-bold text-rose-700">
                                        <?php echo paraSpan($item["TotalExpense"]); ?>
                                    </td>
                                    <td class="text-right font-bold text-emerald-700">
                                        <?php echo paraSpan($item["TotalIncome"]); ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="inline-flex items-center gap-1">
                                            <a href="hesaplar.php?action=edit&ID=<?php echo $accId; ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50" title="Düzenle">
                                                <i class="ti ti-edit text-sm"></i>
                                            </a>
                                            <button type="button" class="deleteAccountBtn inline-flex h-8 w-8 items-center justify-center rounded-xl bg-rose-600 text-white hover:bg-rose-700" 
                                                    data-account-id="<?php echo $accId; ?>" data-account-name="<?php echo htmlspecialchars((string)$item["AccountName"], ENT_QUOTES, "UTF-8"); ?>" title="Sil">
                                                <i class="ti ti-trash text-sm"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <?php include("footer.php"); ?>
    </div>

    <?php include("scripts.php"); ?>
    <script>
        $(document).ready(function() {
            $('#hesaplarTablosu').DataTable({
                ordering: false,
                pageLength: 50,
                lengthMenu: [25, 50, 100],
                language: {
                    search: "Ara:",
                    lengthMenu: "_MENU_ kayıt göster",
                    info: "_TOTAL_ kayıttan _START_ - _END_ arası gösteriliyor",
                    infoEmpty: "Kayıt yok",
                    zeroRecords: "Eşleşen hesap bulunamadı",
                    paginate: { first: "İlk", last: "Son", next: "Sonraki", previous: "Önceki" }
                }
            });

            $('#hesapEkleForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append("yeniHesap", true);

                $.ajax({
                    url: 'hesaplar.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            var yanit = (typeof response === "object") ? response : JSON.parse(response);
                            if (yanit.code === 200) {
                                toastr.success('Hesap başarıyla kaydedildi.');
                                setTimeout(function() { window.location.href = 'hesaplar.php'; }, 700);
                            } else {
                                toastr.error('Hata: ' + (yanit.message || "Bilinmeyen hata"));
                            }
                        } catch (err) {
                            toastr.error('İşlem tamamlanırken hata oluştu.');
                        }
                    },
                    error: function(xhr, status, error) {
                        toastr.error('Sunucu hatası: ' + error);
                    }
                });
            });

            $('#hesapGuncelleForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append("hesapGuncelle", true);

                $.ajax({
                    url: 'hesaplar.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            var yanit = (typeof response === "object") ? response : JSON.parse(response);
                            if (yanit.code === 200) {
                                toastr.success('Hesap başarıyla güncellendi.');
                                setTimeout(function() { window.location.href = 'hesaplar.php'; }, 700);
                            } else {
                                toastr.error('Hata: ' + (yanit.message || "Bilinmeyen hata"));
                            }
                        } catch (err) {
                            toastr.error('İşlem tamamlanırken hata oluştu.');
                        }
                    },
                    error: function(xhr, status, error) {
                        toastr.error('Sunucu hatası: ' + error);
                    }
                });
            });

            // Tek tıkla varsayılan yap
            $(document).on('click', '.setDefaultBtn', function() {
                var accountId = $(this).data('account-id');
                var fd = new FormData();
                fd.append("AccountId", accountId);
                fd.append("IsDefault", "1");
                fd.append("hesapGuncelle", true);

                $.ajax({
                    url: 'hesaplar.php',
                    type: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        toastr.success('Varsayılan hesap güncellendi.');
                        setTimeout(function() { window.location.reload(); }, 600);
                    },
                    error: function() {
                        toastr.error('Varsayılan hesap güncellenemedi.');
                    }
                });
            });

            // Hesap sil
            $(document).on('click', '.deleteAccountBtn', function() {
                var accountId = $(this).data('account-id');
                var accountName = $(this).data('account-name');

                Swal.fire({
                    title: 'Hesabı Sil?',
                    text: '"' + accountName + '" hesabını silmek istediğinize emin misiniz? (Bağlı hareketler silinmez, hesap alanları boş kalır)',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Evet, Sil',
                    cancelButtonText: 'Vazgeç',
                    confirmButtonColor: '#e11d48'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        var fd = new FormData();
                        fd.append("AccountId", accountId);
                        fd.append("hesapSil", true);

                        $.ajax({
                            url: 'hesaplar.php',
                            type: 'POST',
                            data: fd,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                toastr.success('Hesap başarıyla silindi.');
                                setTimeout(function() { window.location.reload(); }, 700);
                            },
                            error: function() {
                                toastr.error('Hesap silinirken hata oluştu.');
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>

