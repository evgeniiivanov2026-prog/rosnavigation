<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

if (DEMO_MODE || !defined('DB_NAME') || !defined('DB_USER')) {
    fwrite(STDERR, "База не подключена, дамп пропущен.\n");
    exit(0);
}

$dir = APP_ROOT . '/storage/backups';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$file = $dir . '/db-' . date('Y-m-d') . '.sql.gz';
$cmd = sprintf(
    'mysqldump --single-transaction -h%s -u%s %s | gzip > %s',
    escapeshellarg(DB_HOST),
    escapeshellarg(DB_USER),
    escapeshellarg(DB_NAME),
    escapeshellarg($file)
);
putenv('MYSQL_PWD=' . (defined('DB_PASS') ? DB_PASS : ''));
exec($cmd, $output, $code);
putenv('MYSQL_PWD');
if ($code !== 0) {
    fwrite(STDERR, "mysqldump завершился с ошибкой\n");
    exit(1);
}
foreach (glob($dir . '/db-*.sql.gz') ?: [] as $old) {
    if (filemtime($old) < time() - 14 * 86400) {
        unlink($old);
    }
}
echo "backup\n";
