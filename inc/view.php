<?php
declare(strict_types=1);

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function honeypot_field(): string
{
    return '<div class="hp" aria-hidden="true"><label>Факс<input type="text" name="fax_number" tabindex="-1" autocomplete="off"></label></div>';
}

function view_status(string $status): string
{
    $meta = status_meta($status);
    return '<span class="status status--' . e($meta['tone']) . '"><i class="status__dot" aria-hidden="true"></i>' . e($meta['label']) . '</span>';
}

function view_avatar(string $name, ?string $path, string $href = ''): string
{
    if ($path) {
        $inner = '<img src="' . e($path) . '" alt="" width="48" height="48">';
        $class = 'avatar avatar--photo';
    } else {
        $inner = e(initials($name));
        $class = 'avatar';
    }
    if ($href !== '') {
        return '<a class="' . $class . '" href="' . e($href) . '" aria-label="' . e($name) . '">' . $inner . '</a>';
    }
    return '<span class="' . $class . '" aria-hidden="true">' . $inner . '</span>';
}

function view_rating(float $rating, int $count): string
{
    $text = number_format($rating, 1, ',', '');
    $word = ru_plural($count, 'оценка', 'оценки', 'оценок');
    return '<p class="rating"><span class="rating__num">' . e($text) . '</span><span class="rating__meta">' . e($count . ' ' . $word) . '</span></p>';
}

/** @param array<string, mixed> $partner */
function view_partner(array $partner, bool $hidden = false): string
{
    $day = !empty($partner['works_24_7']) ? '1' : '0';
    $phone = (string) ($partner['phone'] ?? '');
    $actions = '';
    if ($phone !== '') {
        $actions .= '<a class="btn btn--ghost" href="' . e(tel_href($phone)) . '">' . icon('phone', 'icon icon--sm') . 'Позвонить</a>';
    }
    if ($partner['lat'] !== null && $partner['lng'] !== null) {
        $actions .= '<a class="btn btn--brand" href="' . e(route_url((float) $partner['lat'], (float) $partner['lng'])) . '" rel="noopener">' . icon('route', 'icon icon--sm') . 'Маршрут</a>';
    }
    $price = $partner['price_label'] !== '' ? '<p class="partner__price">' . e($partner['price_label']) . '</p>' : '';
    $hours = !empty($partner['works_24_7']) ? 'Круглосуточно' : 'Не круглосуточно';
    return '<article class="partner"' . ($hidden ? ' hidden' : '') . ' data-type="' . e($partner['type']) . '" data-region="' . e($partner['region']) . '" data-rating="' . e((string) $partner['rating_avg']) . '" data-day="' . $day . '">'
        . '<div class="partner__band partner__band--' . e($partner['type']) . '">' . icon($partner['type'] === 'fuel' ? 'fuel' : ($partner['type'] === 'hotel' ? 'bed' : 'wrench'), 'icon') . '<span>' . e($partner['type_label']) . '</span></div>'
        . '<div class="partner__body">'
        . '<h3>' . e($partner['name']) . '</h3>'
        . '<p class="partner__where">' . e($partner['city']) . ' · ' . e($hours) . '</p>'
        . '<p>' . e($partner['description']) . '</p>'
        . view_rating((float) $partner['rating_avg'], (int) $partner['rating_count'])
        . $price
        . '<p class="partner__address">' . e($partner['address']) . '</p>'
        . '<div class="partner__actions">' . $actions . '</div>'
        . '</div></article>';
}

/** @param array<string, mixed> $post */
function view_post(array $post): string
{
    $photo = '';
    if (!empty($post['photo_path'])) {
        $photo = '<img class="post__photo" src="' . e((string) $post['photo_path']) . '" alt="Фото к записи ' . e($post['name']) . '" loading="lazy">';
    }
    $pending = $post['status'] === 'pending' ? '<p class="post__pending">Фото на премодерации и пока видно только вам.</p>' : '';
    $where = [];
    if ($post['route'] !== '') {
        $where[] = '<span class="mono">' . e($post['route']) . '</span>';
    }
    if ($post['city'] !== '') {
        $where[] = e($post['city']);
    }
    if ($post['ago'] !== '') {
        $where[] = e($post['ago']);
    }
    $pressed = !empty($post['liked']) ? 'true' : 'false';
    return '<article class="post" id="post-' . (int) $post['id'] . '" data-id="' . (int) $post['id'] . '">'
        . '<header class="post__top">'
        . view_avatar((string) $post['name'], null, '/profile/' . (int) $post['user_id'])
        . '<div><a class="post__name" href="/profile/' . (int) $post['user_id'] . '">' . e($post['name']) . '</a>'
        . '<p class="post__meta">' . implode(' · ', $where) . '</p></div></header>'
        . '<p class="post__body">' . nl2br(e((string) $post['body'])) . '</p>'
        . $photo
        . $pending
        . '<footer class="post__bar">'
        . '<button class="salute" type="button" data-like="' . (int) $post['id'] . '" aria-pressed="' . $pressed . '">'
        . '<span class="salute__mark" aria-hidden="true">🫡</span> <span class="salute__count">' . (int) $post['likes_count'] . '</span> <span>Пятюня</span></button>'
        . '<button class="text-btn" type="button" data-report="' . (int) $post['id'] . '">Пожаловаться</button>'
        . '</footer></article>';
}

/** @param array<string, mixed> $sos */
function view_signal(array $sos, bool $compact = false): string
{
    $place = [];
    if ($sos['route'] !== '') {
        $place[] = '<span class="mono">' . e($sos['route']) . '</span>';
    }
    if ($sos['km']) {
        $place[] = e((string) $sos['km'] . ' км');
    }
    if ($sos['distance_label'] !== '') {
        $place[] = e($sos['distance_label']);
    }
    $meta = [];
    if ($sos['truck_brand'] !== '') {
        $meta[] = e($sos['truck_brand']);
    }
    if ($sos['ago'] !== '') {
        $meta[] = e($sos['ago']);
    }
    $actions = '<a class="btn btn--ghost" href="/sos?id=' . (int) $sos['id'] . '">Открыть</a>';
    if ($sos['lat'] && $sos['lng']) {
        $actions .= '<a class="btn btn--brand" href="' . e(route_url((float) $sos['lat'], (float) $sos['lng'])) . '" rel="noopener">Маршрут</a>';
    }
    $comment = $compact ? '' : '<p>' . e((string) $sos['comment']) . '</p>';
    return '<article class="signal" data-id="' . (int) $sos['id'] . '" data-lat="' . e((string) $sos['lat']) . '" data-lng="' . e((string) $sos['lng']) . '" data-type="' . e($sos['type']) . '" data-status="' . e($sos['status']) . '">'
        . '<header class="signal__top"><span class="status status--danger"><i class="status__dot" aria-hidden="true"></i>' . e($sos['type_label']) . '</span>'
        . '<p class="signal__place">' . implode(' · ', $place) . '</p></header>'
        . '<h3><a href="/profile/' . (int) $sos['user_id'] . '">' . e($sos['name']) . '</a></h3>'
        . $comment
        . '<p class="signal__meta">' . implode(' · ', $meta) . '</p>'
        . ($compact ? '' : '<div class="signal__actions">' . $actions . '</div>')
        . '</article>';
}

function view_state(string $tone, string $title, string $text): string
{
    return '<div class="state state--' . e($tone) . '" role="status"><h3>' . e($title) . '</h3><p>' . e($text) . '</p></div>';
}
