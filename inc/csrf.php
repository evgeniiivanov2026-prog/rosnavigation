<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check(): void
{
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $known = $_SESSION['csrf_token'] ?? '';
    if (!$token || !is_string($known) || $known === '' || !hash_equals($known, (string) $token)) {
        throw new HttpError('csrf_failed', 'Сессия устарела. Обновите страницу и повторите.', 403);
    }
}
