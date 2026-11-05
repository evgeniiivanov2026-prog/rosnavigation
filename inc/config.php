<?php
declare(strict_types=1);

/**
 * Локальный и демо-конфиг. Перед боевым запуском скопируйте
 * config.example.php в config.local.php, поставьте APP_ENV=production
 * и данные MySQL. config.local.php в git не входит.
 */
define('APP_NAME', 'РосНавигация');
define('APP_URL', 'https://rosnavigation.ru');
define('APP_EMAIL', 'info@rosnavigation.ru');
define('APP_FORUM', 'https://forum.rosnavigation.ru');
define('APP_ENV', 'demo');

define('DB_HOST', 'localhost');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');

define('DEMO_PHONE', '+79000000015');
define('DEMO_PASSWORD', 'kolonna15');
