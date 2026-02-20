<?php 
include("fonksiyonlar.php");

// DataTables server-side proxy: keeps API token on the server (no API token leakage to JS).
if (isset($_GET["datatable"])) {
	$type = $_GET['type'] ?? false;
	$CategoryId = $_GET['CategoryId'] ?? false;

	$draw = isset($_GET["draw"]) ? (int)$_GET["draw"] : 0;
	$start = isset($_GET["start"]) ? max(0, (int)$_GET["start"]) : 0;
	$length = isset($_GET["length"]) ? (int)$_GET["length"] : 50;
	$searchValue = $_GET["search"]["value"] ?? "";

	$orderColIdx = (int)($_GET["order"][0]["column"] ?? 0);
	$orderDir = $_GET["order"][0]["dir"] ?? "desc";
	$orderDir = (strtolower($orderDir) === "asc") ? "ASC" : "DESC";

	$orderMap = [
		0 => "Date",
		1 => "CategoryName",
		2 => "Title",
		4 => "Amount",
		5 => "Enflasyon",
	];
	$orderkey = $orderMap[$orderColIdx] ?? "Date";

	$params = [
		"orderkey" => $orderkey,
		"ordertype" => $orderDir,
		"start" => $start,
		"length" => $length,
	];
	if (!empty($searchValue)) { $params["search"] = $searchValue; }
	if ($CategoryId) { $params["CategoryId"] = (int)$CategoryId; }

	if ($type == "1") { $resp = apiRequest('/gelirler', 'GET', $params, $_SESSION['Api_Token']); }
	elseif ($type == "2") { $resp = apiRequest('/giderler', 'GET', $params, $_SESSION['Api_Token']); }
	else {
		header("Content-Type: application/json; charset=utf-8");
		echo json_encode(["draw" => $draw, "recordsTotal" => 0, "recordsFiltered" => 0, "data" => []], JSON_UNESCAPED_UNICODE);
		exit;
	}

	$list = $resp["data"]["list"] ?? [];
	$recordsTotal = (int)($resp["data"]["recordsTotal"] ?? 0);
	$recordsFiltered = (int)($resp["data"]["recordsFiltered"] ?? 0);

	$data = [];
	foreach ($list as $row) {
		$date = isset($row["Date"]) ? date("Y-m-d H:i", strtotime($row["Date"])) : "";
		$catName = $row["CategoryName"] ?? "";
		$catId = $row["CategoryId"] ?? "";
		$title = $row["Title"] ?? "";
		$photoCount = (int)($row["PhotoCount"] ?? 0);
		$isPending = (int)($row["Gerceklesmemis"] ?? 0) === 1;
		$desc = $row["Description"] ?? "";
		$amount = isset($row["Amount"]) ? (string)para($row["Amount"]) . " ₺" : "";
		$enf = isset($row["Enflasyon"]) ? (string)para($row["Enflasyon"]) . " ₺" : "";

		if ($type == "1") {
			$id = $row["AssetsId"] ?? "";
			$enc = !empty($id) ? encrypt($id) : "";
			$actions =
				'<a class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 hover:bg-slate-50" href="hareketduzenle.php?type=gelir&ID='.htmlspecialchars((string)$id, ENT_QUOTES, "UTF-8").'" title="Düzenle"><i class="ti ti-edit"></i></a>'.
				'<button class="deleteAssets ml-1 inline-flex items-center justify-center rounded-xl bg-rose-600 px-3 py-2 text-white hover:bg-rose-700" data-assetsid="'.htmlspecialchars((string)$enc, ENT_QUOTES, "UTF-8").'" title="Sil"><i class="ti ti-trash"></i></button>';
			$catEsc = htmlspecialchars((string)$catName, ENT_QUOTES, "UTF-8");
			$catLink = '<a class="font-semibold text-slate-900 hover:underline" href="hareketler.php?type=1&CategoryId='.htmlspecialchars((string)$catId, ENT_QUOTES, "UTF-8").'"><span class="cell-clip" title="'.$catEsc.'">'.$catEsc.'</span></a>';
		} else {
			$id = $row["BillsId"] ?? "";
			$enc = !empty($id) ? encrypt($id) : "";
			$actions =
				'<a class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 hover:bg-slate-50" href="hareketduzenle.php?type=gider&ID='.htmlspecialchars((string)$id, ENT_QUOTES, "UTF-8").'" title="Düzenle"><i class="ti ti-edit"></i></a>'.
				'<button class="deleteBills ml-1 inline-flex items-center justify-center rounded-xl bg-rose-600 px-3 py-2 text-white hover:bg-rose-700" data-billsid="'.htmlspecialchars((string)$enc, ENT_QUOTES, "UTF-8").'" title="Sil"><i class="ti ti-trash"></i></button>';
			$catEsc = htmlspecialchars((string)$catName, ENT_QUOTES, "UTF-8");
			$catLink = '<a class="font-semibold text-slate-900 hover:underline" href="hareketler.php?type=2&CategoryId='.htmlspecialchars((string)$catId, ENT_QUOTES, "UTF-8").'"><span class="cell-clip" title="'.$catEsc.'">'.$catEsc.'</span></a>';
		}

		$titleHtml = '<div class="flex items-center gap-2 min-w-0">';
		if ($isPending) {
			if ($type == "1") {
				$titleHtml .= '<span class="inline-flex items-center text-emerald-600" aria-label="durum"><i class="ti ti-hourglass"></i></span>';
			} else {
				$titleHtml .= '<span class="inline-flex items-center text-rose-600" aria-label="durum"><i class="ti ti-hourglass"></i></span>';
			}
		}
		$titleHtml .= '<div class="cell-clip min-w-0 flex-1 font-semibold" title="'.htmlspecialchars((string)$title, ENT_QUOTES, "UTF-8").'">'.htmlspecialchars((string)$title, ENT_QUOTES, "UTF-8").'</div>';
		if ($photoCount > 0) {
			$titleHtml .= '<span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-extrabold text-slate-700" title="Ekli dosya var">'.
				'<i class="ti ti-paperclip text-slate-400"></i>Ek'.($photoCount > 1 ? ' ('.$photoCount.')' : '').'</span>';
		}
		$titleHtml .= '</div>';

		$data[] = [
			'<div class="whitespace-nowrap tabular-nums">'.$date.'</div>',
			$catLink,
			$titleHtml,
			'<div class="cell-clip text-slate-600" title="'.htmlspecialchars((string)$desc, ENT_QUOTES, "UTF-8").'">'.htmlspecialchars((string)$desc, ENT_QUOTES, "UTF-8").'</div>',
			'<div class="text-right font-extrabold">'.$amount.'</div>',
			'<div class="text-right font-bold text-slate-700">'.$enf.'</div>',
			'<div class="text-center">'.$actions.'</div>',
		];
	}

	header("Content-Type: application/json; charset=utf-8");
	echo json_encode([
		"draw" => $draw,
		"recordsTotal" => $recordsTotal,
		"recordsFiltered" => $recordsFiltered,
		"data" => $data,
	], JSON_UNESCAPED_UNICODE);
	exit;
}

if(empty($_POST)) 
{ 
	$type = $_GET['type'] ?? false;
	$CategoryId = $_GET['CategoryId'] ?? false;
	
	$kriterler = [];
    if ($CategoryId) { $kriterler["CategoryId"] = filter_var($CategoryId, FILTER_SANITIZE_NUMBER_INT); }

    $kriterler["orderkey"] = "Date";
    $kriterler["ordertype"] = "DESC";
	$kriterler["start"] = 0;
	$kriterler["length"] = 1; // summary-only: totals + counts, no heavy list

	if(isset($_GET['CategoryId'])) { $kriterler["CategoryId"] = $_GET['CategoryId']; }
	if($type == "1") { $gelirler = apiRequest('/gelirler', 'GET', $kriterler, $_SESSION['Api_Token']); }
	elseif($type == "2") { $giderler = apiRequest('/giderler', 'GET', $kriterler, $_SESSION['Api_Token']); }
	else { header("Location: index.php"); exit; }
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

$rapor = apiRequest('/rapor', 'GET', [], $_SESSION['Api_Token']);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?php if($type == 1) { ?>Gelir Hareketleri<?php } else { ?>Gider Hareketleri<?php } ?></title>
    
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
      /* Light touch to make DataTables blend with Tailwind. */
      table.dataTable { border-collapse: collapse !important; table-layout: fixed !important; width: 100% !important; }
      table.dataTable thead th { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: rgb(100 116 139); background: rgb(248 250 252); white-space: nowrap; }
      table.dataTable thead th, table.dataTable tbody td { padding: 5px 8px !important; }
      table.dataTable tbody td {
        font-size: 12px;
        line-height: 1.15;
        border-top: 1px solid rgb(241 245 249);
        vertical-align: middle;
        white-space: nowrap;
      }
      table.dataTable tbody tr:hover td { background: rgb(248 250 252); }
      /* Keep dates on one line; allow horizontal scroll when needed. */
      #gelirler td:first-child, #giderler td:first-child { white-space: nowrap; }
      .cell-clip { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
      .cell-clip-2 { overflow: hidden; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
      .dataTables_wrapper .dataTables_filter input { border: 1px solid rgb(226 232 240); border-radius: 10px; padding: 6px 8px; font-size: 12px; }
      .dataTables_wrapper .dataTables_length select { border: 1px solid rgb(226 232 240); border-radius: 10px; padding: 4px 8px; font-size: 12px; }
      .dataTables_wrapper .dataTables_info { color: rgb(100 116 139); font-size: 12px; padding-top: 10px; }
      .dataTables_wrapper .dataTables_paginate { padding-top: 10px; }
      .dataTables_wrapper .dataTables_paginate .paginate_button { border: 1px solid rgb(226 232 240) !important; border-radius: 12px; padding: 6px 10px !important; margin-left: 6px; }
      .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: rgb(15 23 42) !important; color: #fff !important; border-color: rgb(15 23 42) !important; }
      .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: rgb(241 245 249) !important; color: rgb(15 23 42) !important; }

      /* Mobile: DataTables controls should stack and avoid overflowing the viewport. */
      @media (max-width: 640px) {
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate { float: none !important; text-align: left !important; }
        .dataTables_wrapper .dataTables_filter { margin-top: 10px; }
        .dataTables_wrapper .dataTables_filter input { width: 100%; max-width: 100%; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 6px 8px !important; margin-left: 4px; }
      }
    </style>
</head>
<body class="app-bg text-slate-900" id="top">
    <div class="min-h-screen lg:pl-64 flex flex-col">
        <?php include("menu.php"); ?>
        <?php include("ustmenu.php"); ?>

        <main class="w-full flex-1 px-4 py-6 lg:px-8">
            <div class="mb-6">
                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Hareketler</div>
                <div class="mt-1 text-2xl font-extrabold tracking-tight">
                    <?php if($type == 1) { ?>Gelir Hareketleri<?php } else { ?>Gider Hareketleri<?php } ?>
                </div>
            </div>

            <?php if($type == 1) { ?>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-emerald-700">Toplam Gelir</div>
                                <div class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900"><?php echo para($gelirler["data"]["total"]); ?> ₺</div>
                            </div>
                            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-emerald-700">
                                <i class="ti ti-currency-lira"></i>
                            </div>
                        </div>
                    </section>
                    <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-emerald-700">Toplam Gelir (Enflasyon)</div>
                                <div class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900"><?php echo para($gelirler["data"]["enflasyon_total"]); ?> ₺</div>
                            </div>
                            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-sky-50 text-sky-700">
                                <i class="ti ti-chart-line"></i>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="mt-6 rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-emerald-700">Liste (Gelirler)</div>
                            <div class="mt-1 text-sm text-slate-600"><?php echo (int)($gelirler["data"]["recordsFiltered"] ?? 0); ?> hareket</div>
                        </div>
                        <?php if (!empty($_GET["CategoryId"])) { ?>
                            <a href="hareketler.php?type=1" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                <i class="ti ti-x text-slate-400"></i>Filtreyi temizle
                            </a>
                        <?php } ?>
                    </div>

                    <div class="mt-4 -mx-4 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 sm:mx-0 sm:p-3">
                        <table class="min-w-full text-sm" id="gelirler" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Kategori</th>
                                    <th>Başlık</th>
                                    <th>Açıklama</th>
                                    <th class="text-right">Tutar</th>
                                    <th class="text-right">Enflasyon</th>
                                    <th class="text-center">İşlem</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </section>
            <?php } else { ?>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-rose-700">Toplam Gider</div>
                                <div class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900"><?php echo para($giderler["data"]["total"]); ?> ₺</div>
                            </div>
                            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-rose-50 text-rose-700">
                                <i class="ti ti-currency-lira"></i>
                            </div>
                        </div>
                    </section>
                    <section class="rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-rose-700">Toplam Gider (Enflasyon)</div>
                                <div class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900"><?php echo para($giderler["data"]["enflasyon_total"]); ?> ₺</div>
                            </div>
                            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-sky-50 text-sky-700">
                                <i class="ti ti-chart-line"></i>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="mt-6 rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-sm backdrop-blur">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-rose-700">Liste (Giderler)</div>
                            <div class="mt-1 text-sm text-slate-600"><?php echo (int)($giderler["data"]["recordsFiltered"] ?? 0); ?> hareket</div>
                        </div>
                        <?php if (!empty($_GET["CategoryId"])) { ?>
                            <a href="hareketler.php?type=2" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                <i class="ti ti-x text-slate-400"></i>Filtreyi temizle
                            </a>
                        <?php } ?>
                    </div>

                    <div class="mt-4 -mx-4 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 sm:mx-0 sm:p-3">
                        <table class="min-w-full text-sm" id="giderler" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Kategori</th>
                                    <th>Başlık</th>
                                    <th>Açıklama</th>
                                    <th class="text-right">Tutar</th>
                                    <th class="text-right">Enflasyon</th>
                                    <th class="text-center">İşlem</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
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
			function initServerTable(selector, type) {
				if (!$(selector).length) return null;
				var isMobile = window.matchMedia && window.matchMedia("(max-width: 640px)").matches;
				return $(selector).DataTable({
					serverSide: true,
					processing: true,
					autoWidth: false,
					// DataTables scrollX adds extra wrappers that are flaky on mobile; we handle overflow via our container.
					scrollX: false,
					pageLength: isMobile ? 25 : 50,
					lengthMenu: [25, 50, 100, 250],
					order: [[0, "desc"]],
					ordering: true,
					searchDelay: 350,
					ajax: {
						url: "hareketler.php",
						type: "GET",
						data: function (d) {
							d.datatable = 1;
							d.type = type;
							<?php if (!empty($_GET["CategoryId"])) { ?>
							d.CategoryId = "<?php echo (int)$_GET["CategoryId"]; ?>";
							<?php } ?>
						}
					},
					columnDefs: [
						{ targets: 0, width: "140px" },
						{ targets: 1, width: "170px" },
						{ targets: 2, width: "220px" },
						{ targets: 3, width: "320px" },
						{ targets: 4, width: "120px", orderable: true, searchable: false },
						{ targets: 5, width: "120px", orderable: true, searchable: false },
						{ targets: 6, width: "120px", orderable: false, searchable: false },
						// Mobile: hide heavy columns to keep the table readable.
						{ targets: 3, visible: !isMobile },
						{ targets: 5, visible: !isMobile },
					],
					language: {
						search: "Ara:",
						lengthMenu: "_MENU_",
						info: "_TOTAL_ kayıttan _START_ - _END_",
						infoEmpty: "Kayıt yok",
						zeroRecords: "Kayıt bulunamadı",
						paginate: { previous: "Önce", next: "Sonra" },
						processing: "Yükleniyor..."
					}
				});
			}

			var gelirDT = initServerTable("#gelirler", "1");
			var giderDT = initServerTable("#giderler", "2");
			
			<?php if($type == 1) { ?>
			$(document).on("click", ".deleteAssets", function() 
			{
				let AssetsId = $(this).data("assetsid");

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
									if (gelirDT) gelirDT.ajax.reload(null, false);
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
			<?php } ?>
			<?php if($type == 2) { ?>
			$(document).on("click", ".deleteBills", function() 
			{
				let BillsId = $(this).data("billsid");

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
									if (giderDT) giderDT.ajax.reload(null, false);
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
			<?php } ?>

			
		});
	</script>
</body>
</html>


