<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

cache_set('partners:all', ['partners' => repo_partners([])]);
echo "partners\n";
