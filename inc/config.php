<?php
declare(strict_types=1);

/**
 * Секреты лежат вне git: inc/config.local.php или .env.php выше корня сайта.
 * Локальный файл подключается первым и может задать любые константы ниже.
 */
$localCandidates = [
    dirname(__DIR__, 2) . '/.env.php',
    __DIR__ . '/config.local.php',
];
foreach ($localCandidates as $candidate) {
    if (is_file($candidate)) {
        require $candidate;
        break;
    }
}

$defaults = [
    'APP_NAME' => 'РосНавигация',
    'APP_URL' => 'https://rosnavigation.ru',
    'APP_EMAIL' => 'info@rosnavigation.ru',
    'APP_FORUM' => 'https://forum.rosnavigation.ru',
    'APP_ENV' => 'production',
    'DB_HOST' => '',
    'DB_NAME' => '',
    'DB_USER' => '',
    'DB_PASS' => '',
    'HELP_RADIUS_KM' => 15,
    'SOS_TTL_HOURS' => 2,
];
foreach ($defaults as $name => $value) {
    if (!defined($name)) {
        define($name, $value);
    }
}
