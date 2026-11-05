<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

api_dispatch(static function (): void {
    $type = (string) ($_GET['type'] ?? '');
    if ($type !== '' && !isset(catalog_partner_types()[$type])) {
        $type = '';
    }
    $rating = (float) ($_GET['rating'] ?? 0);
    json_response([
        'partners' => repo_partners([
            'type' => $type,
            'region' => (string) ($_GET['region'] ?? ''),
            'rating' => $rating,
            'day' => !empty($_GET['day']),
        ]),
        'regions' => repo_regions(),
    ]);
});
