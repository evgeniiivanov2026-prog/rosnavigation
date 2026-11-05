<?php
declare(strict_types=1);

function cache_file(string $key): string
{
    return dirname(__DIR__) . '/storage/cache/' . hash('sha256', $key) . '.json';
}

function cache_get(string $key, int $ttl): ?array
{
    $file = cache_file($key);
    if (!is_file($file)) {
        return null;
    }
    if (filemtime($file) + $ttl < time()) {
        return null;
    }
    $data = file_get_contents($file);
    if ($data === false || $data === '') {
        return null;
    }
    $decoded = json_decode($data, true);
    return is_array($decoded) ? $decoded : null;
}

function cache_set(string $key, array $value): void
{
    $dir = dirname(__DIR__) . '/storage/cache';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $tmp = $dir . '/' . bin2hex(random_bytes(8)) . '.tmp';
    file_put_contents($tmp, json_encode($value, JSON_UNESCAPED_UNICODE));
    rename($tmp, cache_file($key));
}

function cache_purge_expired(int $maxAge = 86400): int
{
    $dir = dirname(__DIR__) . '/storage/cache';
    if (!is_dir($dir)) {
        return 0;
    }
    $n = 0;
    foreach (glob($dir . '/*.json') ?: [] as $file) {
        if (filemtime($file) < time() - $maxAge) {
            unlink($file);
            $n++;
        }
    }
    return $n;
}
