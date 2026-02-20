<?php 
include("fonksiyonlar.php");

$gelirKategorileri = apiRequest('/kategoriler', 'GET', ["Type" => 1, "orderkey" => "CategoryName", "ordertype" => "ASC"], $_SESSION['Api_Token']);
$giderKategorileri = apiRequest('/kategoriler', 'GET', ["Type" => 2, "orderkey" => "CategoryName", "ordertype" => "ASC"], $_SESSION['Api_Token']);

if(empty($_POST)) 
{
	$hareketturu = $_GET['type'] ?? false;
	$hareketID = $_GET['ID'] ?? false;

	if($hareketturu == "gelir") { $hareket = apiRequest('/gelirler', 'GET', ["AssetsId" => $hareketID], $_SESSION['Api_Token']); if($hareket["code"] != 200) { header("Location: index.php"); exit; } }
	elseif($hareketturu == "gider") { $hareket = apiRequest('/giderler', 'GET', ["BillsId" => $hareketID], $_SESSION['Api_Token']); if($hareket["code"] != 200) { header("Location: index.php"); exit; } }
	else { header("Location: index.php"); exit; }

	$movementType = ($hareketturu == "gelir") ? 1 : 2;
	$movementId = ($hareketturu == "gelir") ? (int)$hareket["data"]["list"][0]["AssetsId"] : (int)$hareket["data"]["list"][0]["BillsId"];
	$fotolar = apiRequest('/fotolar', 'GET', ["Type" => $movementType, "MovementId" => $movementId], $_SESSION['Api_Token']);
}

function uploadPhotosIfAny(int $type, int $movementId): array
{
	$uploaded = [];
	if (empty($_FILES) || empty($_FILES['Photos'])) return ["uploaded_count" => 0, "uploaded" => []];
	if ($movementId <= 0) return ["uploaded_count" => 0, "uploaded" => []];

	$files = $_FILES['Photos'];
	if (!is_array($files['name'] ?? null)) return ["uploaded_count" => 0, "uploaded" => []];

	$count = min(5, count($files['name']));
	for ($i = 0; $i < $count; $i++) {
		if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
		$tmp = $files['tmp_name'][$i] ?? null;
		if (!$tmp || !is_file($tmp)) continue;

		$cfile = new CURLFile($tmp, $files['type'][$i] ?? 'application/octet-stream', $files['name'][$i] ?? 'photo');
		$res = apiRequest('/fotolar', 'POST', [
			"Type" => $type,
			"MovementId" => $movementId,
			"Photo" => $cfile,
		], $_SESSION['Api_Token']);

		if (($res['code'] ?? 500) === 200) {
			$uploaded[] = $res['data']['uploaded'][0] ?? null;
		}
	}
	return ["uploaded_count" => count(array_filter($uploaded)), "uploaded" => array_values(array_filter($uploaded))];
}

if (isset($_POST['fotoEkle'])) {
	$type = (int)($_POST['Type'] ?? 0);
	$movementId = (int)($_POST['MovementId'] ?? 0);
	$res = ["code" => 400, "message" => "Eksik parametre"];
	if (in_array($type, [1,2], true) && $movementId > 0) {
		$photos = uploadPhotosIfAny($type, $movementId);
		$res = ["code" => 200, "message" => "Fotoğraflar yüklendi.", "data" => $photos];
	}
	echo json_encode($res, JSON_UNESCAPED_UNICODE);
	exit;
}

if (isset($_POST['fotoSil'])) {
	$PhotoId = $_POST['PhotoId'] ?? null;
	$resp = apiRequest('/fotolar', 'DELETE', ["PhotoId" => $PhotoId], $_SESSION['Api_Token']);
	echo json_encode($resp, JSON_UNESCAPED_UNICODE);
	exit;
}

if (isset($_POST['gelirSil'])) {
    $gelirSil = apiRequest('/gelirler', 'DELETE', $_POST, $_SESSION['Api_Token']);
    echo json_encode($gelirSil, JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($_POST['giderSil'])) {
    $giderSil = apiRequest('/giderler', 'DELETE', $_POST, $_SESSION['Api_Token']);
    echo json_encode($giderSil, JSON_UNESCAPED_UNICODE);
    exit;
}

if(isset($_POST['gelirGuncelle'])) 
{
	$gelirGuncelle = apiRequest('/gelirler', 'PATCH', $_POST, $_SESSION['Api_Token']);
	echo json_encode($gelirGuncelle, JSON_UNESCAPED_UNICODE);
	exit;
}

if(isset($_POST['giderGuncelle'])) 
{
	$giderGuncelle = apiRequest('/giderler', 'PATCH', $_POST, $_SESSION['Api_Token']);
	echo json_encode($giderGuncelle, JSON_UNESCAPED_UNICODE);
	exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Hareket Düzenle</title>
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
            <div class="mb-6">
                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">İşlemler</div>
                <div class="mt-1 text-2xl font-extrabold tracking-tight">Hareket Düzenle</div>
            </div>

            <?php if($hareket["code"] == 200 && $hareketturu == "gelir") { ?>
                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-emerald-700">Gelir</div>
                            <div class="mt-1 text-sm text-slate-600">Gelir kaydını güncelle veya sil</div>
                        </div>
                        <button class="deleteAssets inline-flex items-center gap-2 rounded-2xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white shadow hover:bg-rose-700"
                                data-AssetsId="<?php echo encrypt($hareket["data"]["list"][0]["AssetsId"]); ?>">
                            <i class="ti ti-trash"></i>
                            Sil
                        </button>
                    </div>

                    <form id="gelirForm" class="mt-5 space-y-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Başlık</label>
                                <input type="text" name="Title" value="<?php echo $hareket["data"]["list"][0]["Title"]; ?>"
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Tutar</label>
                                <input type="number" step="0.01" min="0" name="Amount" value="<?php echo $hareket["data"]["list"][0]["Amount"]; ?>"
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Kategori</label>
                                <select name="CategoryId" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                                    <option value="">Bir kategori seçin</option>
                                    <?php foreach($gelirKategorileri["data"]["list"] as $gelirKategorisi) { ?>
                                        <option value="<?php echo $gelirKategorisi["CategoryId"]; ?>" <?php if($gelirKategorisi["CategoryId"] == $hareket["data"]["list"][0]["CategoryId"]) { ?>selected<?php } ?>>
                                            <?php echo $gelirKategorisi["CategoryName"]; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Tarih</label>
                                <input type="datetime-local" name="Date" value="<?php echo $hareket["data"]["list"][0]["Date"]; ?>"
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                            </div>
                        </div>

                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Açıklama</label>
                            <textarea rows="3" name="Description"
                                      class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300"><?php echo $hareket["data"]["list"][0]["Description"]; ?></textarea>
                        </div>

                        <input type="hidden" name="AssetsId" value="<?php echo encrypt($hareket["data"]["list"][0]["AssetsId"]); ?>">
                        <button class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-extrabold text-white shadow hover:bg-emerald-700" type="submit">
                            <i class="ti ti-device-floppy"></i>
                            Gelir Kaydını Güncelle
                        </button>
                    </form>

                    <div class="mt-6">
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Fotoğraflar</div>
                            <div class="text-xs text-slate-500"><?php echo (int)($fotolar["data"]["count"] ?? 0); ?> adet</div>
                        </div>

                        <form id="fotoFormGelir" class="mt-3">
                            <input type="hidden" name="fotoEkle" value="1">
                            <input type="hidden" name="Type" value="1">
                            <input type="hidden" name="MovementId" value="<?php echo (int)$hareket["data"]["list"][0]["AssetsId"]; ?>">
                            <div class="mt-2" data-photo-uploader>
                                <input type="file" name="Photos[]" multiple accept="image/*,application/pdf" data-photo-input
                                       class="block w-full text-sm file:mr-3 file:rounded-xl file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800">
                                <div class="mt-2 text-xs text-slate-500" data-photo-hint>Dosya seçilmedi</div>
                                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3" data-photo-previews></div>
                            </div>
                            <button type="submit" class="mt-3 inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                <i class="ti ti-upload"></i>Fotoğraf Yükle
                            </button>
                        </form>

                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                            <?php foreach(($fotolar["data"]["list"] ?? []) as $f) { ?>
                                <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    <?php if (!empty($f["is_image"])) { ?>
                                        <button type="button" class="block w-full" data-lightbox-src="<?php echo htmlspecialchars($f["url"] ?? "", ENT_QUOTES, "UTF-8"); ?>">
                                            <img src="<?php echo htmlspecialchars($f["url"] ?? "", ENT_QUOTES, "UTF-8"); ?>" class="h-24 w-full object-cover" alt="">
                                        </button>
                                    <?php } else { ?>
                                        <?php $isPdf = !empty($f["is_pdf"]); $label = $isPdf ? "PDF" : "Dosya"; $icon = $isPdf ? "ti ti-file-type-pdf text-rose-600" : "ti ti-file text-slate-600"; ?>
                                        <a class="flex h-24 w-full items-center justify-center gap-2 bg-slate-50 text-sm font-extrabold text-slate-700" href="<?php echo htmlspecialchars($f["url"] ?? "", ENT_QUOTES, "UTF-8"); ?>" target="_blank" rel="noreferrer">
                                            <i class="<?php echo $icon; ?>"></i><?php echo $label; ?>
                                        </a>
                                    <?php } ?>
                                    <button type="button" class="fotoSil absolute right-2 top-2 hidden rounded-xl bg-rose-600 px-2 py-1 text-xs font-semibold text-white group-hover:block"
                                            data-photoid="<?php echo htmlspecialchars($f["PhotoId"] ?? "", ENT_QUOTES, "UTF-8"); ?>">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </section>
            <?php } ?>

            <?php if($hareket["code"] == 200 && $hareketturu == "gider") { ?>
                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-rose-700">Gider</div>
                            <div class="mt-1 text-sm text-slate-600">Gider kaydını güncelle veya sil</div>
                        </div>
                        <button class="deleteBills inline-flex items-center gap-2 rounded-2xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white shadow hover:bg-rose-700"
                                data-BillsId="<?php echo encrypt($hareket["data"]["list"][0]["BillsId"]); ?>">
                            <i class="ti ti-trash"></i>
                            Sil
                        </button>
                    </div>

                    <form id="giderForm" class="mt-5 space-y-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Başlık</label>
                                <input type="text" name="Title" value="<?php echo $hareket["data"]["list"][0]["Title"]; ?>"
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Tutar</label>
                                <input type="number" step="0.01" min="0" name="Amount" value="<?php echo $hareket["data"]["list"][0]["Amount"]; ?>"
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Kategori</label>
                                <select name="CategoryId" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                                    <option value="">Bir kategori seçin</option>
                                    <?php foreach($giderKategorileri["data"]["list"] as $giderKategorisi) { ?>
                                        <option value="<?php echo $giderKategorisi["CategoryId"]; ?>" <?php if($giderKategorisi["CategoryId"] == $hareket["data"]["list"][0]["CategoryId"]) { ?>selected<?php } ?>>
                                            <?php echo $giderKategorisi["CategoryName"]; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Tarih</label>
                                <input type="datetime-local" value="<?php echo $hareket["data"]["list"][0]["Date"]; ?>" name="Date"
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                            </div>
                        </div>

                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Açıklama</label>
                            <textarea rows="3" name="Description"
                                      class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300"><?php echo $hareket["data"]["list"][0]["Description"]; ?></textarea>
                        </div>

                        <input type="hidden" name="BillsId" value="<?php echo encrypt($hareket["data"]["list"][0]["BillsId"]); ?>">
                        <button class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-rose-600 px-4 py-3 text-sm font-extrabold text-white shadow hover:bg-rose-700" type="submit" id="yeniGider">
                            <i class="ti ti-device-floppy"></i>
                            Gider Kaydını Güncelle
                        </button>
                    </form>

                    <div class="mt-6">
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Fotoğraflar</div>
                            <div class="text-xs text-slate-500"><?php echo (int)($fotolar["data"]["count"] ?? 0); ?> adet</div>
                        </div>

                        <form id="fotoFormGider" class="mt-3">
                            <input type="hidden" name="fotoEkle" value="1">
                            <input type="hidden" name="Type" value="2">
                            <input type="hidden" name="MovementId" value="<?php echo (int)$hareket["data"]["list"][0]["BillsId"]; ?>">
                            <div class="mt-2" data-photo-uploader>
                                <input type="file" name="Photos[]" multiple accept="image/*,application/pdf" data-photo-input
                                       class="block w-full text-sm file:mr-3 file:rounded-xl file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800">
                                <div class="mt-2 text-xs text-slate-500" data-photo-hint>Dosya seçilmedi</div>
                                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3" data-photo-previews></div>
                            </div>
                            <button type="submit" class="mt-3 inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                <i class="ti ti-upload"></i>Fotoğraf Yükle
                            </button>
                        </form>

                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                            <?php foreach(($fotolar["data"]["list"] ?? []) as $f) { ?>
                                <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    <?php if (!empty($f["is_image"])) { ?>
                                        <button type="button" class="block w-full" data-lightbox-src="<?php echo htmlspecialchars($f["url"] ?? "", ENT_QUOTES, "UTF-8"); ?>">
                                            <img src="<?php echo htmlspecialchars($f["url"] ?? "", ENT_QUOTES, "UTF-8"); ?>" class="h-24 w-full object-cover" alt="">
                                        </button>
                                    <?php } else { ?>
                                        <?php $isPdf = !empty($f["is_pdf"]); $label = $isPdf ? "PDF" : "Dosya"; $icon = $isPdf ? "ti ti-file-type-pdf text-rose-600" : "ti ti-file text-slate-600"; ?>
                                        <a class="flex h-24 w-full items-center justify-center gap-2 bg-slate-50 text-sm font-extrabold text-slate-700" href="<?php echo htmlspecialchars($f["url"] ?? "", ENT_QUOTES, "UTF-8"); ?>" target="_blank" rel="noreferrer">
                                            <i class="<?php echo $icon; ?>"></i><?php echo $label; ?>
                                        </a>
                                    <?php } ?>
                                    <button type="button" class="fotoSil absolute right-2 top-2 hidden rounded-xl bg-rose-600 px-2 py-1 text-xs font-semibold text-white group-hover:block"
                                            data-photoid="<?php echo htmlspecialchars($f["PhotoId"] ?? "", ENT_QUOTES, "UTF-8"); ?>">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </section>
            <?php } ?>
        </main>

        <?php include("footer.php"); ?>
    </div>

    <?php include("scripts.php"); ?>

	<script>
		<?php if($hareketturu == "gelir") { ?>
		$(document).ready(function() 
		{
			$('#gelirForm').on('submit', function(e) 
			{
				e.preventDefault();
				var formData = new FormData(this);
				formData.append("gelirGuncelle", true);
				$.ajax({
					url: 'hareketduzenle.php',
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) 
					{
						var yanit = JSON.parse(response);
						console.log(response);
						if (yanit.code === 200) {
							toastr.success('Gelir başarıyla güncellendi.');
							
						} else {
							toastr.error('Hata: ' + yanit.message);
						}
					},
					error: function(xhr, status, error) {
						toastr.error('API isteği sırasında bir hata oluştu: ' + error);
					}
				});
			});
			
			$(document).on("click", ".deleteAssets", function() 
			{
				let AssetsId = $(this).data("assetsid");
				let button = $(this);

				Swal.fire({
					title: "Emin misiniz?",
					text: "Bu işlemi geri alamazsınız!",
					icon: "warning",
					showCancelButton: true,
					confirmButtonColor: "#d33",
					cancelButtonColor: "#3085d6",
					confirmButtonText: "Evet, sil!",
					cancelButtonText: "İptal"
				}).then((result) => {
					if (result.isConfirmed) {
						$.ajax({
							url: window.location.href,
							type: "POST",
							data: { gelirSil: true, AssetsId: AssetsId },
							success: function(response) 
							{
								response = JSON.parse(response);
								if (response.code === 200) {
									toastr.success(response.message);
									button.closest("tr").fadeOut();
									setTimeout(function() { window.location.href = "/index.php"; }, 2000);
								} else {
									toastr.error(response.message);
								}
							},
							error: function(xhr) {
								toastr.error("Bir hata oluştu: " + xhr.responseText);
							}
						});
					}
				});
			});
			
		});
		<?php } ?>
		
		<?php if($hareketturu == "gider") { ?>
		$(document).ready(function() 
		{
			$('#giderForm').on('submit', function(e) 
			{
				e.preventDefault();
				var formData = new FormData(this);
				formData.append("giderGuncelle", true);
				$.ajax({
					url: 'hareketduzenle.php',
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) 
					{
						var yanit = JSON.parse(response);
						if (yanit.code === 200) {
							toastr.success('Gider başarıyla güncellendi.');
						} else {
							toastr.error('Hata: ' + yanit.message);
						}
					},
					error: function(xhr, status, error) {
						toastr.error('API isteği sırasında bir hata oluştu: ' + error);
					}
				});
			});
			
			
			$(document).on("click", ".deleteBills", function() 
			{
				let BillsId = $(this).data("billsid");
				let button = $(this);

				Swal.fire({
					title: "Emin misiniz?",
					text: "Bu işlemi geri alamazsınız!",
					icon: "warning",
					showCancelButton: true,
					confirmButtonColor: "#d33",
					cancelButtonColor: "#3085d6",
					confirmButtonText: "Evet, sil!",
					cancelButtonText: "İptal"
				}).then((result) => {
					if (result.isConfirmed) {
						$.ajax({
							url: window.location.href,
							type: "POST",
							data: { giderSil: true, BillsId: BillsId },
							success: function(response) 
							{
								response = JSON.parse(response);
								if (response.code === 200) {
									toastr.success(response.message);
									setTimeout(function() { window.location.href = "/index.php"; }, 2000);
									button.closest("tr").fadeOut();
								} else {
									toastr.error(response.message);
								}
							},
							error: function(xhr) {
								toastr.error("Bir hata oluştu: " + xhr.responseText);
							}
						});
					}
				});
			});
			
		});
		<?php } ?>

			$(document).on("submit", "#fotoFormGelir, #fotoFormGider", function(e) {
				e.preventDefault();
				var formData = new FormData(this);
				window.uxShowUploading("Fotoğraflar yükleniyor...", "Dosyalar yüklenirken sayfayı kapatmayın.");
				$.ajax({
					url: window.location.href,
					type: "POST",
					data: formData,
					processData: false,
					contentType: false,
					success: function(resp) {
						window.uxHideUploading();
						try { resp = JSON.parse(resp); } catch (e) {}
						if (resp && resp.code === 200) {
							toastr.success("Fotoğraflar yüklendi.");
							setTimeout(function() { location.reload(); }, 700);
						} else {
							toastr.error(resp && resp.message ? resp.message : "Fotoğraf yüklenemedi.");
						}
					},
					error: function(xhr) {
						window.uxHideUploading();
						toastr.error("Bir hata oluştu: " + xhr.responseText);
					}
				});
			});

		$(document).on("click", ".fotoSil", function() {
			var PhotoId = $(this).data("photoid");
			Swal.fire({
				title: "Silinsin mi?",
				text: "Bu fotoğraf silinecek.",
				icon: "warning",
				showCancelButton: true,
				confirmButtonColor: "#d33",
				cancelButtonColor: "#3085d6",
				confirmButtonText: "Evet, sil!",
				cancelButtonText: "İptal"
			}).then((result) => {
				if (!result.isConfirmed) return;
				$.ajax({
					url: window.location.href,
					type: "POST",
					data: { fotoSil: true, PhotoId: PhotoId },
					success: function(resp) {
						resp = JSON.parse(resp);
						if (resp.code === 200) {
							toastr.success(resp.message || "Fotoğraf silindi.");
							setTimeout(function() { location.reload(); }, 600);
						} else {
							toastr.error(resp.message || "Silinemedi.");
						}
					},
					error: function(xhr) {
						toastr.error("Bir hata oluştu: " + xhr.responseText);
					}
				});
			});
		});
	</script>
</body>
</html>


