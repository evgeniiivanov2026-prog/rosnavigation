<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (DB_DOWN) {
    json_response(['error' => 'unavailable', 'message' => 'Эфир временно закрыт.'], 503);
}
