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
        $u = row('SELECT id, username, name, role, email FROM users WHERE id = ?', [(int)$_SESSION['uid']]);
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
        log_activity('Başarısız giriş denemesi: ' . mb_substr(trim($username), 0, 40), 'login');
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
    q('INSERT INTO activity(ts, who, route, text, ip) VALUES(?,?,?,?,?)', [date('Y-m-d H:i:s'), $u['username'], 'login', 'Giriş yapıldı', client_ip()]);
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

function is_admin_role(): bool
{
    $u = admin_user();
    return $u && ($u['role'] ?? 'admin') === 'admin';
}

/** Routes that only administrators may open (editors manage content, SEO and growth, not the system). */
function admin_only_routes(): array
{
    return ['users', 'tools', 'backup', 'settings', 'brand', 'tracking', 'seo_settings', 'golive'];
}

/** Starts a reset: always behaves the same to the outside (no account enumeration). */
function password_reset_request(string $ident): void
{
    if (throttle_count('reset', 3600) >= 5) {
        return;
    }
    throttle_hit('reset');
    $ident = trim($ident);
    $u = $ident === '' ? null : row('SELECT * FROM users WHERE (username = ? OR (email <> \'\' AND lower(email) = lower(?)))', [$ident, $ident]);
    if (!$u || $u['email'] === '' || !filter_var($u['email'], FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $tok = random_token(24);
    q('UPDATE password_resets SET used = 1 WHERE user_id = ?', [$u['id']]);
    q('INSERT INTO password_resets(user_id, token_hash, expires) VALUES(?,?,?)', [$u['id'], hash('sha256', $tok), time() + 3600]);
    require_once UZ_APP . '/mail.php';
    $url = base_url() . 'admin/index.php?a=reset&t=' . $tok;
    mail_send($u['email'], setting('site_name', 'Site') . ' — şifre sıfırlama', "Merhaba " . ($u['name'] ?: $u['username']) . ",\n\nŞifrenizi sıfırlamak için (1 saat geçerli):\n$url\n\nBu isteği siz yapmadıysanız bu e-postayı yok sayın.");
    log_activity('Şifre sıfırlama bağlantısı gönderildi: ' . $u['username'], 'reset');
}

function password_reset_find(string $tok): ?array
{
    if (!preg_match('/^[a-f0-9]{48}$/', $tok)) {
        return null;
    }
    return row('SELECT * FROM password_resets WHERE token_hash = ? AND used = 0 AND expires > ?', [hash('sha256', $tok), time()]);
}
