<?php
// php tools/install_cli.php <username> <password>   (alternative to the web setup screen)
require __DIR__ . '/../app/bootstrap.php';
require UZ_APP . '/install.php';
if ($argc < 3) { fwrite(STDERR, "usage: php tools/install_cli.php <username> <password>\n"); exit(1); }
install_site($argv[1], $argv[2]);
echo "installed. strings=" . val('SELECT COUNT(*) FROM strings') . " pages=" . val('SELECT COUNT(*) FROM pages') . "\n";
