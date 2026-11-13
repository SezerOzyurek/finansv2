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
    <style>
      .login-mesh {
        background-color: #0b0f19;
        background-image: 
          radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.22) 0px, transparent 50%),
          radial-gradient(at 100% 100%, rgba(244, 63, 94, 0.16) 0px, transparent 50%),
          radial-gradient(at 50% 50%, rgba(14, 165, 233, 0.12) 0px, transparent 60%);
      }
    </style>
</head>
<body class="login-mesh min-h-screen flex items-center justify-center p-4 sm:p-6 text-slate-100 antialiased">

    <div class="w-full max-w-4xl grid grid-cols-1 lg:grid-cols-12 overflow-hidden rounded-3xl border border-slate-800/80 bg-slate-900/90 shadow-2xl backdrop-blur-xl">
        
        <!-- Sol Panel: Marka & Görsel Hikaye -->
        <div class="lg:col-span-5 hidden lg:flex flex-col justify-between p-10 border-r border-slate-800/80 bg-gradient-to-b from-slate-900/80 to-slate-950/80">
            <div>
                <div class="flex items-center gap-3">
                    <div class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 font-bold">
                        <i class="ti ti-coins text-2xl"></i>
                    </div>
                    <div>
                        <div class="text-base font-extrabold tracking-tight text-white">Ultra <span class="text-emerald-400">Finans</span></div>
                        <div class="text-[11px] font-semibold text-slate-400 tracking-wider">KİŞİSEL PORTFÖY</div>
                    </div>
                </div>

                <div class="mt-16 space-y-4">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-400">
                        <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Akıllı Finans Asistanı
                    </span>
                    <h2 class="text-2xl font-black text-white leading-tight">
                        Paranın kontrolü <br><span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">tamamen sende.</span>
                    </h2>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Kartlarını tanımla, gelir ve giderlerini şık tablolarla takip et, bütçeni tek bakışta yönet.
                    </p>
                </div>
            </div>

            <div class="text-xs text-slate-500 flex items-center gap-2">
                <i class="ti ti-shield-check text-emerald-400 text-base"></i>
                <span>Güvenli Oturum & Yerel Kurulum</span>
            </div>
        </div>

        <!-- Sağ Panel: Giriş Formu -->
        <div class="lg:col-span-7 p-8 sm:p-12 flex flex-col justify-center">
            
            <!-- Mobil Logo -->
            <div class="flex lg:hidden items-center gap-3 mb-8">
                <div class="grid h-10 w-10 place-items-center rounded-2xl bg-emerald-500 text-white shadow-md font-bold">
                    <i class="ti ti-coins text-xl"></i>
                </div>
                <div>
                    <div class="text-base font-extrabold tracking-tight text-white">Ultra <span class="text-emerald-400">Finans</span></div>
                    <div class="text-[10px] font-semibold text-slate-400 tracking-wider">KİŞİSEL PORTFÖY</div>
                </div>
            </div>

            <div class="mb-8">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Hoş Geldiniz</h1>
                <p class="mt-1 text-xs sm:text-sm text-slate-400">Hesabınıza erişmek için e-posta ve şifrenizi girin.</p>
            </div>

            <form id="girisFormu" class="space-y-5">
                <input type="hidden" name="girisFormu" value="1">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">E-Posta Adresi</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                            <i class="ti ti-mail text-base"></i>
                        </span>
                        <input type="email" name="Email" required autocomplete="email" placeholder="admin@domain.com"
                               class="w-full rounded-2xl border border-slate-700/80 bg-slate-950/60 py-3 pl-10 pr-4 text-sm font-medium text-white placeholder-slate-500 transition focus:border-emerald-500 focus:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Şifre</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                            <i class="ti ti-lock text-base"></i>
                        </span>
                        <input type="password" id="loginPassword" name="Password" required autocomplete="current-password" placeholder="••••••••"
                               class="w-full rounded-2xl border border-slate-700/80 bg-slate-950/60 py-3 pl-10 pr-10 text-sm font-medium text-white placeholder-slate-500 transition focus:border-emerald-500 focus:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        <button type="button" id="toggleLoginPass" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-white transition">
                            <i class="ti ti-eye text-base"></i>
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="btnGiris"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-500 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-400 hover:shadow-emerald-500/40 active:scale-[0.99] focus:outline-none focus:ring-2 focus:ring-emerald-400">
                        <i class="ti ti-login text-base"></i>
                        <span>Sisteme Giriş Yap</span>
                    </button>
                </div>
            </form>

            <div class="mt-8 text-center text-[11px] text-slate-500">
                Ultra Finans Kişisel Yönetim Platformu
            </div>
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
