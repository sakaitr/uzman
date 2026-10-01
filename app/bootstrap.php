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

require_once UZ_APP . '/helpers.php';
require_once UZ_APP . '/db.php';
require_once UZ_APP . '/seo.php';
require_once UZ_APP . '/growth.php';

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
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
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
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
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
}
