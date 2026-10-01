<?php
/** Scheduled tasks. cPanel → Cron Jobs:  curl -s "https://DOMAIN/api/cron.php?key=KEY&task=weekly"  (KEY is shown in admin → İzleme ayarları) */
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require UZ_APP . '/security.php';
require_once UZ_APP . '/view.php';

header('Content-Type: text/plain; charset=utf-8');
if (!is_installed() || !hash_equals(cron_key(), (string)($_GET['key'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}
maybe_upgrade();
$task = in_array($_GET['task'] ?? '', ['weekly', 'monthly', 'all'], true) ? $_GET['task'] : 'all';
echo growth_cron($task) . "\n";
