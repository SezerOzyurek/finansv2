<?php 
include("fonksiyonlar.php");

// AJAX istekleri
if (isset($_POST['profilGuncelle'])) 
{
    $postData = [
        'action' => 'profil',
        'FirstName' => $_POST['FirstName'] ?? '',
        'LastName' => $_POST['LastName'] ?? '',
        'Email' => $_POST['Email'] ?? '',
        'Currency' => $_POST['Currency'] ?? 'TRY',
    ];
    $yanit = apiRequest('/profil', 'POST', $postData, $_SESSION['Api_Token']);
    if (($yanit['code'] ?? 0) === 200) {
        if (!empty($_POST['FirstName'])) $_SESSION['FirstName'] = trim((string)$_POST['FirstName']);
        if (!empty($_POST['LastName'])) $_SESSION['LastName'] = trim((string)$_POST['LastName']);
        if (!empty($_POST['Email'])) $_SESSION['Email'] = trim((string)$_POST['Email']);
    }
    echo json_encode($yanit, JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($_POST['sifreGuncelle'])) 
{
    $postData = [
        'action' => 'sifre',
        'current_password' => $_POST['current_password'] ?? '',
        'new_password' => $_POST['new_password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
    ];
    $yanit = apiRequest('/profil', 'POST', $postData, $_SESSION['Api_Token']);
    echo json_encode($yanit, JSON_UNESCAPED_UNICODE);
    exit;
}

// Profil verisini çek
$userResp = apiRequest('/profil', 'GET', [], $_SESSION['Api_Token']);
$userData = (isset($userResp['data']) && is_array($userResp['data'])) ? $userResp['data'] : [];

$firstName = $userData['FirstName'] ?? ($_SESSION['FirstName'] ?? '');
$lastName = $userData['LastName'] ?? ($_SESSION['LastName'] ?? '');
$email = $userData['Email'] ?? ($_SESSION['Email'] ?? '');
$currency = $userData['Currency'] ?? 'TRY';
$telegramUser = $userData['TelegramBotUsername'] ?? '';
$telegramChatId = $userData['TelegramChatId'] ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Hesap & Güvenlik Ayarları - Ultra Finans</title>
    <?php include("head.php"); ?>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100" id="top">
<div class="min-h-screen lg:pl-64 flex flex-col">
    <?php include("menu.php"); ?>
    <?php include("ustmenu.php"); ?>

    <main class="w-full flex-1 px-3 py-4 lg:px-6 space-y-4">
            
            <!-- Başlık -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Hesap & Güvenlik Ayarları</h1>
                    <p class="text-sm text-slate-500">Profil bilgilerinizi yönetebilir ve hesap şifrenizi güvenli şekilde güncelleyebilirsiniz.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Aktif Oturum
                    </span>
                </div>
            </div>

            <!-- Ana Form Grid -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

                <!-- 1. KART: Profil Bilgileri -->
                <div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div class="grid h-10 w-10 place-items-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400 font-bold">
                            <i class="ti ti-user text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">Profil Bilgileri</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Kişisel bilgilerinizi ve e-posta adresinizi güncelleyin.</p>
                        </div>
                    </div>

                    <form id="profilForm" class="mt-6 space-y-4">
                        <input type="hidden" name="profilGuncelle" value="1">

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ad</label>
                                <input type="text" name="FirstName" value="<?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>" required
                                       class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-medium text-slate-800 transition focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-100 dark:focus:border-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Soyad</label>
                                <input type="text" name="LastName" value="<?= htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8') ?>" required
                                       class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-medium text-slate-800 transition focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-100 dark:focus:border-emerald-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">E-Posta Adresi</label>
                            <div class="relative mt-1.5">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                    <i class="ti ti-mail"></i>
                                </span>
                                <input type="email" name="Email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pl-10 pr-3.5 text-sm font-medium text-slate-800 transition focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-100 dark:focus:border-emerald-500">
                            </div>
                            <p class="mt-1 text-[11px] text-slate-400">Giriş yaparken bu e-posta adresi kullanılacaktır.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Varsayılan Para Birimi</label>
                            <select name="Currency" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-medium text-slate-800 transition focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-100 dark:focus:border-emerald-500">
                                <option value="₺" <?= in_array($currency, ['₺', 'TRY'], true) ? 'selected' : '' ?>>₺ - Türk Lirası (TRY)</option>
                                <option value="$" <?= in_array($currency, ['$', 'USD'], true) ? 'selected' : '' ?>>$ - Amerikan Doları (USD)</option>
                                <option value="€" <?= in_array($currency, ['€', 'EUR'], true) ? 'selected' : '' ?>>€ - Euro (EUR)</option>
                                <option value="£" <?= in_array($currency, ['£', 'GBP'], true) ? 'selected' : '' ?>>£ - İngiliz Sterlini (GBP)</option>
                            </select>
                        </div>

                        <div class="pt-2">
                            <button type="submit" id="btnProfilKaydet"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-500">
                                <i class="ti ti-device-floppy text-sm"></i>
                                <span>Profil Bilgilerini Kaydet</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 2. KART: Şifre Değiştir -->
                <div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div class="grid h-10 w-10 place-items-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400 font-bold">
                            <i class="ti ti-lock text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">Şifre Değiştir</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Hesabınızın güvenliği için güçlü bir şifre belirleyin.</p>
                        </div>
                    </div>

                    <form id="sifreForm" class="mt-6 space-y-4">
                        <input type="hidden" name="sifreGuncelle" value="1">

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Mevcut Şifre</label>
                            <div class="relative mt-1.5">
                                <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                                       placeholder="••••••••"
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pl-3.5 pr-10 text-sm font-medium text-slate-800 transition focus:border-amber-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-100 dark:focus:border-amber-500">
                                <button type="button" class="toggle-pass absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" data-target="current_password">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Yeni Şifre</label>
                            <div class="relative mt-1.5">
                                <input type="password" id="new_password" name="new_password" required minlength="6" autocomplete="new-password"
                                       placeholder="En az 6 karakter"
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pl-3.5 pr-10 text-sm font-medium text-slate-800 transition focus:border-amber-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-100 dark:focus:border-amber-500">
                                <button type="button" class="toggle-pass absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" data-target="new_password">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-400">Şifreniz en az 6 karakter uzunluğunda olmalıdır.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Yeni Şifre Tekrar</label>
                            <div class="relative mt-1.5">
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="6" autocomplete="new-password"
                                       placeholder="Yeni şifrenizi tekrar girin"
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pl-3.5 pr-10 text-sm font-medium text-slate-800 transition focus:border-amber-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-100 dark:focus:border-amber-500">
                                <button type="button" class="toggle-pass absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" data-target="confirm_password">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </div>
                            <div id="passMatchIndicator" class="mt-1 hidden text-[11px] font-medium"></div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" id="btnSifreKaydet"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-amber-500">
                                <i class="ti ti-key text-sm"></i>
                                <span>Şifreyi Güncelle</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 3. KART: Güvenlik ve Bağlantı Özeti -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Güvenlik & Entegrasyon Durumu</h3>
                
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <div class="flex items-center gap-3">
                            <div class="grid h-8 w-8 place-items-center rounded-xl bg-emerald-100 text-emerald-700">
                                <i class="ti ti-shield-check"></i>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Parola Koruması</div>
                                <div class="text-sm font-bold text-slate-800">Bcrypt / Argon2</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <div class="flex items-center gap-3">
                            <div class="grid h-8 w-8 place-items-center rounded-xl bg-sky-100 text-sky-700">
                                <i class="ti ti-lock-access"></i>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Oturum Tipi</div>
                                <div class="text-sm font-bold text-slate-800">JWT + HttpOnly</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="grid h-8 w-8 place-items-center rounded-xl bg-blue-100 text-blue-700">
                                    <i class="ti ti-brand-telegram"></i>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-500">Telegram Bot</div>
                                    <div class="text-sm font-bold text-slate-800">
                                        <?= !empty($telegramChatId) ? 'Bağlı' : 'Bağlı Değil' ?>
                                    </div>
                                </div>
                            </div>
                            <a href="telegram_ayar.php" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100" title="Ayarlara Git">
                                Yönet
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <?php include("footer.php"); ?>
</div>

<?php include("scripts.php"); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-bottom-right",
        timeOut: 3500
    };

    // Şifre göster/gizle
    $('.toggle-pass').on('click', function() {
        var targetId = $(this).data('target');
        var input = $('#' + targetId);
        var icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('ti-eye').addClass('ti-eye-off text-amber-600');
        } else {
            input.attr('type', 'password');
            icon.removeClass('ti-eye-off text-amber-600').addClass('ti-eye');
        }
    });

    // Şifre eşleşme kontrolü (anlık)
    $('#new_password, #confirm_password').on('input', function() {
        var p1 = $('#new_password').val();
        var p2 = $('#confirm_password').val();
        var indicator = $('#passMatchIndicator');

        if (p2.length === 0) {
            indicator.addClass('hidden').text('');
            return;
        }

        indicator.removeClass('hidden');
        if (p1 === p2) {
            indicator.removeClass('text-rose-600').addClass('text-emerald-600').text('✓ Yeni şifreler eşleşiyor');
        } else {
            indicator.removeClass('text-emerald-600').addClass('text-rose-600').text('✗ Şifreler eşleşmiyor');
        }
    });

    // Profil Güncelleme Formu
    $('#profilForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#btnProfilKaydet');
        var oldHtml = btn.html();
        btn.prop('disabled', true).html('<i class="ti ti-loader animate-spin"></i> Kaydediliyor...');

        $.ajax({
            type: 'POST',
            url: 'profil.php',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp) {
                btn.prop('disabled', false).html(oldHtml);
                if (resp && (resp.code === 200 || resp.ok)) {
                    toastr.success(resp.message || 'Profil bilgileriniz başarıyla güncellendi.');
                    // Üst menüdeki ismi güncelle
                    var fn = $('input[name="FirstName"]').val();
                    var ln = $('input[name="LastName"]').val();
                    $('summary span.hidden.md\\:inline').text(fn + ' ' + ln);
                } else {
                    toastr.error((resp && resp.message) ? resp.message : 'Güncelleme yapılamadı.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(oldHtml);
                var msg = 'Bir hata oluştu.';
                try {
                    var j = JSON.parse(xhr.responseText);
                    if (j && j.message) msg = j.message;
                } catch(e) {}
                toastr.error(msg);
            }
        });
    });

    // Şifre Değiştirme Formu
    $('#sifreForm').on('submit', function(e) {
        e.preventDefault();

        var currentPass = $('#current_password').val();
        var newPass = $('#new_password').val();
        var confirmPass = $('#confirm_password').val();

        if (newPass.length < 6) {
            toastr.warning('Yeni şifre en az 6 karakter olmalıdır.');
            return;
        }

        if (newPass !== confirmPass) {
            toastr.error('Yeni şifreler eşleşmiyor. Lütfen kontrol edin.');
            return;
        }

        var btn = $('#btnSifreKaydet');
        var oldHtml = btn.html();
        btn.prop('disabled', true).html('<i class="ti ti-loader animate-spin"></i> Şifre Güncelleniyor...');

        $.ajax({
            type: 'POST',
            url: 'profil.php',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp) {
                btn.prop('disabled', false).html(oldHtml);
                if (resp && (resp.code === 200 || resp.ok)) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Tebrikler!',
                        text: resp.message || 'Şifreniz başarıyla güncellendi.',
                        confirmButtonText: 'Tamam',
                        confirmButtonColor: '#d97706'
                    });
                    $('#sifreForm')[0].reset();
                    $('#passMatchIndicator').addClass('hidden').text('');
                } else {
                    toastr.error((resp && resp.message) ? resp.message : 'Şifre güncellenemedi.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(oldHtml);
                var msg = 'Bir hata oluştu.';
                try {
                    var j = JSON.parse(xhr.responseText);
                    if (j && j.message) msg = j.message;
                } catch(e) {}
                toastr.error(msg);
            }
        });
    });
});
</script>
</body>
</html>
