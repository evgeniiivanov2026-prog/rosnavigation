<?php
declare(strict_types=1);

function cache_file(string $key): string
{
    $dir = APP_ROOT . '/storage/cache';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir . '/' . sha1($key) . '.json';
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
    file_put_contents(cache_file($key), json_encode($value, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function poll_generation(): int
{
    $file = APP_ROOT . '/storage/cache/poll-gen.txt';
    if (!is_file($file)) {
        return 1;
    }
    return max(1, (int) file_get_contents($file));
}

function poll_bump(): void
{
    $file = APP_ROOT . '/storage/cache/poll-gen.txt';
    file_put_contents($file, (string) (poll_generation() + 1), LOCK_EX);
}
