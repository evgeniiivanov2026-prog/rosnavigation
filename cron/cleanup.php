<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require dirname(__DIR__) . '/inc/bootstrap.php';

if (app_demo()) {
    demo_boot();
    fwrite(STDOUT, "demo: просроченные сигналы сняты при чтении\n");
    exit(0);
}

$stmt = db()->prepare("UPDATE sos_signals SET status = 'cancelled' WHERE status = 'active' AND expires_at < NOW()");
$stmt->execute();
fwrite(STDOUT, 'снято сигналов: ' . $stmt->rowCount() . "\n");

$cache = dirname(__DIR__) . '/storage/cache';
foreach (glob($cache . '/*.json') ?: [] as $file) {
    if (filemtime($file) < time() - 86400) {
        unlink($file);
    }
}
