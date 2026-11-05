<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

cache_purge_expired(0);
$rows = repo_partner_rows();
echo 'partners ' . count($rows) . PHP_EOL;
