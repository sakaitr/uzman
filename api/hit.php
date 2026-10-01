<?php
/** Cookie-less, privacy-friendly traffic beacon: stores only daily aggregate counters (no IP, no identifiers). */
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require UZ_APP . '/security.php';

header('Cache-Control: no-store');
if (!is_installed() || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(204);
    exit;
}
$ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
if ($ua === '' || preg_match('/bot|crawl|spider|slurp|preview|headless|monitor|lighthouse|pingdom|curl|wget|python|axios|fetch|scan|facebookexternalhit|whatsapp/i', $ua)) {
    http_response_code(204);
    exit;
}
// never count signed-in administrators
if (!empty($_COOKIE['uzsess'])) {
    start_session();
    if (!empty($_SESSION['uid'])) {
        http_response_code(204);
        exit;
    }
    session_write_close();
}
$slug = (string)($_POST['s'] ?? '');
$lang = (string)($_POST['l'] ?? '');
if (!in_array($lang, LANGS, true) || !preg_match('/^[a-z0-9-]{1,60}$/', $slug) || !val('SELECT 1 FROM pages WHERE slug = ? AND status = 1', [$slug])) {
    http_response_code(204);
    exit;
}
if (throttle_count('hit', 3600) > 400) {
    http_response_code(204);
    exit;
}
throttle_hit('hit');
$clean = function (string $k, int $n = 80): string {
    return mb_substr(preg_replace('/[^\p{L}\p{N}_.\-: ]/u', '', (string)($_POST[$k] ?? '')), 0, $n);
};
$ref = strtolower(preg_replace('/[^a-z0-9.\-]/i', '', (string)($_POST['r'] ?? '')));
$cid = in_array($_POST['c'] ?? '', ['gclid', 'fbclid', 'msclkid'], true) ? $_POST['c'] : '';
[$channel, $source] = classify_channel($clean('um'), $clean('us'), $cid, $ref, (string)preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
$camp = $clean('uc', 60);
if ($camp !== '') {
    $source = 'campaign:' . $camp;
}
$new = ($_POST['n'] ?? '0') === '1' ? 1 : 0;
q('INSERT INTO visits(day, slug, lang, channel, source, sessions, views) VALUES(?,?,?,?,?,?,1) ON CONFLICT(day, slug, lang, channel, source) DO UPDATE SET sessions = sessions + excluded.sessions, views = views + 1',
    [date('Y-m-d'), $slug, $lang, $channel, mb_substr($source, 0, 70), $new]);
http_response_code(204);
