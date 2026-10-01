<?php
declare(strict_types=1);

function admin_user(): ?array
{
    start_session();
    if (empty($_SESSION['uid'])) {
        return null;
    }
    static $u = false;
    if ($u === false) {
        $u = row('SELECT id, username, name, role FROM users WHERE id = ?', [(int)$_SESSION['uid']]);
        if (!$u) {
            unset($_SESSION['uid']);
        }
    }
    return $u;
}

function admin_login(string $username, string $password): string
{
    start_session();
    if (throttle_count('login', 900) >= 8) {
        return 'Çok fazla deneme. Lütfen 15 dakika sonra tekrar deneyin.';
    }
    $u = row('SELECT * FROM users WHERE username = ?', [trim($username)]);
    if (!$u || !password_verify($password, $u['pass_hash'])) {
        throttle_hit('login');
        usleep(350000);
        return 'Kullanıcı adı veya şifre hatalı.';
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    $_SESSION['csrf'] = random_token(16);
    if (password_needs_rehash($u['pass_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE users SET pass_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    q('UPDATE users SET last_login = ? WHERE id = ?', [date('Y-m-d H:i:s'), $u['id']]);
    return '';
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = random_token(16);
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

function csrf_check(): void
{
    start_session();
    $t = (string)($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals(csrf_token(), $t)) {
        http_response_code(400);
        exit('Oturum doğrulaması başarısız (CSRF). Sayfayı yenileyip tekrar deneyin.');
    }
}

function flash(string $msg, string $type = 'ok'): void
{
    start_session();
    $_SESSION['flash'][] = [$type, $msg];
}

function flash_html(): string
{
    start_session();
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) {
        $out .= '<div class="flash ' . h($type) . '" role="status">' . h($msg) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

function password_problem(string $pw): string
{
    if (mb_strlen($pw) < 10) {
        return 'Şifre en az 10 karakter olmalı.';
    }
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) {
        return 'Şifre en az bir harf ve bir rakam içermeli.';
    }
    return '';
}
