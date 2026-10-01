<?php
declare(strict_types=1);

function admin_setup(): void
{
    $req = requirements();
    $ok = !in_array(false, $req, true);
    $err = '';
    if ($ok && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $user = trim((string)($_POST['username'] ?? ''));
        $pw = (string)($_POST['password'] ?? '');
        $pw2 = (string)($_POST['password2'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $user)) {
            $err = 'Kullanıcı adı 3–40 karakter olmalı (harf, rakam, . _ -).';
        } elseif ($pw !== $pw2) {
            $err = 'Şifreler aynı değil.';
        } elseif (($pe = password_problem($pw)) !== '') {
            $err = $pe;
        } else {
            try {
                install_site($user, $pw, trim((string)($_POST['name'] ?? '')) ?: 'Yönetici');
                header('Location: index.php?a=login');
                exit;
            } catch (Throwable $e) {
                $err = 'Kurulum hatası: ' . $e->getMessage();
            }
        }
    }
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Kurulum — Uzman Yönetim</title>
<link rel="stylesheet" href="../assets/css/fonts.css"><link rel="stylesheet" href="admin.css?v=' . UZ_VERSION . '"></head><body><div class="login"><div class="card" style="width:min(520px,100%)">
<h1>Kurulum</h1><p class="sub">Uzman Cosmetic site yönetimi — ilk çalıştırma</p>' . ($err ? '<div class="flash err">' . h($err) . '</div>' : '') . '<ul class="check-list" style="margin-bottom:18px">';
    foreach ($req as $k => $v) {
        echo '<li><span class="dot' . ($v ? ' ok' : '') . '"></span><span>' . h($k) . ($v ? '' : ' — <b style="color:#b3382c">gerekli</b>') . '</span></li>';
    }
    echo '</ul>';
    if ($ok) {
        echo '<form method="post"><div class="field"><label class="l">Adınız</label><input type="text" name="name" value="' . h($_POST['name'] ?? '') . '"></div>
<div class="field"><label class="l">Yönetici kullanıcı adı</label><input type="text" name="username" value="' . h($_POST['username'] ?? '') . '" autocomplete="username" required></div>
<div class="field"><label class="l">Şifre (en az 10 karakter, harf + rakam)</label><input type="password" name="password" autocomplete="new-password" required></div>
<div class="field"><label class="l">Şifre (tekrar)</label><input type="password" name="password2" autocomplete="new-password" required></div>
<button class="btn primary" style="width:100%;justify-content:center">Kurulumu tamamla</button></form>';
    } else {
        echo '<div class="flash err">Eksik gereksinimleri hosting panelinden (cPanel → Select PHP Version / Extensions) tamamlayıp sayfayı yenileyin.</div>';
    }
    echo '</div></div></body></html>';
}
