<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require UZ_APP . '/security.php';
require UZ_APP . '/mail.php';

if (!is_installed() || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['ok' => false, 'error' => 'method'], 405);
}
$age = form_token_age((string)($_POST['token'] ?? ''));
if ($age < 0 || $age > 7200) {
    json_out(['ok' => false, 'error' => 'token'], 400);
}
// honeypot or machine-speed submission: pretend success, store nothing
if (trim((string)($_POST['website'] ?? '')) !== '' || $age < 2) {
    json_out(['ok' => true]);
}
if (throttle_count('quote', 3600) >= 5) {
    json_out(['ok' => false, 'error' => 'rate'], 429);
}
$f = function (string $k, int $max) {
    return mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)($_POST[$k] ?? ''))), 0, $max);
};
$name = $f('name', 120);
$company = $f('company', 160);
$email = $f('email', 160);
$phone = $f('phone', 40);
$category = $f('category', 160);
$market = $f('market', 120);
$qty = $f('qty', 80);
$brief = $f('brief', 4000);
$lang = in_array($_POST['lang'] ?? '', LANGS, true) ? $_POST['lang'] : DEFAULT_LANG;
if ($name === '' || $company === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($_POST['consent'])) {
    json_out(['ok' => false, 'error' => 'invalid'], 422);
}
throttle_hit('quote');
$at = function (string $k, int $n = 80): string {
    return mb_substr(preg_replace('/[^\p{L}\p{N}_.\-: \/]/u', '', (string)($_POST[$k] ?? '')), 0, $n);
};
$cid = in_array($_POST['a_cid'] ?? '', ['gclid', 'fbclid', 'msclkid'], true) ? $_POST['a_cid'] : '';
$refHost = strtolower(preg_replace('/[^a-z0-9.\-]/i', '', (string)($_POST['a_ref'] ?? '')));
[$channel] = classify_channel($at('a_medium'), $at('a_source'), $cid, $refHost, (string)preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
q('INSERT INTO submissions(created_at, lang, name, company, email, phone, category, market, qty, brief, ip, ua, utm_source, utm_medium, utm_campaign, utm_term, utm_content, click_id, ref_host, landing, channel) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
    [date('Y-m-d H:i:s'), $lang, $name, $company, $email, $phone, $category, $market, $qty, $brief, client_ip(), mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250),
     $at('a_source'), $at('a_medium'), $at('a_campaign', 60), $at('a_term', 100), $at('a_content', 100), $cid, $refHost, $at('a_landing', 120), $channel]);
$id = (int)db()->lastInsertId();

$to = trim((string)setting('notify_email')) ?: (string)setting('email');
$body = "Yeni teklif / numune talebi (#$id)\n\nAd Soyad : $name\nFirma    : $company\nE-posta  : $email\nTelefon  : $phone\nKategori : $category\nPazar    : $market\nAdet     : $qty\nDil      : $lang\nKaynak   : " . ($channel ?: 'direct') . ($at('a_campaign') !== '' ? ' / ' . $at('a_campaign') : '') . "\n\nProje:\n$brief\n\n— uzmancosmetic web sitesi";
[$ok, $err] = $to !== '' ? mail_send($to, "Yeni talep: $company — $name", $body, $email) : [false, 'alıcı yok'];
q('UPDATE submissions SET mail_ok = ?, note = ? WHERE id = ?', [$ok ? 1 : 0, $ok ? '' : mb_substr($err, 0, 300), $id]);
json_out(['ok' => true]);
