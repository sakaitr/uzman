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
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Giriş — Uzman Yönetim</title>
<link rel="stylesheet" href="../assets/css/fonts.css"><link rel="stylesheet" href="admin.css?v=' . UZ_VERSION . '"></head><body><div class="login"><div class="card">
<h1>Uzman Cosmetic</h1><p class="sub">Yönetim paneli</p>' . ($err ? '<div class="flash err">' . h($err) . '</div>' : '') . '
<form method="post" autocomplete="on">' . csrf_field() . '
<div class="field"><label class="l" for="u">Kullanıcı adı</label><input type="text" id="u" name="username" autocomplete="username" autofocus required></div>
<div class="field"><label class="l" for="p">Şifre</label><input type="password" id="p" name="password" autocomplete="current-password" required></div>
<button class="btn primary" style="width:100%;justify-content:center">Giriş yap</button></form></div></div></body></html>';
}
