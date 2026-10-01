<?php
declare(strict_types=1);

/** Requirements check shown by the setup screen. */
function requirements(): array
{
    $r = [];
    $r['PHP ≥ 7.4'] = version_compare(PHP_VERSION, '7.4.0', '>=');
    $r['PDO SQLite'] = extension_loaded('pdo_sqlite');
    $r['mbstring'] = extension_loaded('mbstring');
    $r['GD (resim işleme)'] = extension_loaded('gd');
    $r['fileinfo'] = extension_loaded('fileinfo');
    $r['data/ yazılabilir'] = is_dir(UZ_ROOT . '/data') ? is_writable(UZ_ROOT . '/data') : is_writable(UZ_ROOT);
    $r['uploads/ yazılabilir'] = is_dir(UZ_ROOT . '/uploads') ? is_writable(UZ_ROOT . '/uploads') : is_writable(UZ_ROOT);
    return $r;
}

function install_site(string $username, string $password, string $name = 'Yönetici'): void
{
    foreach (['data', 'data/cache', 'data/sessions', 'data/logs', 'uploads'] as $d) {
        if (!is_dir(UZ_ROOT . '/' . $d)) {
            mkdir(UZ_ROOT . '/' . $d, 0775, true);
        }
    }
    $cfg = UZ_ROOT . '/data/config.php';
    if (!is_file($cfg)) {
        $body = "<?php\nreturn " . var_export(['secret' => bin2hex(random_bytes(32)), 'installed_at' => date('c')], true) . ";\n";
        file_put_contents($cfg, $body, LOCK_EX);
    }
    migrate();
    seed_import();
    if (!val('SELECT COUNT(*) FROM users')) {
        q('INSERT INTO users(username, name, pass_hash, role, created_at) VALUES(?,?,?,?,?)',
            [$username, $name, password_hash($password, PASSWORD_DEFAULT), 'admin', date('Y-m-d H:i:s')]);
    }
}
