<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

if (DB_HOST === '' || DB_NAME === '' || DB_USER === '') {
    echo "skip: база не настроена\n";
    exit(0);
}
$dir = dirname(__DIR__) . '/storage/backups';
if (!is_dir($dir)) {
    mkdir($dir, 0750, true);
}
$file = $dir . '/db-' . date('Y-m-d') . '.sql.gz';
$cnf = tempnam(sys_get_temp_dir(), 'rosnav');
if ($cnf === false) {
    fwrite(STDERR, "нет временного файла\n");
    exit(1);
}
file_put_contents($cnf, "[client]\nuser=" . DB_USER . "\npassword=" . DB_PASS . "\nhost=" . DB_HOST . "\n");
chmod($cnf, 0600);
$cmd = sprintf(
    'mysqldump --defaults-extra-file=%s --single-transaction %s | gzip > %s',
    escapeshellarg($cnf),
    escapeshellarg(DB_NAME),
    escapeshellarg($file)
);
exec($cmd, $output, $code);
unlink($cnf);
foreach (glob($dir . '/db-*.sql.gz') ?: [] as $old) {
    if (filemtime($old) < time() - 14 * 86400) {
        unlink($old);
    }
}
exit($code);
