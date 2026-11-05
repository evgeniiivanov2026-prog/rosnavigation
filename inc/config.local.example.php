<?php
declare(strict_types=1);

/**
 * Скопируйте в inc/config.local.php (этот файл не попадёт в git)
 * или положите как .env.php на уровень выше public_html.
 */
define('APP_ENV', 'production');
define('DB_HOST', 'localhost');
define('DB_NAME', 'rosnav');
define('DB_USER', 'rosnav_user');
define('DB_PASS', 'замените-на-длинный-пароль');
