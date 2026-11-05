<?php
declare(strict_types=1);

/** @return array<string, array{label: string, hint: string, icon: string}> */
function catalog_sos_types(): array
{
    return [
        'breakdown' => ['label' => 'Встал', 'hint' => 'Ремонт на обочине', 'icon' => 'wrench'],
        'fuel' => ['label' => 'Обсох', 'hint' => 'Нужно топливо', 'icon' => 'fuel'],
        'sleep' => ['label' => 'Притомился', 'hint' => 'Нужен ночлег', 'icon' => 'bed'],
        'accident' => ['label' => 'ДТП', 'hint' => 'Авария, нужны люди', 'icon' => 'alert'],
        'medical' => ['label' => 'Медицина', 'hint' => 'Плохо с самочувствием', 'icon' => 'med'],
        'other' => ['label' => 'Другое', 'hint' => 'Своими словами', 'icon' => 'help'],
    ];
}

/** @return array<string, string> */
function catalog_partner_types(): array
{
    return [
        'sto' => 'Сервис',
        'fuel' => 'АЗС',
        'hotel' => 'Ночлег',
        'insurance' => 'Страхование',
        'lawyer' => 'Юрист',
        'shop' => 'Магазин',
        'other' => 'Другое',
    ];
}

/** @return array<string, array{label: string, tone: string}> */
function catalog_statuses(): array
{
    return [
        'on_air' => ['label' => 'На приёме', 'tone' => 'success'],
        'on_route' => ['label' => 'На маршруте', 'tone' => 'accent'],
        'need_help' => ['label' => 'Нужна помощь', 'tone' => 'danger'],
        'resting' => ['label' => 'Отдыхаю', 'tone' => 'muted'],
        'offline' => ['label' => 'Молчу', 'tone' => 'muted'],
    ];
}

/** @return array<string, string> */
function catalog_roles(): array
{
    return [
        'seeker' => 'Ищу помощь',
        'helper' => 'Помогаю',
        'both' => 'И то, и то',
    ];
}

/**
 * Точки на трассах, если телефон не отдал геолокацию.
 * @return array<int, array{lat: float, lng: float, label: string}>
 */
function catalog_points(): array
{
    return [
        ['lat' => 56.1439, 'lng' => 47.2489, 'label' => 'М-7 · Чебоксары'],
        ['lat' => 55.7887, 'lng' => 49.1221, 'label' => 'М-7 · Казань'],
        ['lat' => 56.2300, 'lng' => 44.0700, 'label' => 'М-7 · Нижний Новгород'],
        ['lat' => 51.6720, 'lng' => 39.1843, 'label' => 'М-4 · Воронеж'],
        ['lat' => 47.2357, 'lng' => 39.7015, 'label' => 'М-4 · Ростов-на-Дону'],
        ['lat' => 53.1959, 'lng' => 50.1002, 'label' => 'М-5 · Самара'],
        ['lat' => 54.7388, 'lng' => 55.9721, 'label' => 'М-5 · Уфа'],
        ['lat' => 58.5215, 'lng' => 31.2755, 'label' => 'М-11 · Великий Новгород'],
        ['lat' => 61.7849, 'lng' => 34.3469, 'label' => 'Р-21 · Петрозаводск'],
        ['lat' => 52.2870, 'lng' => 104.3050, 'label' => 'Р-258 · Иркутск'],
        ['lat' => 44.0486, 'lng' => 43.0594, 'label' => 'Р-217 · Пятигорск'],
        ['lat' => 55.5700, 'lng' => 37.6000, 'label' => 'МКАД · юг'],
    ];
}

/** @param array<string, mixed> $user */
function user_badges(array $user): array
{
    $badges = [
        ['id' => 'channel', 'name' => '15-й канал', 'hint' => 'В колонне'],
    ];
    if ((int) ($user['helps_count'] ?? 0) >= 15) {
        $badges[] = ['id' => 'mentor', 'name' => 'Наставник', 'hint' => 'Не раз выручал своих'];
    }
    if ((int) ($user['experience_y'] ?? 0) >= 15) {
        $badges[] = ['id' => 'veteran', 'name' => 'Ветеран эфира', 'hint' => 'Стаж от 15 лет'];
    }
    if ((int) ($user['helps_count'] ?? 0) >= 40) {
        $badges[] = ['id' => 'russia', 'name' => 'Дальнобой России', 'hint' => 'Десятки выручек'];
    }
    return $badges;
}

function sos_label(string $type): string
{
    return catalog_sos_types()[$type]['label'] ?? 'Сигнал';
}

function status_meta(string $status): array
{
    return catalog_statuses()[$status] ?? catalog_statuses()['offline'];
}
