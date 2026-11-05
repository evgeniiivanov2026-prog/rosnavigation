<?php
declare(strict_types=1);

/**
 * Скопируйте в inc/config.php или положите выше корня сайта как .env.php.
 * Файл с паролем не коммитится.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'rosnav');
define('DB_USER', 'rosnav_user');
define('DB_PASS', 'замените-на-пароль');
define('APP_URL', 'https://rosnavigation.ru');
define('APP_ENV', 'production');
