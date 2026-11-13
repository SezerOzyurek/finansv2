<?php 
include("fonksiyonlar.php");

// DataTables server-side proxy: keeps API token on the server (no API token leakage to JS).
if (isset($_GET["datatable"])) {
	$type = $_GET['type'] ?? false;
	$CategoryId = $_GET['CategoryId'] ?? false;
	$AccountId = $_GET['AccountId'] ?? false;

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
	if ($AccountId) { $params["AccountId"] = (int)$AccountId; }

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
		$date = isset($row["Date"]) ? formatTarihSaat($row["Date"]) : "";
		$catName = $row["CategoryName"] ?? "";
		$catId = $row["CategoryId"] ?? "";
		$title = $row["Title"] ?? "";
		$photoCount = (int)($row["PhotoCount"] ?? 0);
		$isPending = (int)($row["Gerceklesmemis"] ?? 0) === 1;
		$desc = $row["Description"] ?? "";
		$rowDate = !empty($row["Date"]) ? date("Y-m-d", strtotime((string)$row["Date"])) : null;
		$amount = isset($row["Amount"]) ? paraSpan($row["Amount"], ["date" => $rowDate, "context" => "movement"]) : "";

		if ($type == "1") {
			$id = $row["AssetsId"] ?? "";
			$enc = !empty($id) ? encrypt($id) : "";
			$actions =
				'<a class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700" href="hareketduzenle.php?type=gelir&ID='.htmlspecialchars((string)$id, ENT_QUOTES, "UTF-8").'" title="Düzenle"><i class="ti ti-edit"></i></a>'.
				'<button class="deleteAssets ml-1 inline-flex items-center justify-center rounded-xl bg-rose-600 px-3 py-2 text-white hover:bg-rose-700" data-assetsid="'.htmlspecialchars((string)$enc, ENT_QUOTES, "UTF-8").'" title="Sil"><i class="ti ti-trash"></i></button>';
			$catEsc = htmlspecialchars((string)$catName, ENT_QUOTES, "UTF-8");
			$catLink = '<a class="font-semibold text-slate-900 dark:text-slate-100 hover:text-emerald-600 dark:hover:text-emerald-400 hover:underline" href="hareketler.php?type=1&CategoryId='.htmlspecialchars((string)$catId, ENT_QUOTES, "UTF-8").'"><span class="cell-clip" title="'.$catEsc.'">'.$catEsc.'</span></a>';
		} else {
			$id = $row["BillsId"] ?? "";
			$enc = !empty($id) ? encrypt($id) : "";
			$actions =
				'<a class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700" href="hareketduzenle.php?type=gider&ID='.htmlspecialchars((string)$id, ENT_QUOTES, "UTF-8").'" title="Düzenle"><i class="ti ti-edit"></i></a>'.
				'<button class="deleteBills ml-1 inline-flex items-center justify-center rounded-xl bg-rose-600 px-3 py-2 text-white hover:bg-rose-700" data-billsid="'.htmlspecialchars((string)$enc, ENT_QUOTES, "UTF-8").'" title="Sil"><i class="ti ti-trash"></i></button>';
			$catEsc = htmlspecialchars((string)$catName, ENT_QUOTES, "UTF-8");
			$catLink = '<a class="font-semibold text-slate-900 dark:text-slate-100 hover:text-rose-600 dark:hover:text-rose-400 hover:underline" href="hareketler.php?type=2&CategoryId='.htmlspecialchars((string)$catId, ENT_QUOTES, "UTF-8").'"><span class="cell-clip" title="'.$catEsc.'">'.$catEsc.'</span></a>';
		}

		$titleHtml = '<div class="flex items-center gap-2 min-w-0">';
		if ($isPending) {
			if ($type == "1") {
				$titleHtml .= '<span class="inline-flex items-center text-emerald-600 dark:text-emerald-400" aria-label="durum"><i class="ti ti-hourglass"></i></span>';
			} else {
				$titleHtml .= '<span class="inline-flex items-center text-rose-600 dark:text-rose-400" aria-label="durum"><i class="ti ti-hourglass"></i></span>';
			}
		}
		$titleHtml .= '<div class="cell-clip min-w-0 flex-1 font-semibold text-slate-900 dark:text-slate-100" title="'.htmlspecialchars((string)$title, ENT_QUOTES, "UTF-8").'">'.htmlspecialchars((string)$title, ENT_QUOTES, "UTF-8").'</div>';
		$accName = $row["AccountName"] ?? "";
		if (!empty($accName)) {
			$titleHtml .= '<span class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" title="Hesap / Kart: '.htmlspecialchars((string)$accName, ENT_QUOTES, "UTF-8").'">'.
				'<i class="ti ti-credit-card text-slate-400 dark:text-slate-500"></i>'.htmlspecialchars((string)$accName, ENT_QUOTES, "UTF-8").'</span>';
		}
		if ($photoCount > 0) {
			$titleHtml .= '<span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-extrabold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" title="Ekli dosya var">'.
				'<i class="ti ti-paperclip text-slate-400 dark:text-slate-500"></i>Ek'.($photoCount > 1 ? ' ('.$photoCount.')' : '').'</span>';
		}
		$titleHtml .= '</div>';

		$data[] = [
			'<div class="whitespace-nowrap tabular-nums text-slate-700 dark:text-slate-300">'.$date.'</div>',
			$catLink,
			$titleHtml,
			'<div class="cell-clip text-slate-600 dark:text-slate-400" title="'.htmlspecialchars((string)$desc, ENT_QUOTES, "UTF-8").'">'.htmlspecialchars((string)$desc, ENT_QUOTES, "UTF-8").'</div>',
			'<div class="text-right font-extrabold">'.$amount.'</div>',
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
	$AccountId = $_GET['AccountId'] ?? false;
	
	$kriterler = [];
    if ($CategoryId) { $kriterler["CategoryId"] = filter_var($CategoryId, FILTER_SANITIZE_NUMBER_INT); }
    if ($AccountId) { $kriterler["AccountId"] = filter_var($AccountId, FILTER_SANITIZE_NUMBER_INT); }

    $kriterler["orderkey"] = "Date";
    $kriterler["ordertype"] = "DESC";
	$kriterler["start"] = 0;
	$kriterler["length"] = 1; // summary-only: totals + counts, no heavy list

	if(isset($_GET['CategoryId'])) { $kriterler["CategoryId"] = $_GET['CategoryId']; }
	if($type == "1") { $gelirler = apiRequest('/gelirler', 'GET', $kriterler, $_SESSION['Api_Token']); }
	elseif($type == "2") { $giderler = apiRequest('/giderler', 'GET', $kriterler, $_SESSION['Api_Token']); }
	else { header("Location: index.php"); exit; }
}

$selectedCategoryName = "";
if (!empty($CategoryId) && ($type == "1" || $type == "2")) {
	$categoryResp = apiRequest('/kategoriler', 'GET', [
		"CategoryId" => (int)$CategoryId,
		"Type" => (int)$type,
		"orderkey" => "CategoryName",
		"ordertype" => "ASC",
	], $_SESSION['Api_Token']);
	$selectedCategoryName = (string)($categoryResp["data"]["list"][0]["CategoryName"] ?? "");
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
    <title><?php if($type == 1) { ?>Gelir Hareketleri<?php } else { ?>Gider Hareketleri<?php } ?><?php if ($selectedCategoryName !== "") { ?> (<?php echo htmlspecialchars($selectedCategoryName, ENT_QUOTES, "UTF-8"); ?>)<?php } ?> - Ultra Finans</title>
    <?php include("head.php"); ?>
    <style>
      table.dataTable { border-collapse: collapse !important; table-layout: fixed !important; width: 100% !important; }
      table.dataTable thead th { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: rgb(100 116 139); background: rgb(248 250 252); white-space: nowrap; }
      table.dataTable thead th, table.dataTable tbody td { padding: 8px 10px !important; }
      table.dataTable tbody td {
        font-size: 12px;
        line-height: 1.2;
        border-top: 1px solid rgb(241 245 249);
        vertical-align: middle;
        white-space: nowrap;
      }
      table.dataTable tbody tr:hover td { background: rgb(248 250 252); }
      #gelirler td:first-child, #giderler td:first-child { white-space: nowrap; }
      .cell-clip { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
      .cell-clip-2 { overflow: hidden; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
      @media (max-width: 640px) {
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate { float: none !important; text-align: left !important; }
        .dataTables_wrapper .dataTables_filter { margin-top: 10px; }
        .dataTables_wrapper .dataTables_filter input { width: 100%; max-width: 100%; }
      }
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
                        <i class="ti <?= ($type == 1) ? 'ti-arrow-down-left text-emerald-600 dark:text-emerald-400' : 'ti-arrow-up-right text-rose-600 dark:text-rose-400' ?>"></i>
                        <span><?php if($type == 1) { ?>Gelir Hareketleri<?php } else { ?>Gider Hareketleri<?php } ?></span>
                        <?php if ($selectedCategoryName !== "") { ?>
                            <span class="text-xs font-semibold rounded-lg bg-slate-200/80 px-2 py-0.5 text-slate-700 dark:bg-slate-800 dark:text-slate-300"><?= htmlspecialchars($selectedCategoryName, ENT_QUOTES, "UTF-8") ?></span>
                        <?php } ?>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                        Kaydedilmiş tüm <?= ($type == 1) ? 'gelir' : 'gider' ?> işlemlerinizi arayın, filtreleyin ve yönetin.
                    </p>
                </div>
                <div>
                    <a href="hareket.php?type=<?= ($type == 1) ? 'gelir' : 'gider' ?>" class="inline-flex items-center gap-2 rounded-xl <?= ($type == 1) ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-rose-600 hover:bg-rose-500' ?> px-4 py-2.5 text-xs font-bold text-white shadow-xs transition">
                        <i class="ti ti-plus text-sm"></i>
                        <span>Yeni <?= ($type == 1) ? 'Gelir' : 'Gider' ?> Ekle</span>
                    </a>
                </div>
            </div>

            <?php if($type == 1) { ?>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Toplam Gelir</div>
                                <div class="mt-2 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white"><?php echo paraSpan($gelirler["data"]["total"]); ?></div>
                            </div>
                            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                                <i class="ti ti-arrow-down-left text-xl"></i>
                            </div>
                        </div>
                    </section>
                    <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-sky-600 dark:text-sky-400">Toplam Gelir (Enflasyon)</div>
                                <div class="mt-2 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white"><?php echo paraSpan($gelirler["data"]["enflasyon_total"]); ?></div>
                            </div>
                            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-sky-50 text-sky-600 dark:bg-sky-950/40 dark:text-sky-400">
                                <i class="ti ti-chart-line text-xl"></i>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="mt-6 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">İşlem Listesi</div>
                            <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"><?php echo (int)($gelirler["data"]["recordsFiltered"] ?? 0); ?> hareket listeleniyor</div>
                        </div>
                        <?php if (!empty($_GET["CategoryId"])) { ?>
                            <a href="hareketler.php?type=1" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-200">
                                <i class="ti ti-x text-xs"></i><span>Filtreyi Temizle</span>
                            </a>
                        <?php } ?>
                    </div>

                    <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-100 bg-slate-50/50 p-2 sm:p-3 dark:border-slate-800 dark:bg-slate-950/50">
                        <table class="min-w-full text-xs" id="gelirler" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Kategori</th>
                                    <th>Başlık</th>
                                    <th>Açıklama</th>
                                    <th class="text-right">Tutar</th>
                                    <th class="text-center">İşlem</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </section>
            <?php } else { ?>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-rose-600 dark:text-rose-400">Toplam Gider</div>
                                <div class="mt-2 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white"><?php echo paraSpan($giderler["data"]["total"]); ?></div>
                            </div>
                            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                                <i class="ti ti-arrow-up-right text-xl"></i>
                            </div>
                        </div>
                    </section>
                    <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-sky-600 dark:text-sky-400">Toplam Gider (Enflasyon)</div>
                                <div class="mt-2 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white"><?php echo paraSpan($giderler["data"]["enflasyon_total"]); ?></div>
                            </div>
                            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-sky-50 text-sky-600 dark:bg-sky-950/40 dark:text-sky-400">
                                <i class="ti ti-chart-line text-xl"></i>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="mt-6 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-rose-600 dark:text-rose-400">İşlem Listesi</div>
                            <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"><?php echo (int)($giderler["data"]["recordsFiltered"] ?? 0); ?> hareket listeleniyor</div>
                        </div>
                        <?php if (!empty($_GET["CategoryId"])) { ?>
                            <a href="hareketler.php?type=2" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-200">
                                <i class="ti ti-x text-xs"></i><span>Filtreyi Temizle</span>
                            </a>
                        <?php } ?>
                    </div>

                    <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-100 bg-slate-50/50 p-2 sm:p-3 dark:border-slate-800 dark:bg-slate-950/50">
                        <table class="min-w-full text-xs" id="giderler" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Kategori</th>
                                    <th>Başlık</th>
                                    <th>Açıklama</th>
                                    <th class="text-right">Tutar</th>
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
						{ targets: 5, width: "120px", orderable: false, searchable: false },
						// Mobile: hide heavy columns to keep the table readable.
						{ targets: 3, visible: !isMobile },
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


