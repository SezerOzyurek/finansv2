<?php 
include("fonksiyonlar.php");

$gelirKategorileri = apiRequest('/kategoriler', 'GET', ["Type" => 1, "orderkey" => "CategoryName", "ordertype" => "ASC"], $_SESSION['Api_Token']);
$giderKategorileri = apiRequest('/kategoriler', 'GET', ["Type" => 2, "orderkey" => "CategoryName", "ordertype" => "ASC"], $_SESSION['Api_Token']);
$gelirKategoriList = (isset($gelirKategorileri["data"]["list"]) && is_array($gelirKategorileri["data"]["list"])) ? $gelirKategorileri["data"]["list"] : [];
$giderKategoriList = (isset($giderKategorileri["data"]["list"]) && is_array($giderKategorileri["data"]["list"])) ? $giderKategorileri["data"]["list"] : [];
$gelirKategoriCount = count($gelirKategoriList);
$giderKategoriCount = count($giderKategoriList);

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

if(isset($_POST['yeniGelir'])) 
{
	$yeniGelir = apiRequest('/gelirler', 'POST', $_POST, $_SESSION['Api_Token']);
	if (($yeniGelir["code"] ?? 500) === 200 && !empty($yeniGelir["data"]["AssetsId"])) {
		$photoRes = uploadPhotosIfAny(1, (int)$yeniGelir["data"]["AssetsId"]);
		$yeniGelir["data"]["photos"] = $photoRes;
	}
	echo json_encode($yeniGelir, JSON_UNESCAPED_UNICODE);
	exit;
}

if(isset($_POST['yeniGider'])) 
{
	$yeniGider = apiRequest('/giderler', 'POST', $_POST, $_SESSION['Api_Token']);
	if (($yeniGider["code"] ?? 500) === 200 && !empty($yeniGider["data"]["BillsId"])) {
		$photoRes = uploadPhotosIfAny(2, (int)$yeniGider["data"]["BillsId"]);
		$yeniGider["data"]["photos"] = $photoRes;
	}
	echo json_encode($yeniGider, JSON_UNESCAPED_UNICODE);
	exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Yeni Gelir/Gider Hareketi</title>
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
                <div class="mt-1 text-2xl font-extrabold tracking-tight">Yeni Gelir / Gider</div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-emerald-700">Gelir</div>
                                <div class="mt-1 text-sm text-slate-600">Yeni gelir hareketi ekle</div>
                            </div>
                            <div class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-50 text-emerald-700">
                            <i class="ti ti-wallet"></i>
                            </div>
                        </div>

                    <?php if ($gelirKategoriCount > 0) { ?>
                    <form id="gelirForm" class="mt-5 space-y-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Başlık</label>
                                <input type="text" name="Title" placeholder="Başlık"
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Tutar</label>
                                <input type="number" step="0.01" min="0" name="Amount" placeholder="Tutar"
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Kategori</label>
                                <select name="CategoryId" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                                    <option value="" selected>Bir kategori seçin</option>
                                    <?php foreach($gelirKategoriList as $gelirKategorisi) { ?>
                                        <option value="<?php echo $gelirKategorisi["CategoryId"]; ?>"><?php echo $gelirKategorisi["CategoryName"]; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Tarih</label>
                                <input type="datetime-local" value="<?php echo date("Y-m-d H:i"); ?>" name="Date"
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                            </div>
                        </div>

                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Açıklama</label>
                            <textarea rows="3" name="Description" placeholder="Açıklama yazınız..."
                                      class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300"></textarea>
                        </div>

                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Fotoğraflar (opsiyonel, max 5)</label>
                            <div class="mt-2" data-photo-uploader>
                            <input type="file" name="Photos[]" multiple accept="image/*,application/pdf" data-photo-input
                                       class="block w-full text-sm file:mr-3 file:rounded-xl file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800">
                                <div class="mt-2 text-xs text-slate-500" data-photo-hint>Dosya seçilmedi</div>
                                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3" data-photo-previews></div>
                            </div>
                        </div>

                        <button class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-extrabold text-white shadow hover:bg-emerald-700" type="submit">
                            <i class="ti ti-device-floppy"></i>
                            Yeni Gelir Kaydet
                        </button>
                    </form>
                    <?php } else { ?>
                    <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-white text-emerald-700">
                                <i class="ti ti-info-circle"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-bold text-emerald-800">Gelir kategorisi bulunamadı</div>
                                <div class="mt-1 text-xs text-emerald-700">Yeni gelir eklemek için önce en az bir gelir kategorisi oluşturun.</div>
                                <a href="kategoriler.php?type=1" class="mt-3 inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-3 py-2 text-xs font-extrabold text-white hover:bg-emerald-800">
                                    <i class="ti ti-plus"></i>Kategoriye Git
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-rose-700">Gider</div>
                                <div class="mt-1 text-sm text-slate-600">Yeni gider hareketi ekle</div>
                            </div>
                            <div class="grid h-11 w-11 place-items-center rounded-2xl bg-rose-50 text-rose-700">
                            <i class="ti ti-cash-banknote"></i>
                            </div>
                        </div>

                    <?php if ($giderKategoriCount > 0) { ?>
                    <form id="giderForm" class="mt-5 space-y-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Başlık</label>
                                <input type="text" name="Title" placeholder="Başlık"
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Tutar</label>
                                <input type="number" step="0.01" min="0" name="Amount" placeholder="Tutar"
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Kategori</label>
                                <select name="CategoryId" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                                    <option value="" selected>Bir kategori seçin</option>
                                    <?php foreach($giderKategoriList as $giderKategorisi) { ?>
                                        <option value="<?php echo $giderKategorisi["CategoryId"]; ?>"><?php echo $giderKategorisi["CategoryName"]; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Tarih</label>
                                <input type="datetime-local" value="<?php echo date("Y-m-d H:i"); ?>" name="Date"
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300">
                            </div>
                        </div>

                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Açıklama</label>
                            <textarea rows="3" name="Description" placeholder="Açıklama yazınız..."
                                      class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-slate-300"></textarea>
                        </div>

                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Fotoğraflar (opsiyonel, max 5)</label>
                            <div class="mt-2" data-photo-uploader>
                            <input type="file" name="Photos[]" multiple accept="image/*,application/pdf" data-photo-input
                                       class="block w-full text-sm file:mr-3 file:rounded-xl file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800">
                                <div class="mt-2 text-xs text-slate-500" data-photo-hint>Dosya seçilmedi</div>
                                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3" data-photo-previews></div>
                            </div>
                        </div>

                        <button class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-rose-600 px-4 py-3 text-sm font-extrabold text-white shadow hover:bg-rose-700" type="submit" id="yeniGider">
                            <i class="ti ti-device-floppy"></i>
                            Yeni Gider Kaydet
                        </button>
                    </form>
                    <?php } else { ?>
                    <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-white text-rose-700">
                                <i class="ti ti-info-circle"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-bold text-rose-800">Gider kategorisi bulunamadı</div>
                                <div class="mt-1 text-xs text-rose-700">Yeni gider eklemek için önce en az bir gider kategorisi oluşturun.</div>
                                <a href="kategoriler.php?type=2" class="mt-3 inline-flex items-center gap-2 rounded-xl bg-rose-700 px-3 py-2 text-xs font-extrabold text-white hover:bg-rose-800">
                                    <i class="ti ti-plus"></i>Kategoriye Git
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </section>
            </div>
        </main>

        <?php include("footer.php"); ?>
    </div>

    <?php include("scripts.php"); ?>

	<script>
		$(document).ready(function() {
			$('#gelirForm').on('submit', function(e) {
				e.preventDefault();
				var formData = new FormData(this);
				formData.append("yeniGelir", true);
				window.uxShowUploading("Gelir kaydediliyor...", "Fotoğraflar yükleniyor olabilir. Lütfen bekleyin.");
				$.ajax({
					url: 'hareket.php',
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) 
					{
						window.uxHideUploading();
						var yanit = JSON.parse(response);
						if (yanit.code === 200) {
							toastr.success('Gelir başarıyla kaydedildi!');
							$('#gelirForm')[0].reset();
							$('#gelirForm [data-photo-hint]').text('Dosya seçilmedi');
							$('#gelirForm [data-photo-previews]').empty();
						} else {
							toastr.error('Hata: ' + yanit.message);
						}
					},
					error: function(xhr, status, error) {
						window.uxHideUploading();
						toastr.error('API isteği sırasında bir hata oluştu: ' + error);
					}
				});
			});
		});
		
		$(document).ready(function() {
			$('#giderForm').on('submit', function(e) {
				e.preventDefault();
				var formData = new FormData(this);
				formData.append("yeniGider", true);
				window.uxShowUploading("Gider kaydediliyor...", "Fotoğraflar yükleniyor olabilir. Lütfen bekleyin.");
				$.ajax({
					url: 'hareket.php',
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) 
					{
						window.uxHideUploading();
						var yanit = JSON.parse(response);
						if (yanit.code === 200) {
							toastr.success('Gider başarıyla kaydedildi!');
							$('#giderForm')[0].reset();
							$('#giderForm [data-photo-hint]').text('Dosya seçilmedi');
							$('#giderForm [data-photo-previews]').empty();
						} else {
							toastr.error('Hata: ' + yanit.message);
						}
					},
					error: function(xhr, status, error) {
						window.uxHideUploading();
						toastr.error('API isteği sırasında bir hata oluştu: ' + error);
					}
				});
			});
		});
	</script>
</body>
</html>


