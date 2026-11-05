<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

repo_cleanup();
$cache = APP_ROOT . '/storage/cache';
foreach (glob($cache . '/rate-*.json') ?: [] as $file) {
    if (filemtime($file) < time() - 86400) {
        unlink($file);
    }
}
echo "ok\n";
