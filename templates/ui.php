<?php
declare(strict_types=1);

function render_status(string $status): string
{
    [$label, $tone] = status_meta($status);
    return '<span class="status status--' . e($tone) . '">' . e($label) . '</span>';
}

function render_avatar(array $user, string $class = 'avatar'): string
{
    $name = (string) ($user['name'] ?? '');
    if (!empty($user['avatar_path'])) {
        return '<img class="' . e($class) . '" src="' . e($user['avatar_path']) . '" alt="" width="72" height="72">';
    }
    return '<span class="' . e($class) . ' avatar--letter">' . e(initials($name)) . '</span>';
}

function render_partner(array $partner): string
{
    $demo = defined('DEMO_MODE') && DEMO_MODE;
    $mapHref = '/map?partner=' . (int) $partner['id'];
    $route = $demo ? $mapHref : (yandex_point($partner['lat'], $partner['lng']) ?? $mapHref);
    $routeLabel = $demo || $route === $mapHref ? 'На карте' : 'Маршрут';
    $external = str_starts_with($route, 'http') ? ' target="_blank" rel="noopener noreferrer"' : '';
    $price = $partner['price_from'] ? 'от ' . money_label((int) $partner['price_from']) : '';
    $rating = number_format((float) $partner['rating'], 1, ',', '');

    $html = '<article class="card partner" data-type="' . e($partner['type']) . '" data-city="' . e($partner['city']) . '">';
    $html .= '<div class="partner__top"><span class="eyebrow">' . e($partner['type_label']) . '</span>';
    if (!empty($partner['works_24_7'])) {
        $html .= '<span class="chip">Круглосуточно</span>';
    }
    $html .= '</div>';
    $html .= '<h3>' . e($partner['name']) . '</h3>';
    $html .= '<p class="partner__where"><span class="route">' . e($partner['city']) . '</span> · ' . e($partner['address']) . '</p>';
    $html .= '<p>' . e($partner['description']) . '</p>';
    $html .= '<p class="partner__meta"><span class="stars">' . icon('star', 'icon icon--sm') . ' ' . e($rating) . '</span>';
    $html .= '<span>' . (int) $partner['rating_count'] . ' оценок</span>';
    if ($price !== '') {
        $html .= '<span>' . e($price) . '</span>';
    }
    $html .= '</p><div class="actions">';
    if (!empty($partner['phone'])) {
        $html .= '<a class="btn btn--primary" href="tel:' . e(preg_replace('/[^\d+]/', '', (string) $partner['phone'])) . '">Позвонить</a>';
    } else {
        $html .= '<p class="fine">Телефон откроем после проверки. В учебном эфире звонить некуда.</p>';
    }
    $html .= '<a class="btn btn--ghost" href="' . e($route) . '"' . $external . '>' . e($routeLabel) . '</a>';
    $html .= '</div></article>';
    return $html;
}

function render_post(array $post, bool $withForms = true): string
{
    $author = $post['author'];
    $html = '<article class="post" id="post-' . (int) $post['id'] . '">';
    $html .= '<header class="post__head">' . render_avatar($author, 'avatar avatar--sm');
    $html .= '<div><a class="post__name" href="/profile/' . (int) $author['id'] . '">' . e($author['name']) . '</a>';
    $meta = array_filter([
        $post['route'] !== '' ? $post['route'] : null,
        $post['city'] !== '' ? $post['city'] : null,
        $post['ago'] ?? '',
    ]);
    $html .= '<p class="fine">' . e(implode(' · ', $meta)) . '</p></div></header>';
    $html .= '<p class="post__body">' . nl2br_e($post['body']) . '</p>';
    if (!empty($post['photo_path']) && ($post['status'] ?? '') === 'published') {
        $html .= '<img class="post__photo photo-trucker" src="' . e($post['photo_path']) . '" alt="Фото к записи" loading="lazy">';
    }
    if (($post['status'] ?? '') === 'pending') {
        $html .= '<p class="chip">На проверке</p>';
    }
    if ($withForms && ($post['status'] ?? 'published') === 'published') {
        $html .= '<div class="post__tools">';
        $html .= '<form method="post" action="/channel" class="like-form">';
        $html .= csrf_field();
        $html .= '<input type="hidden" name="action" value="like">';
        $html .= '<input type="hidden" name="post_id" value="' . (int) $post['id'] . '">';
        $pressed = !empty($post['liked']) ? 'true' : 'false';
        $html .= '<button class="like" type="submit" aria-pressed="' . $pressed . '">Пятюня <span>' . (int) $post['likes_count'] . '</span></button>';
        $html .= '</form>';
        $html .= '<form method="post" action="/channel">';
        $html .= csrf_field();
        $html .= '<input type="hidden" name="action" value="report">';
        $html .= '<input type="hidden" name="post_id" value="' . (int) $post['id'] . '">';
        $html .= '<button class="text-btn" type="submit">Пожаловаться</button>';
        $html .= '</form></div>';
    }
    $html .= '</article>';
    return $html;
}

function render_signal(array $signal, ?int $viewerId, string $back = '/map'): string
{
    $html = '<article class="signal" id="signal-' . (int) $signal['id'] . '" data-id="' . (int) $signal['id'] . '" data-lat="' . e((string) $signal['lat']) . '" data-lng="' . e((string) $signal['lng']) . '" data-route="' . e($signal['route']) . '" data-kind="sos" data-status="' . e($signal['status']) . '">';
    $state = match ($signal['status']) {
        'accepted' => '<span class="status status--accent">Уже едут</span>',
        'completed' => '<span class="status status--success">Выручили</span>',
        'cancelled' => '<span class="status status--muted">Снят</span>',
        default => render_status('need_help'),
    };
    $html .= '<div class="signal__top"><span class="eyebrow">' . e($signal['type_label']) . '</span>' . $state . '</div>';
    $place = trim($signal['route'] . ($signal['km'] !== null ? ' · ' . $signal['km'] . ' км' : ''));
    $html .= '<h3>' . e($place !== '' ? $place : 'Точка на трассе') . '</h3>';
    $bits = array_filter([
        $signal['distance_km'] !== null ? km_label((float) $signal['distance_km']) : null,
        $signal['ago'] ?? '',
    ]);
    if ($bits) {
        $html .= '<p class="fine">' . e(implode(' · ', $bits)) . '</p>';
    }
    if ($signal['comment'] !== '') {
        $html .= '<p>' . e($signal['comment']) . '</p>';
    }
    $html .= '<p class="signal__who"><a href="/profile/' . (int) $signal['author']['id'] . '">' . e($signal['author']['name']) . '</a>';
    if ($signal['author']['truck_brand'] !== '') {
        $html .= ' · ' . e($signal['author']['truck_brand']);
    }
    $html .= '</p>';
    if (!empty($signal['helper'])) {
        $html .= '<p class="fine">Едет ' . e($signal['helper']['name']) . '</p>';
    }
    $html .= '<div class="actions">';
    $point = yandex_point((float) $signal['lat'], (float) $signal['lng']);
    if ($point && !(defined('DEMO_MODE') && DEMO_MODE)) {
        $html .= '<a class="btn btn--ghost" target="_blank" rel="noopener noreferrer" href="' . e($point) . '">Маршрут</a>';
    }
    $html .= '<a class="btn btn--ghost" href="/map?sos=' . (int) $signal['id'] . '">На карте</a>';
    if ($viewerId && $signal['status'] === 'active' && (int) $signal['user_id'] !== $viewerId) {
        $html .= signal_action_form('accept', (int) $signal['id'], $back, 'Еду', 'btn btn--primary');
    }
    if ($viewerId && (int) $signal['user_id'] === $viewerId && in_array($signal['status'], ['active', 'accepted'], true)) {
        $html .= signal_action_form('complete', (int) $signal['id'], $back, 'Выручили', 'btn btn--primary');
        $html .= signal_action_form('cancel', (int) $signal['id'], $back, 'Снять сигнал', 'btn btn--ghost');
    }
    $html .= '</div></article>';
    return $html;
}

function signal_action_form(string $action, int $id, string $back, string $label, string $class): string
{
    return '<form method="post" action="' . e($back) . '">'
        . csrf_field()
        . '<input type="hidden" name="action" value="' . e($action) . '">'
        . '<input type="hidden" name="sos_id" value="' . $id . '">'
        . '<input type="hidden" name="back" value="' . e($back) . '">'
        . '<button class="' . e($class) . '" type="submit">' . e($label) . '</button></form>';
}

function render_empty(string $title, string $text): string
{
    return '<div class="empty"><h3>' . e($title) . '</h3><p>' . e($text) . '</p></div>';
}
