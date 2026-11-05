<?php
declare(strict_types=1);

function rate_hit(string $bucket, int $limit, int $windowSec): bool
{
    $dir = APP_ROOT . '/storage/cache';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return true;
    }
    $file = $dir . '/rate-' . sha1($bucket) . '.json';
    $fh = fopen($file, 'c+');
    if (!$fh) {
        return true;
    }
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $stamps = json_decode($raw ?: '[]', true);
    if (!is_array($stamps)) {
        $stamps = [];
    }
    $now = time();
    $stamps = array_values(array_filter(
        $stamps,
        static fn ($stamp): bool => is_numeric($stamp) && (int) $stamp > $now - $windowSec
    ));
    $allowed = count($stamps) < $limit;
    if ($allowed) {
        $stamps[] = $now;
    }
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($stamps));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $allowed;
}
