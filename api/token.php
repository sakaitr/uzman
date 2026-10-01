<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require UZ_APP . '/security.php';
if (!is_installed()) {
    json_out(['ok' => false], 503);
}
json_out(['ok' => true, 'token' => form_token()]);
