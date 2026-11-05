<?php
declare(strict_types=1);

define('ROSNAV_API', true);
require dirname(__DIR__) . '/inc/bootstrap.php';

json_response([
    'ok' => true,
    'partners' => repo_partners($_GET),
    'cities' => repo_partner_cities(),
]);
