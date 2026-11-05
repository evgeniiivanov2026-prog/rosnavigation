<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if (DB_HOST === '' || DB_NAME === '') {
        throw new RuntimeException('База не настроена');
    }
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 2,
    ];
    if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+03:00'";
    }
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $pdo->exec("SET time_zone = '+03:00'");
    return $pdo;
}

function db_available(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    if (DB_HOST === '' || DB_NAME === '' || DB_USER === '') {
        return $ok = false;
    }
    try {
        $stmt = db()->query("SHOW TABLES LIKE 'users'");
        $ok = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        log_line('db', $e->getMessage());
        $ok = false;
    }
    return $ok;
}
