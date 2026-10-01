<?php
// Local dev only:  php -S localhost:8766 tools/dev-router.php   (mimics .htaccess rewrites)
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$root = getenv('UZ_ROOT') ?: dirname(__DIR__);
$prefix = rtrim((string)getenv('UZ_PREFIX'), '/');   // e.g. /uzmanp to test sub-folder installs
if ($prefix !== '' && strpos($path, $prefix) === 0) { $path = substr($path, strlen($prefix)) ?: '/'; }
if ($path !== '/' && is_dir($root . $path) && is_file(rtrim($root . $path, '/') . '/index.php') && !preg_match('#^/(app|data|tools|build)(/|$)#', $path)) {
    $path = rtrim($path, '/') . '/index.php';
}
if ($path !== '/' && is_file($root . $path) && !preg_match('#^/(app|data|tools|build)/#', $path)) {
    if (substr($path, -4) === '.php') { chdir(dirname($root . $path)); $_SERVER['SCRIPT_NAME'] = $prefix . $path; require $root . $path; return true; }
    return false;
}
if (preg_match('#^/(app|data|tools|build)(/|$)#', $path)) { http_response_code(403); exit('forbidden'); }
$_SERVER['SCRIPT_NAME'] = $prefix . '/index.php';
if ($path === '/sitemap.xml') { $_GET['x'] = 'sitemap'; }
elseif ($path === '/robots.txt') { $_GET['x'] = 'robots'; }
elseif ($path === '/llms.txt') { $_GET['x'] = 'llms'; }
elseif ($path === '/llms-full.txt') { $_GET['x'] = 'llmsfull'; }
elseif (preg_match('#^/(en|fr|ar|ru)/?$#', $path, $m)) { $_GET['l'] = $m[1]; $_GET['p'] = 'index'; }
elseif (preg_match('#^/(en|fr|ar|ru)/([a-z0-9-]+)\.html$#', $path, $m)) { $_GET['l'] = $m[1]; $_GET['p'] = $m[2]; }
elseif (preg_match('#^/([a-z0-9-]+)\.html$#', $path, $m)) { $_GET['l'] = 'tr'; $_GET['p'] = $m[1]; }
chdir($root);
require $root . '/index.php';
