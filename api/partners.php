<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/api_boot.php';

$type = (string) ($_GET['type'] ?? '');
if (!isset(partner_types()[$type])) {
    $type = '';
}
json_response([
    'partners' => repo_partners([
        'q' => (string) ($_GET['q'] ?? ''),
        'type' => $type,
        'city' => (string) ($_GET['city'] ?? ''),
        'day' => $_GET['day'] ?? '',
        'rating' => (string) ($_GET['rating'] ?? ''),
    ]),
]);
