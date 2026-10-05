<?php
declare(strict_types=1);

function admin_login_page(): void
{
    $err = '';
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        $err = admin_login((string)($_POST['username'] ?? ''), (string)($_POST['password'] ?? ''));
        if ($err === '') {
            header('Location: index.php');
            exit;
        }
    }
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Giriş — ' . h(brand_word()) . ' Yönetim</title>
<link rel="stylesheet" href="../assets/css/fonts.css"><link rel="stylesheet" href="admin.css?v=' . UZ_VERSION . '">' . admin_brand_css() . '</head><body><div class="login"><div class="card">
<h1>' . h(setting('site_name', brand_word())) . '</h1><p class="sub">Yönetim paneli</p>' . ($err ? '<div class="flash err">' . h($err) . '</div>' : '') . '
<form method="post" autocomplete="on">' . csrf_field() . '
<div class="field"><label class="l" for="u">Kullanıcı adı</label><input type="text" id="u" name="username" autocomplete="username" autofocus required></div>
<div class="field"><label class="l" for="p">Şifre</label><input type="password" id="p" name="password" autocomplete="current-password" required></div>
<button class="btn primary" style="width:100%;justify-content:center">Giriş yap</button></form><p style="text-align:center;margin:16px 0 0"><a href="index.php?a=forgot">Şifremi unuttum</a></p></div></div></body></html>';
}

function auth_shell(string $title, string $body): void
{
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . h($title) . '</title>
<link rel="stylesheet" href="../assets/css/fonts.css"><link rel="stylesheet" href="admin.css?v=' . UZ_VERSION . '">' . admin_brand_css() . '</head><body><div class="login"><div class="card"><h1>' . h(setting('site_name', brand_word())) . '</h1><p class="sub">' . h($title) . '</p>' . $body . '</div></div></body></html>';
}

function admin_forgot_page(): void
{
    $sent = false;
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        password_reset_request((string)($_POST['ident'] ?? ''));
        $sent = true;
    }
    auth_shell('Şifre sıfırlama', $sent
        ? '<div class="flash ok">Bu bilgiyle eşleşen bir hesap ve kayıtlı e-posta varsa, sıfırlama bağlantısı gönderildi (1 saat geçerli).</div><p style="text-align:center"><a href="index.php?a=login">Girişe dön</a></p>'
        : '<form method="post">' . csrf_field() . '<div class="field"><label class="l" for="i">Kullanıcı adı veya e-posta</label><input type="text" id="i" name="ident" autofocus required></div><button class="btn primary" style="width:100%;justify-content:center">Bağlantı gönder</button></form><p style="text-align:center;margin:16px 0 0"><a href="index.php?a=login">Girişe dön</a></p><p class="hint" style="text-align:center">Hesabınıza e-posta tanımlı değilse bir yönetici Kullanıcılar sayfasından ekleyebilir.</p>');
}

function admin_reset_page(): void
{
    $tok = (string)($_GET['t'] ?? '');
    $r = password_reset_find($tok);
    if (!$r) {
        auth_shell('Şifre sıfırlama', '<div class="flash err">Bağlantı geçersiz ya da süresi dolmuş.</div><p style="text-align:center"><a href="index.php?a=forgot">Yeni bağlantı iste</a></p>');
        return;
    }
    $err = '';
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        $pw = (string)($_POST['password'] ?? '');
        if ($pw !== (string)($_POST['password2'] ?? '')) {
            $err = 'Şifreler aynı değil.';
        } elseif (($pe = password_problem($pw)) !== '') {
            $err = $pe;
        } else {
            q('UPDATE users SET pass_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $r['user_id']]);
            q('UPDATE password_resets SET used = 1 WHERE user_id = ?', [$r['user_id']]);
            log_activity('Şifre sıfırlandı (kullanıcı #' . $r['user_id'] . ')', 'reset');
            auth_shell('Şifre sıfırlama', '<div class="flash ok">Şifreniz değiştirildi.</div><p style="text-align:center"><a class="btn primary" href="index.php?a=login">Giriş yap</a></p>');
            return;
        }
    }
    auth_shell('Yeni şifre belirleyin', ($err ? '<div class="flash err">' . h($err) . '</div>' : '') . '<form method="post">' . csrf_field() . '<div class="field"><label class="l">Yeni şifre (en az 10 karakter, harf + rakam)</label><input type="password" name="password" autocomplete="new-password" required autofocus></div><div class="field"><label class="l">Yeni şifre (tekrar)</label><input type="password" name="password2" autocomplete="new-password" required></div><button class="btn primary" style="width:100%;justify-content:center">Şifreyi kaydet</button></form>');
}
