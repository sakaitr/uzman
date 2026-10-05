<?php
/**
 * Uzman Cosmetic CMS — bootstrap. PHP 7.4+, PDO SQLite. No external services needed (runs on plain cPanel hosting).
 */
declare(strict_types=1);

define('UZ_ROOT', dirname(__DIR__));
define('UZ_APP', __DIR__);
define('UZ_VERSION', '1.0.0');

const LANGS = ['tr', 'en', 'fr', 'ar', 'ru'];
const LANG_LABEL = ['tr' => 'TR', 'en' => 'EN', 'fr' => 'FR', 'ar' => 'AR', 'ru' => 'RU'];
const LANG_NAME = ['tr' => 'Türkçe', 'en' => 'English', 'fr' => 'Français', 'ar' => 'العربية', 'ru' => 'Русский'];
const DEFAULT_LANG = 'tr';
const RTL_LANGS = ['ar'];
const UNIT = ['tr' => 'ml', 'en' => 'ml', 'fr' => 'ml', 'ar' => 'مل', 'ru' => 'мл'];
const THEMES = [
    'noir' => ['Noir & Honey', '#0b0c0e'], 'bordeaux' => ['Bordeaux Rose Gold', '#1c080d'], 'emerald' => ['Emerald & Rose Gold', '#03211b'],
    'twotone' => ['Two-Tone Bordeaux', '#021813'], 'inverse' => ['Bordeaux × Emerald', '#1c080d'],
];
const SYSTEM_PAGES = ['index', 'private-label', 'products', 'body-care', 'home-care', 'about', 'contact'];

mb_internal_encoding('UTF-8');

/** Production safety: no PHP errors on screen, everything goes to data/logs/php-error.log, friendly 500 page. Set UZ_DEBUG=1 for development. */
function uz_boot_errors(): void
{
    if (getenv('UZ_DEBUG')) {
        return;
    }
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    $dir = UZ_ROOT . '/data/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (is_dir($dir) && is_writable($dir)) {
        $f = $dir . '/php-error.log';
        if (is_file($f) && filesize($f) > 2 * 1024 * 1024) {
            @rename($f, $dir . '/php-error-' . date('Ymd-His') . '.log');
        }
        ini_set('error_log', $f);
    }
    set_exception_handler(function ($e) {
        error_log('UNCAUGHT ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        if (!headers_sent()) {
            http_response_code(500);
        }
        $api = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', 'a=upload') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', 'media_json') !== false;
        if ($api) {
            header('Content-Type: application/json; charset=utf-8');
            echo '{"ok":false,"error":"server"}';
            return;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>500</title><style>body{font-family:system-ui,sans-serif;background:#0b0c0e;color:#f4f1ea;display:grid;place-items:center;min-height:100vh;margin:0;text-align:center}main{max-width:460px;padding:24px}h1{font-weight:400}p{color:#a9a49b;line-height:1.6}</style></head><body><main><h1>500</h1><p>Beklenmeyen bir sorun oluştu. Lütfen biraz sonra tekrar deneyin.<br>An unexpected error occurred. Please try again shortly.</p></main></body></html>';
    });
}
uz_boot_errors();

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

require_once UZ_APP . '/helpers.php';
require_once UZ_APP . '/db.php';
require_once UZ_APP . '/seo.php';
require_once UZ_APP . '/growth.php';
require_once UZ_APP . '/brand.php';
require_once UZ_APP . '/backup.php';

/** Data directory (database, cache, logs). Can be moved outside the web root through data/config.php => 'data_dir'. */
function data_dir(): string
{
    static $dir = null;
    if ($dir === null) {
        $dir = UZ_ROOT . '/data';
        $cfg = UZ_ROOT . '/data/config.php';
        if (is_file($cfg)) {
            $c = require $cfg;
            if (is_array($c) && !empty($c['data_dir']) && is_dir($c['data_dir'])) {
                $dir = rtrim($c['data_dir'], '/');
            }
        }
    }
    return $dir;
}

function config(string $key, $default = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $f = UZ_ROOT . '/data/config.php';
        $cfg = is_file($f) ? (array)(require $f) : [];
    }
    return $cfg[$key] ?? $default;
}

function is_installed(): bool
{
    return is_file(data_dir() . '/site.sqlite') && is_file(UZ_ROOT . '/data/config.php');
}

/** Absolute URL of the site root (works in a sub-folder too, e.g. https://demo.example.com/uzmanp/). */
function base_url(): string
{
    static $u = null;
    if ($u === null) {
        $https = is_https();
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        // admin/ and api/ scripts live one level below the root
        if (preg_match('#/(admin|api)$#', $dir)) {
            $dir = dirname($dir);
        }
        $dir = rtrim($dir, '/');
        $u = ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/';
    }
    return $u;
}

function canonical_base(): string
{
    $s = trim((string)setting('site_url', ''));
    return $s !== '' ? rtrim($s, '/') . '/' : base_url();
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = is_https();
    session_name('uzsess');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    $dir = data_dir() . '/sessions';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (is_dir($dir) && is_writable($dir)) {
        session_save_path($dir);
    }
    session_start();
    // idle timeout: 8 hours without activity ends the session
    if (!empty($_SESSION['last']) && time() - (int)$_SESSION['last'] > 28800) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last'] = time();
}

/** 301 to https and/or the canonical host when enabled in Ayarlar → SEO (never on localhost / IPs; ?noredirect=1 bypasses). */
function enforce_canonical(): void
{
    if (PHP_SAPI === 'cli' || isset($_GET['noredirect'])) {
        return;
    }
    $host = strtolower(explode(':', (string)($_SERVER['HTTP_HOST'] ?? ''))[0]);
    if ($host === '' || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
        return;
    }
    $p = parse_url(trim((string)setting('site_url', '')));
    if (empty($p['host'])) {
        return;
    }
    $needHttps = setting('force_https', '0') === '1' && ($p['scheme'] ?? '') === 'https' && !is_https();
    $needHost = setting('force_host', '0') === '1' && $host !== strtolower($p['host']);
    if (!$needHttps && !$needHost) {
        return;
    }
    $scheme = (setting('force_https', '0') === '1' && ($p['scheme'] ?? '') === 'https') || is_https() ? 'https' : 'http';
    header('Location: ' . $scheme . '://' . ($needHost ? strtolower($p['host']) : $host) . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

/** Notification recipients (comma / semicolon / space separated, validated). */
function notify_recipients(): array
{
    $raw = (string)setting('notify_email', '') ?: (string)setting('email', '');
    return array_values(array_unique(array_filter(preg_split('/[\s,;]+/', $raw) ?: [], function ($e) { return filter_var($e, FILTER_VALIDATE_EMAIL); })));
}
