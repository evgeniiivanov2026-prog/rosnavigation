<?php
declare(strict_types=1);

function cache_dir(): string
{
    $dir = dirname(__DIR__) . '/storage/cache';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function cache_file(string $key): string
{
    return cache_dir() . '/' . hash('sha256', $key) . '.json';
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
    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

function cache_set(string $key, array $value): void
{
    file_put_contents(
        cache_file($key),
        json_encode($value, JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

function cache_forget(string $key): void
{
    $file = cache_file($key);
    if (is_file($file)) {
        unlink($file);
    }
}
