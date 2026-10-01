<?php
declare(strict_types=1);

function admin_users(): void
{
    $me = admin_user();
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $do = (string)($_POST['do'] ?? '');
        if ($do === 'password') {
            $u = row('SELECT * FROM users WHERE id = ?', [$me['id']]);
            $new = (string)($_POST['new'] ?? '');
            if (!password_verify((string)($_POST['current'] ?? ''), $u['pass_hash'])) {
                flash('Mevcut şifre hatalı.', 'err');
            } elseif ($new !== (string)($_POST['new2'] ?? '')) {
                flash('Yeni şifreler aynı değil.', 'err');
            } elseif (($pe = password_problem($new)) !== '') {
                flash($pe, 'err');
            } else {
                q('UPDATE users SET pass_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
                flash('Şifre değiştirildi.');
            }
        } elseif ($do === 'add') {
            $un = post_str('username', 40);
            $pw = (string)($_POST['password'] ?? '');
            if (!preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $un)) {
                flash('Kullanıcı adı 3–40 karakter olmalı (harf, rakam, . _ -).', 'err');
            } elseif (row('SELECT id FROM users WHERE username = ?', [$un])) {
                flash('Bu kullanıcı adı kullanılıyor.', 'err');
            } elseif (($pe = password_problem($pw)) !== '') {
                flash($pe, 'err');
            } else {
                q('INSERT INTO users(username,name,pass_hash,role,created_at) VALUES(?,?,?,?,?)', [$un, post_str('name', 80), password_hash($pw, PASSWORD_DEFAULT), 'admin', date('Y-m-d H:i:s')]);
                flash('Kullanıcı eklendi.');
            }
        } elseif ($do === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)$me['id']) {
                flash('Kendi hesabınızı silemezsiniz.', 'err');
            } else {
                q('DELETE FROM users WHERE id = ?', [$id]);
                flash('Kullanıcı silindi.');
            }
        }
        redirect_to('users');
    }
    ahead('Kullanıcılar', 'users', 'Yönetim paneline girebilen hesaplar');
    echo '<div class="grid g2"><div class="card"><h2>Hesaplar</h2><table><tbody>';
    foreach (rows('SELECT * FROM users ORDER BY id') as $u) {
        echo '<tr><td><b>' . h($u['username']) . '</b>' . ((int)$u['id'] === (int)$me['id'] ? ' <span class="pill ok">siz</span>' : '') . '<div class="hint">' . h($u['name']) . ' · son giriş: ' . h($u['last_login'] ?: '—') . '</div></td><td class="actions">'
            . ((int)$u['id'] !== (int)$me['id'] ? '<form method="post">' . csrf_field() . '<input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="' . $u['id'] . '"><button class="btn sm danger" data-confirm="Kullanıcı silinsin mi?">Sil</button></form>' : '') . '</td></tr>';
    }
    echo '</tbody></table></div><div>
<form method="post" class="card" autocomplete="off">' . csrf_field() . '<input type="hidden" name="do" value="password"><h2>Şifremi değiştir</h2>
<div class="field"><label class="l">Mevcut şifre</label><input type="password" name="current" autocomplete="current-password" required></div>
<div class="field"><label class="l">Yeni şifre (en az 10 karakter, harf + rakam)</label><input type="password" name="new" autocomplete="new-password" required></div>
<div class="field"><label class="l">Yeni şifre (tekrar)</label><input type="password" name="new2" autocomplete="new-password" required></div><button class="btn primary">Değiştir</button></form>
<form method="post" class="card" autocomplete="off">' . csrf_field() . '<input type="hidden" name="do" value="add"><h2>Yeni kullanıcı</h2>' . field('Ad', 'name', '') . field('Kullanıcı adı', 'username', '') . '<div class="field"><label class="l">Şifre</label><input type="password" name="password" autocomplete="new-password" required></div><button class="btn">Ekle</button></form></div></div>';
    afoot();
}

function admin_tools(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $do = (string)($_POST['do'] ?? '');
        if ($do === 'cache') {
            cache_clear();
            flash('Sayfa önbelleği temizlendi.');
        } elseif ($do === 'mailtest') {
            $to = post_str('to', 160);
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                flash('Geçerli bir e-posta girin.', 'err');
            } else {
                [$ok, $err] = mail_send($to, 'Uzman Cosmetic — test e-postası', "Bu e-posta yönetim panelinden gönderilen bir testtir.\n" . date('c'));
                $ok ? flash('Test e-postası gönderildi: ' . $to) : flash('Gönderilemedi: ' . $err, 'err');
            }
        }
        redirect_to('tools');
    }
    ahead('Araçlar', 'tools', 'Önbellek, yedek, e-posta testi, sistem bilgisi');
    $tok = csrf_token();
    echo '<div class="grid g2"><div class="card"><h2>Önbellek</h2><p class="hint">Sayfalar hızlı açılsın diye önbelleğe alınır; her kayıtta otomatik temizlenir. Sorun olursa elle temizleyin.</p><form method="post">' . csrf_field() . '<input type="hidden" name="do" value="cache"><button class="btn">Önbelleği temizle</button></form></div>
<div class="card"><h2>Yedek</h2><p class="hint">Veritabanı tüm metinleri, ürünleri ve başvuruları içerir. Düzenli indirip saklayın.</p><a class="btn" href="' . admin_url('backup', ['kind' => 'db', 't' => $tok]) . '">Veritabanını indir</a> <a class="btn" href="' . admin_url('backup', ['kind' => 'uploads', 't' => $tok]) . '">Yüklenen dosyaları indir (ZIP)</a></div>
<div class="card" id="mailtest"><h2>E-posta testi</h2><form method="post">' . csrf_field() . '<input type="hidden" name="do" value="mailtest">' . field('Alıcı', 'to', setting('notify_email'), 'email') . '<button class="btn">Test gönder</button></form></div>
<div class="card"><h2>Sistem</h2><table><tbody>';
    $sys = ['PHP' => PHP_VERSION, 'SQLite' => (string)val('select sqlite_version()'), 'GD / WebP' => extension_loaded('gd') ? (function_exists('imagewebp') ? 'var / WebP destekli' : 'var / WebP yok') : 'yok', 'Sürüm' => UZ_VERSION,
        'Veritabanı' => round(filesize(data_dir() . '/site.sqlite') / 1024) . ' KB', 'Boş disk' => round((float)@disk_free_space(UZ_ROOT) / 1048576) . ' MB', 'Site adresi' => base_url()];
    foreach ($sys as $k => $v) {
        echo '<tr><th style="width:140px">' . h($k) . '</th><td>' . h((string)$v) . '</td></tr>';
    }
    echo '</tbody></table></div></div>';
    afoot();
}

function admin_backup(): void
{
    if (!hash_equals(csrf_token(), (string)($_GET['t'] ?? ''))) {
        http_response_code(400);
        exit('Geçersiz istek.');
    }
    $kind = (string)($_GET['kind'] ?? 'db');
    if ($kind === 'db') {
        $tmp = tempnam(sys_get_temp_dir(), 'uzdb');
        @unlink($tmp);
        db()->exec('VACUUM INTO ' . db()->quote($tmp));
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="uzman-yedek-' . date('Y-m-d') . '.sqlite"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        return;
    }
    if (!class_exists('ZipArchive')) {
        exit('Sunucuda ZipArchive yok; uploads/ klasörünü cPanel Dosya Yöneticisi ile sıkıştırıp indirin.');
    }
    $tmp = tempnam(sys_get_temp_dir(), 'uzzip');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::OVERWRITE);
    $base = UZ_ROOT . '/uploads';
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && !in_array($f->getFilename(), ['.htaccess', 'index.php', '.gitignore'], true)) {
            $zip->addFile($f->getPathname(), 'uploads/' . substr($f->getPathname(), strlen($base) + 1));
        }
    }
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="uzman-uploads-' . date('Y-m-d') . '.zip"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
}
