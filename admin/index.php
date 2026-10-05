<?php
/** Admin panel front controller (Turkish UI). */
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require UZ_APP . '/security.php';
require UZ_APP . '/auth.php';
require UZ_APP . '/install.php';
require UZ_APP . '/upload.php';
require UZ_APP . '/mail.php';
require UZ_APP . '/admin/ui.php';

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

$a = (string)($_GET['a'] ?? 'dash');

// ---------------------------------------------------------------- first-run setup
if (!is_installed()) {
    require UZ_APP . '/admin/setup.php';
    admin_setup();
    exit;
}

maybe_upgrade();
enforce_canonical();
start_session();
if ($a === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php?a=login');
    exit;
}
if (!admin_user()) {
    require UZ_APP . '/admin/login.php';
    if ($a === 'forgot') {
        admin_forgot_page();
    } elseif ($a === 'reset') {
        admin_reset_page();
    } else {
        admin_login_page();
    }
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();
    log_activity('POST ' . $a . (isset($_POST['do']) ? ' / ' . preg_replace('/[^a-z_]/', '', (string)$_POST['do']) : ''), $a);
}
if (!is_admin_role() && in_array($a, admin_only_routes(), true)) {
    flash('Bu bölüm yalnızca yöneticiler içindir.', 'err');
    header('Location: index.php?a=dash');
    exit;
}

$routes = [
    'dash' => ['dash.php', 'admin_dash'], 'pages' => ['pages.php', 'admin_pages'], 'page_edit' => ['pages.php', 'admin_page_edit'], 'page_new' => ['pages.php', 'admin_page_new'],
    'strings' => ['strings.php', 'admin_strings'], 'catalog' => ['catalog.php', 'admin_catalog'], 'cat_edit' => ['catalog.php', 'admin_cat_edit'], 'sub_edit' => ['catalog.php', 'admin_sub_edit'],
    'tubes' => ['lists.php', 'admin_tubes'], 'hero' => ['lists.php', 'admin_hero'], 'docs' => ['lists.php', 'admin_docs'],
    'media' => ['media.php', 'admin_media'], 'media_json' => ['media.php', 'admin_media_json'], 'upload' => ['media.php', 'admin_upload'],
    'subs' => ['subs.php', 'admin_subs'], 'sub_view' => ['subs.php', 'admin_sub_view'], 'subs_csv' => ['subs.php', 'admin_subs_csv'],
    'seo' => ['seo.php', 'admin_seo'], 'seo_pages' => ['seo.php', 'admin_seo_pages'], 'seo_settings' => ['seo.php', 'admin_seo_settings'], 'seo_preview' => ['seo.php', 'admin_seo_preview'],
    'growth' => ['growth.php', 'admin_growth'], 'actions' => ['growth.php', 'admin_actions'], 'action_edit' => ['growth.php', 'admin_action_edit'], 'ads' => ['growth.php', 'admin_ads'], 'ad_edit' => ['growth.php', 'admin_ad_edit'],
    'reports' => ['growth.php', 'admin_reports'], 'tracking' => ['growth.php', 'admin_tracking'], 'golive' => ['golive.php', 'admin_golive'],
    'strings_csv' => ['strings.php', 'admin_strings_csv'], 'redirects' => ['pages.php', 'admin_redirects'],
    'brand' => ['brand.php', 'admin_brand'],
    'settings' => ['settings.php', 'admin_settings'], 'users' => ['users.php', 'admin_users'], 'tools' => ['users.php', 'admin_tools'], 'backup' => ['users.php', 'admin_backup'],
];
if (!isset($routes[$a])) {
    http_response_code(404);
    $a = 'dash';
}
require_once UZ_APP . '/view.php';
require UZ_APP . '/admin/' . $routes[$a][0];
$routes[$a][1]();
