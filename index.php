<?php
/** Public front controller: pages, sitemap.xml, robots.txt. */
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require UZ_APP . '/view.php';

if (!is_installed()) {
    header('Location: ' . base_url() . 'admin/');
    exit;
}

maybe_upgrade();
$x = $_GET['x'] ?? '';
if ($x === 'robots') {
    header('Content-Type: text/plain; charset=utf-8');
    echo robots_txt();
    exit;
}
if ($x === 'llms' || $x === 'llmsfull') {
    $on = seo('seo_llms') === '1' && ($x === 'llms' || seo('seo_llms_full') === '1');
    if (!$on || setting('robots_index', '1') !== '1') {
        http_response_code(404);
        exit;
    }
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    echo $x === 'llms' ? llms_txt() : llms_full_txt();
    exit;
}
if ($x === 'sitemap') {
    header('Content-Type: application/xml; charset=utf-8');
    $site = canonical_base();
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
    foreach (rows('SELECT slug, updated_at FROM pages WHERE status = 1 AND noindex = 0 ORDER BY sort') as $p) {
        foreach (LANGS as $l) {
            echo "<url><loc>" . h($site . ($l === DEFAULT_LANG ? '' : $l . '/') . $p['slug'] . '.html') . "</loc>" . ($p['updated_at'] ? '<lastmod>' . date('Y-m-d', (int)strtotime($p['updated_at'])) . '</lastmod>' : '');
            foreach (LANGS as $l2) {
                echo '<xhtml:link rel="alternate" hreflang="' . $l2 . '" href="' . h($site . ($l2 === DEFAULT_LANG ? '' : $l2 . '/') . $p['slug'] . '.html') . '"/>';
            }
            echo "</url>\n";
        }
    }
    echo "</urlset>\n";
    exit;
}

$lang = $_GET['l'] ?? DEFAULT_LANG;
$slug = $_GET['p'] ?? 'index';
if (!in_array($lang, LANGS, true) || !preg_match('/^[a-z0-9-]{1,60}$/', $slug)) {
    $lang = DEFAULT_LANG;
    $slug = '__missing__';
}

$cacheDir = data_dir() . '/cache';
$cacheFile = $cacheDir . '/' . substr(md5((string)($_SERVER['HTTP_HOST'] ?? '')), 0, 8) . '__' . $lang . '__' . $slug . '.html';
$useCache = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !isset($_GET['theme']);
if ($useCache && is_file($cacheFile)) {
    $html = (string)file_get_contents($cacheFile);
    $status = 200;
} else {
    [$status, $html] = render_page($lang, $slug);
    if ($status === 200 && $useCache) {
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        @file_put_contents($cacheFile, $html, LOCK_EX);
    }
}
http_response_code($status);
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
if ($status === 200) {
    $etag = '"' . md5($html) . '"';
    header('ETag: ' . $etag);
    header('Cache-Control: public, max-age=300');
    if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
        http_response_code(304);
        exit;
    }
}
echo $html;
