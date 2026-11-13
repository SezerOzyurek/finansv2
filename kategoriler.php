<?php 
include("fonksiyonlar.php");

if(empty($_POST)) 
{ 
	$type = $_GET['type'] ?? false;
	$action = $_GET['action'] ?? false;
	$CategoryId = $_GET['CategoryId'] ?? false;
	
	if($action == "edit") { $mevcutKategori = apiRequest('/kategoriler', 'GET', ["CategoryId" => $CategoryId, "orderkey" => "CategoryName", "ordertype" => "ASC"], $_SESSION['Api_Token']); }
	if($type == "1") { $kategoriler = apiRequest('/kategoriler', 'GET', ["Type" => 1, "orderkey" => "CategoryName", "ordertype" => "ASC"], $_SESSION['Api_Token']); }
	elseif($type == "2") { $kategoriler = apiRequest('/kategoriler', 'GET', ["Type" => 2, "orderkey" => "CategoryName", "ordertype" => "ASC"], $_SESSION['Api_Token']); }
	else { $kategoriler = apiRequest('/kategoriler', 'GET', ["Type" => 1, "orderkey" => "CategoryName", "ordertype" => "ASC"], $_SESSION['Api_Token']); }

	$kategoriData = (isset($kategoriler["data"]) && is_array($kategoriler["data"])) ? $kategoriler["data"] : [];
	$kategoriCount = isset($kategoriData["count"]) ? (int)$kategoriData["count"] : 0;
	$kategoriList = (isset($kategoriData["list"]) && is_array($kategoriData["list"])) ? $kategoriData["list"] : [];
}

if(isset($_POST['yeniKategori'])) 
{
	$yeniGelir = apiRequest('/kategoriler', 'POST', $_POST, $_SESSION['Api_Token']);
	echo json_encode($yeniGelir, JSON_UNESCAPED_UNICODE);
	exit;
} 

if(isset($_POST['kategoriGuncelle'])) 
{
	$yeniGelir = apiRequest('/kategoriler', 'PATCH', $_POST, $_SESSION['Api_Token']);
	echo json_encode($yeniGelir, JSON_UNESCAPED_UNICODE);
	exit;
} 


?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <title><?php if($type == 1) { ?>Gelir Kategorileri<?php } else { ?>Gider Kategorileri<?php } ?> - Ultra Finans</title>
    <?php include("head.php"); ?>
    <style>
      table.dataTable { border-collapse: collapse !important; table-layout: fixed !important; width: 100% !important; }
      table.dataTable thead th { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: rgb(100 116 139); background: rgb(248 250 252); white-space: nowrap; }
      table.dataTable thead th, table.dataTable tbody td { padding: 8px 12px !important; }
      table.dataTable tbody td { font-size: 13px; line-height: 1.25; border-top: 1px solid rgb(241 245 249); vertical-align: middle; white-space: nowrap; }
      table.dataTable tbody tr:hover td { background: rgb(248 250 252); }
      #kategoriler td:nth-child(2) { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
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
                        <i class="ti <?= ($type == 1) ? 'ti-folder-plus text-emerald-600 dark:text-emerald-400' : 'ti-folder-minus text-rose-600 dark:text-rose-400' ?>"></i>
                        <span><?php if($type == 1) { ?>Gelir Kategorileri<?php } else { ?>Gider Kategorileri<?php } ?></span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                        Hareketlerinizi gruplandırmak için kategorilerinizi düzenleyin ve yönetin.
                    </p>
                </div>
            </div>

            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <?php if(isset($action) && $action != "edit") { ?>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Yeni Kategori Ekle</div>
                            <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Kategori adını girip ekleyin</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                            <i class="ti ti-plus text-base"></i>
                        </div>
                    </div>

                    <form id="kategoriForm" class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Başlık</label>
                            <input type="text" name="CategoryName" placeholder="Kategori Adı" required
                                   class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-emerald-400">
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Tür</label>
                            <select name="Type" required class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-emerald-400">
                                <option value="1" <?php if ((int)$type === 1) { ?>selected<?php } ?>>Gelir Kategorisi</option>
                                <option value="2" <?php if ((int)$type === 2) { ?>selected<?php } ?>>Gider Kategorisi</option>
                            </select>
                        </div>

                        <div class="sm:col-span-3">
                            <button class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-extrabold text-white shadow-xs transition hover:bg-emerald-500 active:scale-[0.99] dark:bg-emerald-600 dark:hover:bg-emerald-500" type="submit">
                                <i class="ti ti-device-floppy"></i>Yeni Kategori Ekle
                            </button>
                        </div>
                    </form>
                <?php } else { ?>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-amber-500 dark:text-amber-400">Kategori Düzenle</div>
                            <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Mevcut kategoriyi güncelleyin</div>
                        </div>
                        <div class="grid h-10 w-10 place-items-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400">
                            <i class="ti ti-edit text-base"></i>
                        </div>
                    </div>

                    <form id="kategoriGuncelle" class="mt-5 space-y-3">
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Başlık</label>
                            <input type="text" name="CategoryName" placeholder="Başlık" value="<?php echo $mevcutKategori["data"]["list"][0]["CategoryName"]; ?>" required
                                   class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder-slate-400 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-amber-400">
                        </div>
                        <input type="hidden" name="CategoryId" value="<?php echo encrypt($mevcutKategori["data"]["list"][0]["CategoryId"]); ?>">
                        <button class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-amber-600 px-4 py-3 text-sm font-extrabold text-white shadow-xs transition hover:bg-amber-500 active:scale-[0.99]" type="submit">
                            <i class="ti ti-device-floppy"></i>Kategoriyi Düzenle
                        </button>
                    </form>
                <?php } ?>
            </section>

            <?php if(!$action && $action != "edit") { ?>
                <section class="mt-6 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div>
                            <?php if($type == 1) { ?>
                                <div class="text-xs font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Gelir Kategorileri</div>
                            <?php } else { ?>
                                <div class="text-xs font-semibold uppercase tracking-widest text-rose-600 dark:text-rose-400">Gider Kategorileri</div>
                            <?php } ?>
                            <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"><?php echo $kategoriCount; ?> kategori listeleniyor</div>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-100 bg-slate-50/50 p-2 sm:p-3 dark:border-slate-800 dark:bg-slate-950/50">
                        <table class="w-full text-sm" id="kategoriler" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="w-32 text-left">Hareket</th>
                                    <th class="text-left">Kategori</th>
                                    <th class="w-48 text-right">Toplam Meblağ</th>
                                    <th class="w-24 text-center">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($kategoriList AS $kategori) { ?>
                                <tr>
                                    <td class="w-32 text-left">
                                        <?php if($kategori["Type"] == 1) { ?>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400"><?php echo $kategori["Total_Count"]; ?> Hareket</span>
                                        <?php } else { ?>
                                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-950/40 dark:text-rose-400"><?php echo $kategori["Total_Count"]; ?> Hareket</span>
                                        <?php } ?>
                                    </td>
                                    <td class="text-left">
                                        <a class="font-semibold text-slate-900 dark:text-slate-100 hover:text-emerald-600 dark:hover:text-emerald-400 hover:underline" href="hareketler.php?type=<?php echo $type; ?>&CategoryId=<?php echo $kategori["CategoryId"]; ?>">
                                            <?php echo htmlspecialchars((string)$kategori["CategoryName"], ENT_QUOTES, "UTF-8"); ?>
                                        </a>
                                    </td>
                                    <td class="w-48 text-right font-extrabold"><?php echo paraSpan($kategori["Total_Amount"]); ?></td>
                                    <td class="w-24 text-center">
                                        <a class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                           href="kategoriler.php?action=edit&CategoryId=<?php echo $kategori["CategoryId"]; ?>" title="Düzenle">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php } ?>
        </main>

        <?php include("footer.php"); ?>
    </div>
   
	<?php include("scripts.php"); ?>
	<script>
		$(document).ready(function () 
		{
			$('#kategoriler').DataTable({
				ordering: false,
				pageLength: 50,
				lengthMenu: [25, 50, 100, 250],
				searchDelay: 250,
				autoWidth: false,
				columns: [
					{ width: "120px", className: "text-left" },
					{ className: "text-left" },
					{ width: "180px", className: "text-right" },
					{ width: "90px", className: "text-center" }
				]
			});
			
			$('#kategoriForm').on('submit', function(e) {
				e.preventDefault();
				var formData = new FormData(this);
				var kategoriTuru = formData.get("Type");
				formData.append("yeniKategori", true);
				$.ajax({
					url: 'kategoriler.php',
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) 
					{
						var yanit = JSON.parse(response);
						if (yanit.code === 200) {
							window.location.href = 'kategoriler.php?type='+kategoriTuru;
						} else {
							toastr.error('Hata: ' + yanit.message);
						}
					},
					error: function(xhr, status, error) {
						toastr.error('API isteği sırasında bir hata oluştu: ' + error);
					}
				});
			});
			
			$('#kategoriGuncelle').on('submit', function(e) {
				e.preventDefault();
				var formData = new FormData(this);
				formData.append("kategoriGuncelle", true);
				$.ajax({
					url: 'kategoriler.php',
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) 
					{
						var yanit = JSON.parse(response);
						if (yanit.code === 200) {
							toastr.success('Kategori başarıyla güncellendi.');
						} else {
							toastr.error('Hata: ' + yanit.message);
						}
					},
					error: function(xhr, status, error) {
						toastr.error('API isteği sırasında bir hata oluştu: ' + error);
					}
				});
			});
			
		});
	</script>
</body>
</html>


