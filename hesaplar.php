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
    <title>Hesaplar ve Kartlar - Ultra Finans</title>
    <?php include("head.php"); ?>
    <style>
      table.dataTable { border-collapse: collapse !important; table-layout: fixed !important; width: 100% !important; }
      table.dataTable thead th { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: rgb(100 116 139); background: rgb(248 250 252); white-space: nowrap; }
      table.dataTable thead th, table.dataTable tbody td { padding: 8px 12px !important; }
      table.dataTable tbody td { font-size: 13px; line-height: 1.25; border-top: 1px solid rgb(241 245 249); vertical-align: middle; }
      table.dataTable tbody tr:hover td { background: rgb(248 250 252); }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100" id="top">
    <div class="min-h-screen lg:pl-64 flex flex-col">
        <?php include("menu.php"); ?>
        <?php include("ustmenu.php"); ?>

        <main class="w-full flex-1 px-4 py-6 lg:px-8 space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="ti ti-credit-card text-emerald-600 dark:text-emerald-400"></i>
                        <span>Hesaplar & Kartlar</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                        Kredi kartları, banka hesapları ve nakit cüzdanlarınızı tek yerden yönetin.
                    </p>
                </div>
                <?php if ($defaultAccount) { ?>
                    <div class="inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50/90 px-3.5 py-2 text-xs font-bold text-emerald-800 shadow-xs dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300">
                        <i class="ti ti-star-filled text-emerald-600 dark:text-emerald-400"></i>
                        <span>Varsayılan: <?php echo htmlspecialchars((string)$defaultAccount["AccountName"], ENT_QUOTES, "UTF-8"); ?></span>
                    </div>
                <?php } ?>
            </div>

            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <?php if ($action !== "edit") { ?>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Yeni Hesap / Kart Tanımla</div>
                            <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Kredi kartı, banka hesabı veya nakit cüzdan ekleyin</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                            <i class="ti ti-plus text-base"></i>
                        </div>
                    </div>

                    <form id="hesapEkleForm" class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Hesap / Kart Adı</label>
                            <input type="text" name="AccountName" placeholder="Örn: Garanti Bonus, İş Bankası Maaş, Nakit" required
                                   class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-emerald-400">
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Hesap Türü</label>
                            <select name="AccountType" required class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-emerald-400">
                                <option value="Kredi Kartı" selected>Kredi Kartı</option>
                                <option value="Banka Kartı">Banka Kartı / Vadesiz</option>
                                <option value="Nakit">Nakit</option>
                                <option value="Diğer">Diğer</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Varsayılan Hesap</label>
                            <select name="IsDefault" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-emerald-400">
                                <option value="-1" selected>Hayır (-1)</option>
                                <option value="1">Evet, Varsayılan Yap (1)</option>
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Tanım / Not (Opsiyonel)</label>
                            <input type="text" name="Description" placeholder="Örn: Ay sonu ekstre kesim tarihi 15 olan kartım"
                                   class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-emerald-400">
                        </div>

                        <div class="sm:col-span-3">
                            <button class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-extrabold text-white shadow-xs transition hover:bg-emerald-500 active:scale-[0.99] dark:bg-emerald-600 dark:hover:bg-emerald-500" type="submit">
                                <i class="ti ti-device-floppy"></i>Hesap Kaydet
                            </button>
                        </div>
                    </form>
                <?php } else { 
                    $editItem = $mevcutHesap["data"]["list"][0] ?? null;
                    if (!$editItem) { ?>
                        <div class="text-sm text-rose-600">Düzenlenecek hesap bulunamadı. <a href="hesaplar.php" class="underline">Geri Dön</a></div>
                    <?php } else { ?>
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-amber-500 dark:text-amber-400">Hesap Düzenle</div>
                                <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">"<?php echo htmlspecialchars((string)$editItem["AccountName"], ENT_QUOTES, "UTF-8"); ?>" bilgilerini güncelle</div>
                            </div>
                            <div class="grid h-10 w-10 place-items-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400">
                                <i class="ti ti-edit text-base"></i>
                            </div>
                        </div>

                        <form id="hesapGuncelleForm" class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <input type="hidden" name="AccountId" value="<?php echo htmlspecialchars((string)$editItem["AccountId"], ENT_QUOTES, "UTF-8"); ?>">
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Hesap / Kart Adı</label>
                                <input type="text" name="AccountName" value="<?php echo htmlspecialchars((string)$editItem["AccountName"], ENT_QUOTES, "UTF-8"); ?>" required
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder-slate-400 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-amber-400">
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Hesap Türü</label>
                                <select name="AccountType" required class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-amber-400">
                                    <option value="Kredi Kartı" <?php if ($editItem["AccountType"] === "Kredi Kartı") { ?>selected<?php } ?>>Kredi Kartı</option>
                                    <option value="Banka Kartı" <?php if ($editItem["AccountType"] === "Banka Kartı") { ?>selected<?php } ?>>Banka Kartı / Vadesiz</option>
                                    <option value="Nakit" <?php if ($editItem["AccountType"] === "Nakit") { ?>selected<?php } ?>>Nakit</option>
                                    <option value="Diğer" <?php if ($editItem["AccountType"] === "Diğer") { ?>selected<?php } ?>>Diğer</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Varsayılan Hesap</label>
                                <select name="IsDefault" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-amber-400">
                                    <option value="-1" <?php if ((int)$editItem["IsDefault"] !== 1) { ?>selected<?php } ?>>Hayır (-1)</option>
                                    <option value="1" <?php if ((int)$editItem["IsDefault"] === 1) { ?>selected<?php } ?>>Evet, Varsayılan Yap (1)</option>
                                </select>
                            </div>
                            <div class="sm:col-span-3">
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Tanım / Not (Opsiyonel)</label>
                                <input type="text" name="Description" value="<?php echo htmlspecialchars((string)($editItem["Description"] ?? ""), ENT_QUOTES, "UTF-8"); ?>"
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder-slate-400 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-amber-400">
                            </div>

                            <div class="sm:col-span-3 flex gap-3">
                                <button class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-amber-600 px-4 py-3 text-sm font-extrabold text-white shadow-xs transition hover:bg-amber-500 active:scale-[0.99]" type="submit">
                                    <i class="ti ti-device-floppy"></i>Değişiklikleri Kaydet
                                </button>
                                <a href="hesaplar.php" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">Vazgeç</a>
                            </div>
                        </form>
                    <?php } ?>
                <?php } ?>
            </section>

            <section class="mt-6 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Kayıtlı Hesaplar & Kartlar</div>
                        <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Toplam <?php echo $hesapCount; ?> hesap kayıtlı</div>
                    </div>
                    <?php if ($action === "edit") { ?>
                        <a href="hesaplar.php" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-200">
                            <i class="ti ti-plus text-xs"></i><span>Yeni Hesap Ekle</span>
                        </a>
                    <?php } ?>
                </div>

                <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-100 bg-slate-50/50 p-2 sm:p-3 dark:border-slate-800 dark:bg-slate-950/50">
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
                                $typeBadgeCls = "bg-slate-50 border-slate-200 text-slate-700 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300";
                                $typeIcon = "ti ti-credit-card";
                                if ($item["AccountType"] === "Nakit") {
                                    $typeBadgeCls = "bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-950/40 dark:border-amber-800 dark:text-amber-300";
                                    $typeIcon = "ti ti-cash";
                                } elseif ($item["AccountType"] === "Banka Kartı") {
                                    $typeBadgeCls = "bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-950/40 dark:border-blue-800 dark:text-blue-300";
                                    $typeIcon = "ti ti-building-bank";
                                }
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <?php if ($isDef) { ?>
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400" title="Varsayılan Hesap">
                                                <i class="ti ti-star-filled text-sm"></i>
                                            </span>
                                        <?php } else { ?>
                                            <button type="button" class="setDefaultBtn inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 text-slate-400 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-600 dark:border-slate-700 dark:text-slate-500 dark:hover:border-emerald-700 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-400"
                                                    data-account-id="<?php echo $accId; ?>" title="Varsayılan Yap">
                                                <i class="ti ti-star text-sm"></i>
                                            </button>
                                        <?php } ?>
                                    </td>
                                    <td class="font-bold text-slate-900 dark:text-slate-100">
                                        <div class="flex items-center gap-2">
                                            <span><?php echo htmlspecialchars((string)$item["AccountName"], ENT_QUOTES, "UTF-8"); ?></span>
                                            <?php if ($isDef) { ?>
                                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/50 dark:border-emerald-800 dark:text-emerald-300">Varsayılan</span>
                                            <?php } ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center gap-1 rounded-xl border px-2.5 py-1 text-xs font-semibold <?php echo $typeBadgeCls; ?>">
                                            <i class="<?php echo $typeIcon; ?>"></i>
                                            <?php echo htmlspecialchars((string)$item["AccountType"], ENT_QUOTES, "UTF-8"); ?>
                                        </span>
                                    </td>
                                    <td class="text-slate-600 dark:text-slate-400 max-w-xs truncate" title="<?php echo htmlspecialchars((string)($item["Description"] ?? ""), ENT_QUOTES, "UTF-8"); ?>">
                                        <?php echo htmlspecialchars((string)($item["Description"] ?? "-"), ENT_QUOTES, "UTF-8"); ?>
                                    </td>
                                    <td class="text-right font-bold text-rose-700 dark:text-rose-400">
                                        <?php echo paraSpan($item["TotalExpense"]); ?>
                                    </td>
                                    <td class="text-right font-bold text-emerald-700 dark:text-emerald-400">
                                        <?php echo paraSpan($item["TotalIncome"]); ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="inline-flex items-center gap-1">
                                            <a href="hesaplar.php?action=edit&ID=<?php echo $accId; ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700" title="Düzenle">
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

