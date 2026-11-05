<?php
declare(strict_types=1);

function demo_file(): string
{
    $dir = APP_ROOT . '/storage/demo';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir . '/state.json';
}

function demo_seed(): array
{
    $now = date('Y-m-d H:i:s');
    $hash = hash_password('doroga15');
    $ago = static fn (int $seconds): string => date('Y-m-d H:i:s', time() - $seconds);
    $base = [
        'password_hash' => $hash,
        'role' => 'both',
        'truck_plate' => null,
        'avatar_path' => null,
        'car_path' => null,
        'is_partner' => 0,
        'is_banned' => 0,
        'last_seen_at' => $now,
        'created_at' => $ago(86400 * 400),
        'updated_at' => $now,
    ];
    $users = [
        $base + ['id' => 1, 'phone' => '+79000000015', 'name' => 'Сергей Волгин', 'city' => 'Казань', 'truck_brand' => 'Volvo FH', 'truck_trailer' => 'тент 13,6', 'experience_y' => 18, 'motto' => 'Своих не бросаем.', 'status' => 'on_air', 'lat' => 55.7887, 'lng' => 49.1221, 'route' => 'М-7', 'helps_count' => 37, 'rating_sum' => 49, 'rating_count' => 10],
        $base + ['id' => 2, 'phone' => '+79000000011', 'name' => 'Марина Елина', 'city' => 'Тверь', 'truck_brand' => 'MAN TGX', 'truck_trailer' => 'рефрижератор', 'experience_y' => 7, 'motto' => 'Напишу, как есть. Даже если это «не знаю».', 'status' => 'on_air', 'lat' => 56.8587, 'lng' => 35.9176, 'route' => 'М-11', 'helps_count' => 9, 'rating_sum' => 28, 'rating_count' => 6],
        $base + ['id' => 3, 'phone' => '+79000000012', 'name' => 'Илья Морозов', 'city' => 'Петрозаводск', 'truck_brand' => 'Scania R', 'truck_trailer' => 'тент', 'experience_y' => 11, 'motto' => 'Ветер на Коле не прощает пустую фуру.', 'status' => 'on_route', 'lat' => 61.7849, 'lng' => 34.3469, 'route' => 'Р-21', 'helps_count' => 14, 'rating_sum' => 33, 'rating_count' => 7],
        $base + ['id' => 4, 'phone' => '+79000000013', 'name' => 'Артём Белов', 'city' => 'Воронеж', 'truck_brand' => 'Mercedes Actros', 'truck_trailer' => 'штора', 'experience_y' => 9, 'motto' => 'Лучше рано остановиться, чем геройствовать на сухом баке.', 'status' => 'need_help', 'lat' => 51.05, 'lng' => 39.4, 'route' => 'М-4', 'helps_count' => 6, 'rating_sum' => 19, 'rating_count' => 4],
        $base + ['id' => 5, 'phone' => '+79000000014', 'name' => 'Павел Игнатьев', 'city' => 'Челябинск', 'truck_brand' => 'КамАЗ 5490', 'truck_trailer' => 'тент', 'experience_y' => 22, 'motto' => 'Сначала домкрат и башмаки, потом разговоры.', 'status' => 'on_air', 'lat' => 55.1644, 'lng' => 61.4368, 'route' => 'М-5', 'helps_count' => 54, 'rating_sum' => 71, 'rating_count' => 14],
        $base + ['id' => 6, 'phone' => '+79000000016', 'name' => 'Николай Сафин', 'city' => 'Иркутск', 'truck_brand' => 'Volvo FM', 'truck_trailer' => 'контейнер', 'experience_y' => 15, 'motto' => 'На Байкале утро красивое и скользкое.', 'status' => 'resting', 'lat' => 52.2896, 'lng' => 104.2806, 'route' => 'Р-258', 'helps_count' => 21, 'rating_sum' => 40, 'rating_count' => 9],
        $base + ['id' => 7, 'phone' => '+79000000017', 'name' => 'Руслан Гагиев', 'city' => 'Минеральные Воды', 'truck_brand' => 'DAF XF', 'truck_trailer' => 'тент', 'experience_y' => 13, 'motto' => 'В горах тормоза важнее расписания.', 'status' => 'on_route', 'lat' => 44.2103, 'lng' => 43.1353, 'route' => 'Р-217', 'helps_count' => 18, 'rating_sum' => 36, 'rating_count' => 8],
        $base + ['id' => 8, 'phone' => '+79000000018', 'name' => 'Кирилл Демин', 'city' => 'Ульяновск', 'truck_brand' => 'КамАЗ 65116', 'truck_trailer' => 'бортовой', 'experience_y' => 4, 'motto' => 'Первый год слушаю больше, чем говорю.', 'status' => 'need_help', 'lat' => 54.32, 'lng' => 48.4, 'route' => 'М-5', 'helps_count' => 1, 'rating_sum' => 5, 'rating_count' => 1],
    ];

    $sos = [
        ['id' => 1, 'user_id' => 4, 'type' => 'fuel', 'lat' => 51.05, 'lng' => 39.4, 'route' => 'М-4', 'km' => 514, 'comment' => 'Бак сухой, до колонки не дотяну. Стою на обочине, аварийка включена.', 'status' => 'active', 'helper_id' => null, 'created_at' => $ago(25 * 60), 'accepted_at' => null, 'completed_at' => null, 'expires_at' => date('Y-m-d H:i:s', time() + 7200)],
        ['id' => 2, 'user_id' => 8, 'type' => 'breakdown', 'lat' => 54.32, 'lng' => 48.4, 'route' => 'М-5', 'km' => 812, 'comment' => 'Заднее левое. Запаска на месте, домкрата нет.', 'status' => 'active', 'helper_id' => null, 'created_at' => $ago(70 * 60), 'accepted_at' => null, 'completed_at' => null, 'expires_at' => date('Y-m-d H:i:s', time() + 5400)],
        ['id' => 3, 'user_id' => 2, 'type' => 'breakdown', 'lat' => 56.9, 'lng' => 35.8, 'route' => 'М-11', 'km' => 180, 'comment' => 'Колесо на обочине. Дошли вместе, без героизма.', 'status' => 'completed', 'helper_id' => 5, 'created_at' => $ago(5 * 86400), 'accepted_at' => $ago(5 * 86400 - 600), 'completed_at' => $ago(5 * 86400 - 5400), 'expires_at' => $ago(5 * 86400 - 7200)],
        ['id' => 4, 'user_id' => 1, 'type' => 'other', 'lat' => 55.75, 'lng' => 49.05, 'route' => 'М-7', 'km' => 820, 'comment' => 'Села кабина. Прикурили от соседней фуры и поехали.', 'status' => 'completed', 'helper_id' => 2, 'created_at' => $ago(12 * 86400), 'accepted_at' => $ago(12 * 86400 - 300), 'completed_at' => $ago(12 * 86400 - 2400), 'expires_at' => $ago(12 * 86400 - 3600)],
    ];

    $partners = [
        ['id' => 1, 'name' => 'СТО «Волжский пролёт»', 'type' => 'sto', 'city' => 'Казань', 'address' => 'М-7, северный выезд. Учебная точка, вывески нет.', 'lat' => 55.83, 'lng' => 49.18, 'phone' => null, 'site_url' => null, 'description' => 'В учебном эфире: яма под тягач, резина и электрика. Ночью дежурит один механик.', 'photo_path' => null, 'price_from' => 3500, 'works_24_7' => 1, 'rating_avg' => 4.8, 'rating_count' => 36, 'is_active' => 1],
        ['id' => 2, 'name' => 'АЗС «Плечо»', 'type' => 'fuel', 'city' => 'Нижний Новгород', 'address' => 'М-7, учебная колонка. Не ищите её на трассе.', 'lat' => 56.3, 'lng' => 44.05, 'phone' => null, 'site_url' => null, 'description' => 'ДТ, душ, фура встаёт сбоку — не на легковой пистолет. Очередь короче до семи утра.', 'photo_path' => null, 'price_from' => null, 'works_24_7' => 1, 'rating_avg' => 4.6, 'rating_count' => 22, 'is_active' => 1],
        ['id' => 3, 'name' => 'Ночлег «Рассвет»', 'type' => 'hotel', 'city' => 'Воронеж', 'address' => 'М-4, учебный двор. Это не действующий мотель.', 'lat' => 51.7, 'lng' => 39.25, 'phone' => null, 'site_url' => null, 'description' => 'Фура за воротами, душ тёплый, кухня до 23. Охрана на воротах, не у каждой кабины.', 'photo_path' => null, 'price_from' => 900, 'works_24_7' => 1, 'rating_avg' => 4.4, 'rating_count' => 18, 'is_active' => 1],
        ['id' => 4, 'name' => 'Шиномонтаж «Обод»', 'type' => 'sto', 'city' => 'Рязань', 'address' => 'М-5, учебный бокс.', 'lat' => 54.62, 'lng' => 39.75, 'phone' => null, 'site_url' => null, 'description' => 'Грузовая резина, без записи в будни до обеда. Домкрат свой, башмаки тоже.', 'photo_path' => null, 'price_from' => 1800, 'works_24_7' => 0, 'rating_avg' => 4.7, 'rating_count' => 15, 'is_active' => 1],
        ['id' => 5, 'name' => 'Стоянка «Кедр»', 'type' => 'hotel', 'city' => 'Иркутск', 'address' => 'Р-258, учебная площадка.', 'lat' => 52.32, 'lng' => 104.2, 'phone' => null, 'site_url' => null, 'description' => 'Розетка, туалет, свет. Зимой дальний ряд не чистят — не ставьтесь туда на ночь.', 'photo_path' => null, 'price_from' => 500, 'works_24_7' => 1, 'rating_avg' => 4.5, 'rating_count' => 11, 'is_active' => 1],
        ['id' => 6, 'name' => 'Сервис «Перевал»', 'type' => 'sto', 'city' => 'Минеральные Воды', 'address' => 'Р-217, учебный въезд.', 'lat' => 44.22, 'lng' => 43.1, 'phone' => null, 'site_url' => null, 'description' => 'Берут тягач без записи в будни до девяти. В обед уже живая очередь.', 'photo_path' => null, 'price_from' => 4000, 'works_24_7' => 0, 'rating_avg' => 4.9, 'rating_count' => 27, 'is_active' => 1],
        ['id' => 7, 'name' => 'АЗС «Северная»', 'type' => 'fuel', 'city' => 'Кандалакша', 'address' => 'Р-21, учебная колонка.', 'lat' => 67.15, 'lng' => 32.41, 'phone' => null, 'site_url' => null, 'description' => 'ДТ есть, ветер на площадке сильный. Канистру отдадут, если попросите по-человечески.', 'photo_path' => null, 'price_from' => null, 'works_24_7' => 1, 'rating_avg' => 4.3, 'rating_count' => 9, 'is_active' => 1],
        ['id' => 8, 'name' => 'Магазин «В рейс»', 'type' => 'shop', 'city' => 'Тверь', 'address' => 'М-11, учебный киоск.', 'lat' => 56.84, 'lng' => 35.95, 'phone' => null, 'site_url' => null, 'description' => 'Вода, ремни, фонари, перчатки. Фура остаётся у бокового въезда, в центр не заезжайте.', 'photo_path' => null, 'price_from' => 150, 'works_24_7' => 0, 'rating_avg' => 4.2, 'rating_count' => 8, 'is_active' => 1],
    ];

    $posts = [
        ['id' => 1, 'user_id' => 5, 'body' => 'На М-5 перед Челябинском ветер боковой, пустую фуру ставит на полполосы. Держите дистанцию, особенно ночью.', 'photo_path' => null, 'city' => 'Челябинск', 'route' => 'М-5', 'likes_count' => 3, 'status' => 'published', 'created_at' => $ago(2 * 3600)],
        ['id' => 2, 'user_id' => 2, 'body' => 'Стоянка у Твери: свет есть, туалет открыт, фура встаёт. После полуночи места остаются только во втором ряду.', 'photo_path' => null, 'city' => 'Тверь', 'route' => 'М-11', 'likes_count' => 2, 'status' => 'published', 'created_at' => $ago(5 * 3600)],
        ['id' => 3, 'user_id' => 1, 'body' => 'Туман на Волге к утру сел плотно. Шёл по габаритам впереди идущего и сбросил скорость сам, не дожидаясь знака.', 'photo_path' => null, 'city' => 'Казань', 'route' => 'М-7', 'likes_count' => 4, 'status' => 'published', 'created_at' => $ago(8 * 3600)],
        ['id' => 4, 'user_id' => 7, 'body' => 'Сервис на въезде берёт тягач без очереди, если подъехать до девяти. В обед уже стоят четверо.', 'photo_path' => null, 'city' => 'Минеральные Воды', 'route' => 'Р-217', 'likes_count' => 1, 'status' => 'published', 'created_at' => $ago(26 * 3600)],
        ['id' => 5, 'user_id' => 3, 'body' => 'Открытый участок за Петрозаводском: порывы, тент хлопает. Кому с высокой парусностью — не геройствуйте.', 'photo_path' => null, 'city' => 'Петрозаводск', 'route' => 'Р-21', 'likes_count' => 2, 'status' => 'published', 'created_at' => $ago(30 * 3600)],
        ['id' => 6, 'user_id' => 6, 'body' => 'На теневых участках к Байкалу утром лёд, даже если на солнце сухо. Первый поворот после спуска — особенно.', 'photo_path' => null, 'city' => 'Иркутск', 'route' => 'Р-258', 'likes_count' => 5, 'status' => 'published', 'created_at' => $ago(50 * 3600)],
    ];

    return [
        'v' => 1,
        'users' => $users,
        'sos' => $sos,
        'partners' => $partners,
        'posts' => $posts,
        'likes' => [
            ['user_id' => 1, 'post_id' => 1],
            ['user_id' => 2, 'post_id' => 1],
            ['user_id' => 6, 'post_id' => 1],
            ['user_id' => 1, 'post_id' => 2],
            ['user_id' => 5, 'post_id' => 2],
            ['user_id' => 2, 'post_id' => 3],
            ['user_id' => 5, 'post_id' => 3],
            ['user_id' => 3, 'post_id' => 3],
            ['user_id' => 7, 'post_id' => 3],
            ['user_id' => 1, 'post_id' => 4],
            ['user_id' => 5, 'post_id' => 5],
            ['user_id' => 2, 'post_id' => 5],
            ['user_id' => 1, 'post_id' => 6],
            ['user_id' => 3, 'post_id' => 6],
            ['user_id' => 5, 'post_id' => 6],
            ['user_id' => 7, 'post_id' => 6],
            ['user_id' => 2, 'post_id' => 6],
        ],
        'reports' => [],
    ];
}

function demo_read(): array
{
    $file = demo_file();
    if (!is_file($file)) {
        $data = demo_seed();
        demo_write($data);
        return $data;
    }
    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data) || (int) ($data['v'] ?? 0) !== 1 || empty($data['users'])) {
        $data = demo_seed();
        demo_write($data);
    }
    return $data;
}

function demo_write(array $data): void
{
    $fh = fopen(demo_file(), 'c+');
    if (!$fh) {
        throw new RuntimeException('Учебный эфир не записывается');
    }
    flock($fh, LOCK_EX);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
}

function demo_edit(callable $fn): mixed
{
    $file = demo_file();
    if (!is_file($file)) {
        demo_write(demo_seed());
    }
    $fh = fopen($file, 'c+');
    if (!$fh) {
        throw new RuntimeException('Учебный эфир не открывается');
    }
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $data = json_decode($raw ?: '', true);
    if (!is_array($data) || (int) ($data['v'] ?? 0) !== 1) {
        $data = demo_seed();
    }
    $result = $fn($data);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $result;
}

function demo_next_id(array $rows): int
{
    $max = 0;
    foreach ($rows as $row) {
        $max = max($max, (int) ($row['id'] ?? 0));
    }
    return $max + 1;
}
