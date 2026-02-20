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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Gelir Kategorileri</title>
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
      table.dataTable thead th, table.dataTable tbody td { padding: 5px 8px !important; }
      table.dataTable tbody td { font-size: 12px; line-height: 1.15; border-top: 1px solid rgb(241 245 249); vertical-align: middle; white-space: nowrap; }
      table.dataTable tbody tr:hover td { background: rgb(248 250 252); }
      #kategoriler td:nth-child(2) { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
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
            <div class="mb-6">
                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Kategoriler</div>
                <div class="mt-1 text-2xl font-extrabold tracking-tight">
                    <?php if($type == 1) { ?>Gelir Kategorileri<?php } else { ?>Gider Kategorileri<?php } ?>
                </div>
            </div>

            <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                <?php if(isset($action) && $action != "edit") { ?>
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Kategori Ekle</div>
                            <div class="mt-1 text-sm text-slate-600">Yeni kategori oluştur</div>
                        </div>
                            <div class="grid h-11 w-11 place-items-center rounded-2xl bg-slate-50 text-slate-700">
                            <i class="ti ti-plus"></i>
                            </div>
                        </div>

                    <form id="kategoriForm" class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Başlık</label>
                            <input type="text" name="CategoryName" placeholder="Başlık" required
                                   class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Tür</label>
                            <select name="Type" required class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                                <option value="1" selected>Gelir Kategorisi</option>
                                <option value="2">Gider Kategorisi</option>
                            </select>
                        </div>

                        <div class="sm:col-span-3">
                            <button class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-extrabold text-white shadow hover:bg-slate-800" type="submit">
                                <i class="ti ti-device-floppy"></i>Yeni Kategori Ekle
                            </button>
                        </div>
                    </form>
                <?php } else { ?>
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Kategori Düzenle</div>
                            <div class="mt-1 text-sm text-slate-600">Mevcut kategoriyi güncelle</div>
                        </div>
                        <div class="grid h-11 w-11 place-items-center rounded-2xl bg-amber-50 text-amber-700">
                            <i class="ti ti-edit"></i>
                        </div>
                    </div>

                    <form id="kategoriGuncelle" class="mt-5 space-y-3">
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Başlık</label>
                            <input type="text" name="CategoryName" placeholder="Başlık" value="<?php echo $mevcutKategori["data"]["list"][0]["CategoryName"]; ?>" required
                                   class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                        </div>
                        <input type="hidden" name="CategoryId" value="<?php echo encrypt($mevcutKategori["data"]["list"][0]["CategoryId"]); ?>">
                        <button class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-extrabold text-white shadow hover:bg-slate-800" type="submit">
                            <i class="ti ti-device-floppy"></i>Kategoriyi Düzenle
                        </button>
                    </form>
                <?php } ?>
            </section>

            <?php if(!$action && $action != "edit") { ?>
                <section class="mt-6 rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <?php if($type == 1) { ?>
                                <div class="text-xs font-semibold uppercase tracking-widest text-emerald-700">Gelir Kategorileri</div>
                            <?php } else { ?>
                                <div class="text-xs font-semibold uppercase tracking-widest text-rose-700">Gider Kategorileri</div>
                            <?php } ?>
                            <div class="mt-1 text-sm text-slate-600"><?php echo $kategoriCount; ?> kategori</div>
                        </div>
                        <a href="kategoriler.php?action=add&type=<?php echo (int)$type; ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <i class="ti ti-plus text-slate-400"></i>Yeni
                        </a>
                    </div>

                    <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-3">
                        <table class="min-w-full text-sm" id="kategoriler" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Hareket</th>
                                    <th>Kategori</th>
                                    <th class="text-right">Toplam Meblağ</th>
                                    <th class="text-center">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($kategoriList AS $kategori) { ?>
                                <tr>
                                    <td>
                                        <?php if($kategori["Type"] == 1) { ?>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"><?php echo $kategori["Total_Count"]; ?> Hareket</span>
                                        <?php } else { ?>
                                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700"><?php echo $kategori["Total_Count"]; ?> Hareket</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <a class="font-semibold text-slate-900 hover:underline" href="hareketler.php?type=<?php echo $type; ?>&CategoryId=<?php echo $kategori["CategoryId"]; ?>">
                                            <?php echo $kategori["CategoryName"]; ?>
                                        </a>
                                    </td>
                                    <td class="text-right font-extrabold"><?php echo para($kategori["Total_Amount"]); ?> ₺</td>
                                    <td class="text-center">
                                        <a class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 hover:bg-slate-50"
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
				searchDelay: 250
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


