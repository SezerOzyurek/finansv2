<?php
$_GET['loginbypass'] = true;	
include("fonksiyonlar.php");
if (loginmi()) { header("Location: " . APP_SITE_URL . "/index.php"); exit; }
$ayarlar = apiRequest('/ayarlar', 'GET', [], NULL);

if(isset($_POST['girisFormu'])) 
{
	$yanit = apiRequest('/login', 'POST', $_POST, NULL);
	if($yanit["code"] == 200) 
	{
		$payload = $yanit["data"] ?? [];
		$_SESSION['UserId'] = $payload['UserId'] ?? null;
		$_SESSION['Api_Token'] = $payload['AccessToken'] ?? ($payload['Api_Token'] ?? null);
		$_SESSION['Refresh_Token'] = $payload['RefreshToken'] ?? null;
		$_SESSION['FirstName'] = $payload['FirstName'] ?? "";
		$_SESSION['LastName'] = $payload['LastName'] ?? "";
		$_SESSION['rakamlar'] = 1;
	}
	echo json_encode($yanit, JSON_UNESCAPED_UNICODE);
	exit;
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Giri&#351; Yap</title>
    <link href="https://fonts.googleapis.com/css?family=Manrope:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: { extend: { fontFamily: { sans: ["Manrope", "ui-sans-serif", "system-ui", "Segoe UI", "Arial"] } } }
      }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
      body { font-family: Manrope, ui-sans-serif, system-ui, Segoe UI, Arial, sans-serif; }
      .bgx {
        background:
          radial-gradient(900px 420px at 30% 10%, rgba(2,132,199,.20), transparent 60%),
          radial-gradient(900px 420px at 70% 20%, rgba(15,23,42,.16), transparent 55%),
          linear-gradient(135deg, #020617, #0b1220 55%, #020617);
      }
    </style>
</head>
<body class="bgx text-white">
    <div class="min-h-screen px-4 py-10">
        <div class="mx-auto grid max-w-5xl grid-cols-1 overflow-hidden rounded-3xl border border-white/10 bg-white/5 shadow-2xl backdrop-blur lg:grid-cols-2">
            <div class="hidden flex-col justify-between p-10 lg:flex">
                <div class="flex items-center gap-3">
                    <div class="grid h-12 w-12 place-items-center rounded-2xl bg-white/10">
                        <i class="ti ti-coins text-xl"></i>
                    </div>
                    <div>
                        <div class="text-sm font-semibold tracking-wide">Ultra</div>
                        <div class="text-xs text-white/70">Finans Sistemi</div>
                    </div>
                </div>

                <div class="mt-10">
                    <div class="text-3xl font-extrabold leading-tight">
                        Finans ak&#305;&#351;&#305;na tek yerden hakim ol.
                    </div>
                    <div class="mt-3 max-w-sm text-sm text-white/70">
                        Gelir ve gider hareketlerini d&#252;zenli tut, kategorilerini y&#246;net,
                        raporlar&#305;n&#305; anl&#305;k izle ve b&#252;t&#252;n s&#252;reci tek panelden kolayca takip et.
                    </div>
                </div>

                <div class="text-xs text-white/50">
                    <?php if (!empty($ayarlar["site_url"])) { echo $ayarlar["site_url"]; } ?>
                </div>
            </div>

            <div class="p-8 sm:p-10">
                <div class="mb-6">
                    <div class="text-2xl font-extrabold">Giri&#351;</div>
                    <div class="mt-1 text-sm text-white/70">Devam etmek i&#231;in bilgilerini gir.</div>
                </div>

                <form id="girisFormu" class="space-y-4">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-widest text-white/60">E-Posta</label>
                        <input type="email" name="Email" autocomplete="email"
                               class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none placeholder:text-white/40 focus:border-white/20 focus:bg-white/10"
                               placeholder="ornek@mail.com">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-widest text-white/60">&#350;ifre</label>
                        <input type="password" name="Password" autocomplete="current-password"
                               class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none placeholder:text-white/40 focus:border-white/20 focus:bg-white/10"
                               placeholder="********">
                    </div>
                    <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-extrabold text-slate-900 shadow-xl hover:bg-slate-100">
                        <i class="ti ti-login"></i>
                        Giri&#351; Yap
                    </button>
                </form>

                <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-4 text-center text-xs text-white/70">
                    <div>Ultra Software Group</div>
                    <div>Finance Management Software</div>
                    <div class="font-semibold">I love Open Source <span class="font-extrabold text-rose-300">&hearts;</span></div>
                </div>
            </div>
        </div>
    </div>

	<?php include("scripts.php"); ?>
	<script>
		$(document).ready(function() {
			$('#girisFormu').on('submit', function(e) {
				e.preventDefault();
				var formData = new FormData(this);
				formData.append("girisFormu", true);
				$.ajax({
					url: 'login.php',
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) 
					{
						var yanit = JSON.parse(response);
						if (yanit.code === 200) {
							window.location.href = 'index.php';
						} else {
							toastr.error('Hata: ' + yanit.message);
						}
					},
					error: function(xhr, status, error) {
                        toastr.error('API iste&#287;i s&#305;ras&#305;nda bir hata olu&#351;tu: ' + error);
					}
				});
			});
		});
	</script>
</body>
</html>
