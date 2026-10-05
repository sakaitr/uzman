<?php
declare(strict_types=1);

function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** For attribute values that may already contain entities (&amp;): escape only quotes and bare <, >. */
function attr($s): string
{
    return str_replace(['"', '<', '>'], ['&quot;', '&lt;', '&gt;'], (string)$s);
}

/** Strip HTML text to plain text (for meta descriptions). */
function plain($s): string
{
    return trim(html_entity_decode(strip_tags((string)$s), ENT_QUOTES, 'UTF-8'));
}

/** Allow a tiny, safe subset of inline HTML in editable text. */
function clean_html(string $s): string
{
    $s = preg_replace('#<(script|style|iframe|object|embed)\b.*?</\1>#is', '', $s);
    $s = strip_tags($s, '<em><strong><b><i><br><a><u><span><small>');
    $s = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $s);
    $s = preg_replace('#(href|src)\s*=\s*(["\'])\s*(javascript|data):#i', '$1=$2#', $s);
    return trim($s);
}

/** Pick the string for $lang from a {lang: text} array (falls back to TR, then to anything). */
function ml($v, ?string $lang = null): string
{
    if (!is_array($v)) {
        return (string)$v;
    }
    $lang = $lang ?? ($GLOBALS['UZ_LANG'] ?? DEFAULT_LANG);
    foreach ([$lang, DEFAULT_LANG, 'en'] as $l) {
        if (isset($v[$l]) && trim((string)$v[$l]) !== '') {
            return (string)$v[$l];
        }
    }
    foreach ($v as $x) {
        if (trim((string)$x) !== '') {
            return (string)$x;
        }
    }
    return '';
}

function jd($json, $default = [])
{
    if (is_array($json)) {
        return $json;
    }
    $d = json_decode((string)$json, true);
    return is_array($d) ? $d : $default;
}

function je($v): string
{
    return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
}

function slugify(string $s): string
{
    $map = ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'Ç' => 'c', 'Ğ' => 'g', 'İ' => 'i', 'Ö' => 'o', 'Ş' => 's', 'Ü' => 'u'];
    $s = strtr($s, $map);
    $s = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $s));
    return trim($s, '-');
}

/** Per-request translated string. */
function t(string $key): string
{
    static $cache = [];
    $lang = $GLOBALS['UZ_LANG'] ?? DEFAULT_LANG;
    if (!isset($cache[$lang])) {
        $cache[$lang] = [];
        $fallback = [];
        foreach (db()->query("SELECT k, lang, v FROM strings WHERE lang IN ('" . $lang . "','tr')") as $r) {
            if ($r['lang'] === $lang && trim($r['v']) !== '') {
                $cache[$lang][$r['k']] = $r['v'];
            } elseif ($r['lang'] === 'tr') {
                $fallback[$r['k']] = $r['v'];
            }
        }
        $cache[$lang] = $cache[$lang] + $fallback;
    }
    return $cache[$lang][$key] ?? '';
}

function setting(string $key, $default = '')
{
    static $all = null;
    if ($all === null) {
        $all = [];
        try {
            foreach (db()->query('SELECT k, v FROM settings') as $r) {
                $all[$r['k']] = $r['v'];
            }
        } catch (Throwable $e) {
            return $default;
        }
    }
    return $all[$key] ?? $default;
}

function set_setting(string $key, string $value): void
{
    $st = db()->prepare('INSERT INTO settings(k, v) VALUES(?, ?) ON CONFLICT(k) DO UPDATE SET v = excluded.v');
    $st->execute([$key, $value]);
}

function cache_clear(): void
{
    foreach (glob(data_dir() . '/cache/*.html') ?: [] as $f) {
        @unlink($f);
    }
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function random_token(int $bytes = 24): string
{
    return bin2hex(random_bytes($bytes));
}

/** Image intrinsic size (cached per request). */
function img_size(string $path): array
{
    static $c = [];
    if (!isset($c[$path])) {
        $f = UZ_ROOT . '/' . ltrim($path, '/');
        $s = is_file($f) ? @getimagesize($f) : false;
        $c[$path] = $s ? [$s[0], $s[1]] : [0, 0];
    }
    return $c[$path];
}

function log_activity(string $text, string $route = ''): void
{
    try {
        $u = function_exists('admin_user') ? admin_user() : null;
        q('INSERT INTO activity(ts, who, route, text, ip) VALUES(?,?,?,?,?)', [date('Y-m-d H:i:s'), $u['username'] ?? '', $route, mb_substr($text, 0, 300), client_ip()]);
        if (random_int(1, 60) === 1) {
            q('DELETE FROM activity WHERE id NOT IN (SELECT id FROM activity ORDER BY id DESC LIMIT 2000)');
        }
    } catch (Throwable $e) {
        error_log('activity log failed: ' . $e->getMessage());
    }
}

/** Where is this uploaded/static file referenced? Returns human-readable places. */
function media_usage(string $path): array
{
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $path) . '%';
    $use = [];
    foreach (rows("SELECT k FROM settings WHERE v LIKE ? ESCAPE '\\'", [$like]) as $r) {
        $use[] = 'Ayar: ' . $r['k'];
    }
    foreach (rows('SELECT i.cap, s.slug FROM items i JOIN subcats s ON s.id = i.sub_id WHERE i.image = ?', [$path]) as $r) {
        $use[] = 'Ürün görseli (' . $r['slug'] . ')';
    }
    foreach (rows('SELECT label FROM pl_models WHERE image = ?', [$path]) as $r) {
        $use[] = 'Tüp modeli: ' . $r['label'];
    }
    foreach (rows('SELECT label FROM hero_slides WHERE image = ?', [$path]) as $r) {
        $use[] = 'Slider: ' . $r['label'];
    }
    foreach (rows('SELECT id FROM docs WHERE image = ? OR file = ?', [$path, $path]) as $r) {
        $use[] = 'Belge #' . $r['id'];
    }
    foreach (rows("SELECT slug FROM pages WHERE blocks LIKE ? ESCAPE '\\'", [$like]) as $r) {
        $use[] = 'Sayfa: ' . $r['slug'];
    }
    return array_values(array_unique($use));
}

/** CSV helpers with the escape character set explicitly (PHP 8.4 deprecates the implicit default). */
function csv_put($fh, array $row): void
{
    fputcsv($fh, $row, ',', '"', '\\');
}

function csv_get($fh, string $delim = ',')
{
    return fgetcsv($fh, 0, $delim, '"', '\\');
}
