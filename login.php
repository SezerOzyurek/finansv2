<?php
$_GET['loginbypass'] = true;	
include("fonksiyonlar.php");
if (loginmi()) { header("Location: " . APP_SITE_URL . "/index.php"); exit; }

if(isset($_POST['girisFormu'])) 
{
	$yanit = apiRequest('/login', 'POST', $_POST, NULL);
	if(($yanit["code"] ?? 0) === 200) 
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
    <title>Giriş Yap - Ultra Finans</title>
    <?php include("head.php"); ?>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100 flex flex-col justify-between p-4 sm:p-6">

    <div class="w-full max-w-sm mx-auto my-auto py-8">
        <!-- Logo & Başlık -->
        <div class="text-center mb-8">
            <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-sm dark:bg-emerald-500 dark:text-slate-950 mb-3">
                <i class="ti ti-wallet text-2xl font-bold"></i>
            </div>
            <h1 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">Ultra Finans</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Kişisel Finans Yönetim Sistemi</p>
        </div>

        <!-- Giriş Kartı -->
        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <form id="girisFormu" class="space-y-4">
                <input type="hidden" name="girisFormu" value="1">

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">E-Posta</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                            <i class="ti ti-mail text-base"></i>
                        </span>
                        <input type="email" name="Email" required autocomplete="email" placeholder="ornek@alanadi.com"
                               class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 py-2.5 pl-10 pr-3 text-sm font-medium text-slate-900 placeholder-slate-400 transition focus:border-slate-400 focus:bg-white focus:outline-none dark:border-slate-700 dark:bg-slate-800/80 dark:text-white dark:placeholder-slate-500 dark:focus:border-slate-600">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Şifre</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                            <i class="ti ti-lock text-base"></i>
                        </span>
                        <input type="password" id="loginPassword" name="Password" required autocomplete="current-password" placeholder="••••••••"
                               class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 py-2.5 pl-10 pr-10 text-sm font-medium text-slate-900 placeholder-slate-400 transition focus:border-slate-400 focus:bg-white focus:outline-none dark:border-slate-700 dark:bg-slate-800/80 dark:text-white dark:placeholder-slate-500 dark:focus:border-slate-600">
                        <button type="button" id="toggleLoginPass" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                            <i class="ti ti-eye text-base"></i>
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="btnGiris"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-bold text-white shadow-xs transition hover:bg-slate-800 active:scale-[0.99] dark:bg-emerald-600 dark:text-white dark:hover:bg-emerald-500">
                        <i class="ti ti-arrow-right text-base"></i>
                        <span>Giriş Yap</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-6 text-center text-xs text-slate-400 dark:text-slate-600">
            &copy; <?= date("Y") ?> Ultra Finans
        </div>
    </div>

    <?php include("scripts.php"); ?>
    <script>
    $(document).ready(function() {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-bottom-right",
            timeOut: 3500
        };

        $('#toggleLoginPass').on('click', function() {
            var input = $('#loginPassword');
            var icon = $(this).find('i');
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('ti-eye').addClass('ti-eye-off text-emerald-400');
            } else {
                input.attr('type', 'password');
                icon.removeClass('ti-eye-off text-emerald-400').addClass('ti-eye');
            }
        });

        $('#girisFormu').on('submit', function(e) {
            e.preventDefault();
            var btn = $('#btnGiris');
            var oldHtml = btn.html();
            btn.prop('disabled', true).html('<i class="ti ti-loader animate-spin"></i> Giriş Yapılıyor...');

            $.ajax({
                type: 'POST',
                url: 'login.php',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(yanit) {
                    btn.prop('disabled', false).html(oldHtml);
                    if (yanit.code === 200) {
                        toastr.success('Giriş başarılı! Yönlendiriliyorsunuz...');
                        setTimeout(function() {
                            window.location.href = "index.php";
                        }, 600);
                    } else {
                        toastr.error(yanit.message || 'Giriş bilgileri hatalı.');
                    }
                },
                error: function() {
                    btn.prop('disabled', false).html(oldHtml);
                    toastr.error('Sunucuyla iletişim kurulamadı.');
                }
            });
        });
    });
    </script>
</body>
</html>
