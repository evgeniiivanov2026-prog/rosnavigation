<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require dirname(__DIR__) . '/inc/bootstrap.php';

if (app_demo() || DB_NAME === '') {
    fwrite(STDOUT, "Боевая база не настроена, дамп пропущен.\n");
    exit(0);
}

$dir = dirname(__DIR__) . '/storage/backups';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$file = $dir . '/db-' . date('Y-m-d') . '.sql.gz';
$cmd = sprintf(
    'mysqldump --single-transaction -h%s -u%s -p%s %s | gzip > %s',
    escapeshellarg(DB_HOST),
    escapeshellarg(DB_USER),
    escapeshellarg(DB_PASS),
    escapeshellarg(DB_NAME),
    escapeshellarg($file)
);
exec($cmd, $output, $code);
if ($code !== 0) {
    fwrite(STDERR, "mysqldump завершился с кодом {$code}\n");
    exit(1);
}
foreach (glob($dir . '/db-*.sql.gz') ?: [] as $old) {
    if (filemtime($old) < time() - 14 * 86400) {
        unlink($old);
    }
}
fwrite(STDOUT, "бэкап: {$file}\n");
