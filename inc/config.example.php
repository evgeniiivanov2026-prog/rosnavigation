<?php
declare(strict_types=1);

/**
 * Боевой конфиг. Положите файл ВЫШЕ public_html как .env.php
 * или рядом с этим каталогом как inc/config.local.php.
 * В git его не кладут.
 */
define('APP_NAME', 'РосНавигация');
define('APP_URL', 'https://rosnavigation.ru');
define('APP_EMAIL', 'info@rosnavigation.ru');
define('APP_FORUM', 'https://forum.rosnavigation.ru');
define('APP_ENV', 'production');

define('DB_HOST', 'localhost');
define('DB_NAME', 'rosnav');
define('DB_USER', 'rosnav_user');
define('DB_PASS', 'замените-на-длинный-пароль');
