<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

$result = repo_cleanup();
echo json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL;
