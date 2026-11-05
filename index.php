<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

$GLOBALS['page'] = 'home';
$user = current_user();
$stats = repo_stats();
$crew = repo_on_air(4);
$posts = array_slice(repo_posts($user['id'] ?? null), 0, 3);

$meta = [
    'title' => 'РосНавигация — рассвет, трасса, свои',
    'description' => 'Всероссийская сеть взаимовыручки дальнобойщиков. На 15-м — свои. Ты не один на трассе.',
    'canonical' => APP_URL . '/',
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'РосНавигация',
        'url' => APP_URL,
        'slogan' => 'На 15-м — свои. Ты не один на трассе.',
        'email' => APP_EMAIL,
        'description' => 'Всероссийская сеть взаимовыручки дальнобойщиков',
        'areaServed' => 'RU',
    ],
];

render_header($meta);
?>
    <section class="hero">
      <div class="hero__sky" aria-hidden="true"></div>
      <div class="container hero__grid">
        <div class="hero__copy">
          <p class="kicker">Всероссийская сеть взаимовыручки</p>
          <h1>Рассвет.<br>Трасса.<br><em>Свои.</em></h1>
          <p class="lede">Всероссийская сеть взаимовыручки на 15-м канале. Ты не один на трассе.</p>
          <div class="actions">
            <a class="btn btn--danger" href="/sos.php">Выйти на связь</a>
            <a class="btn btn--ghost" href="/auth.php">Встать на приём</a>
          </div>
          <ul class="hero__facts">
            <li><strong>Россия</strong><span>от Колы до Байкала</span></li>
            <li><strong>24/7</strong><span>трасса не спит</span></li>
            <li><strong>112</strong><span>если угроза жизни</span></li>
          </ul>
        </div>
        <aside class="ether" aria-label="Живой эфир">
          <div class="ether__top">
            <span class="ether__led"><i></i> Живой эфир</span>
            <span class="meta">раз в полминуты</span>
          </div>
          <p class="ether__count" id="ether-count" aria-live="polite"><?= (int) $stats['online'] ?></p>
          <p class="ether__label"><?= (int) $stats['online'] > 0 ? 'на связи, без учебных карточек' : 'живой эфир ещё тихий — ниже учебные карточки' ?></p>
          <div class="ether__row">
            <span>Свои на плече</span>
            <span class="meta"><?= (int) $stats['active_sos'] ?> <?= e(plural((int) $stats['active_sos'], 'сигнал', 'сигнала', 'сигналов')) ?></span>
          </div>
          <div class="ether__list">
            <?php foreach ($crew as $person): ?>
              <a class="ether__person" href="/profile.php?id=<?= (int) $person['id'] ?>">
                <?= avatar_html($person) ?>
                <span>
                  <b><?= e($person['name']) ?><?php if ($person['demo']): ?> · образец<?php endif; ?></b>
                  <span><?= e($person['status_label']) ?><?= $person['city'] ? ' · ' . e($person['city']) : '' ?></span>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
          <a class="btn btn--ghost" href="/map.php">Открыть полную карту</a>
        </aside>
      </div>
      <div class="road" aria-hidden="true">
        <svg class="road__truck" viewBox="0 0 240 90">
          <rect x="18" y="28" width="130" height="36" rx="4" fill="#141b24" stroke="#ff7a1a" stroke-width="2"/>
          <path d="M148 40h34l22 16v8h-56V40z" fill="#1b2531" stroke="#ffc933" stroke-width="2"/>
          <rect x="158" y="46" width="16" height="12" rx="2" fill="#9fd4ff" opacity=".8"/>
          <circle cx="58" cy="70" r="12" fill="#0a0e13" stroke="#d5dde6" stroke-width="3"/>
          <circle cx="108" cy="70" r="12" fill="#0a0e13" stroke="#d5dde6" stroke-width="3"/>
          <circle cx="186" cy="70" r="12" fill="#0a0e13" stroke="#d5dde6" stroke-width="3"/>
          <rect x="28" y="58" width="18" height="6" rx="1" fill="#e74c3c"/>
        </svg>
      </div>
    </section>

    <div class="tape" aria-hidden="true">
      <div class="tape__track">
        <?php
        $roads = ['М-4 «Дон»', 'М-5 «Урал»', 'М-7 «Волга»', 'М-11 «Нева»', 'М-12 «Восток»', 'Р-21 «Кола»', 'Р-217 «Кавказ»', 'Р-258 «Байкал»', 'А-370 «Уссури»'];
        for ($copy = 0; $copy < 2; $copy++):
            foreach ($roads as $road): ?>
              <span><?= e($road) ?></span>
            <?php endforeach;
        endfor; ?>
      </div>
    </div>

    <section class="section" id="kak">
      <div class="container">
        <header class="section-head">
          <p class="kicker">Как это работает</p>
          <h2>Три шага, пока связь ещё есть</h2>
          <p class="lede">Без анкеты на десять экранов. Сигнал, свои рядом и закрытая выручка.</p>
        </header>
        <div class="steps">
          <article class="step">
            <p class="step__n">01</p>
            <h3>Занять место в колонне</h3>
            <p>Телефон, имя, по желанию машина. Минута, и вас уже слышно.</p>
          </article>
          <article class="step">
            <p class="step__n">02</p>
            <h3>Выйти на связь</h3>
            <p>Что случилось, точка или трасса. Рядом видят сигнал те, кто реально на плече.</p>
          </article>
          <article class="step">
            <p class="step__n">03</p>
            <h3>Выручили друг друга</h3>
            <p>Кто принял — едет. Кто доехал — закрывает. Номер открывается только своим по этому сигналу.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="section section--flat" id="karta">
      <div class="container split">
        <div>
          <header class="section-head">
            <p class="kicker">Карта помощи</p>
            <h2>Кто на плече, а кто уже стоит</h2>
            <p class="lede">Фуры на приёме, те, кто в рейсе, и открытые сигналы. Учебные метки помечены и не зовут в дорогу.</p>
          </header>
          <a class="btn" href="/map.php">Открыть полную карту</a>
        </div>
        <a class="map-teaser" href="/map.php" aria-label="Открыть карту помощи">
          <svg viewBox="0 0 640 360" role="img">
            <title>Схема трасс</title>
            <rect width="640" height="360" fill="#101820"/>
            <path d="M40 250 C 140 250, 180 120, 280 130 S 420 220, 600 80" fill="none" stroke="#243244" stroke-width="14" stroke-linecap="round"/>
            <path d="M40 250 C 140 250, 180 120, 280 130 S 420 220, 600 80" fill="none" stroke="#ff7a1a" stroke-width="3" stroke-dasharray="2 10" stroke-linecap="round"/>
            <path d="M80 80 C 200 140, 260 40, 420 160" fill="none" stroke="#1e4fa3" stroke-width="3" stroke-linecap="round"/>
            <g fill="#ffc933">
              <circle cx="150" cy="210" r="6"/>
              <circle cx="280" cy="130" r="6"/>
              <circle cx="470" cy="168" r="6"/>
            </g>
            <g fill="#e74c3c">
              <circle cx="210" cy="168" r="7"/>
              <circle cx="390" cy="190" r="7"/>
            </g>
          </svg>
          <span class="btn map-teaser__link">Смотреть сигналы</span>
        </a>
      </div>
    </section>

    <section class="quote">
      <div class="container">
        <p class="kicker">15-й канал</p>
        <figure>
          <blockquote>Ночь, дождь, габариты погасли. Ответили свои. Вот и вся навигация, которая нужна в эту минуту.</blockquote>
          <figcaption>Образец тона эфира. Не отчёт о конкретном рейсе.</figcaption>
        </figure>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <header class="section-head">
          <p class="kicker">Красота трассы</p>
          <h2>Шесть дорог, одна колонна</h2>
          <p class="lede">Пока здесь рисунок дороги. Живой кадр встанет на это место, когда появится свой снимок — без стоковых улыбок.</p>
        </header>
        <div class="gallery">
          <?php
          $regions = [
              ['М-7', 'Волга, рассвет', '#ff7a1a', '#3a241c', 'm'],
              ['Р-21', 'Кола, долгий закат', '#6a4a86', '#1b2438', 'r'],
              ['М-4', 'Дон, ночная смена', '#142033', '#ff7a1a', 'm'],
              ['М-5', 'Урал, снег и борт', '#d5dde6', '#1b2531', 'm'],
              ['Р-258', 'Байкал, лёд и скала', '#16324a', '#7fd0d8', 'r'],
              ['Р-217', 'Кавказ, перевал', '#8a3d2f', '#241820', 'r'],
          ];
          foreach ($regions as $region): ?>
            <figure class="region">
              <svg viewBox="0 0 640 360" aria-hidden="true">
                <rect width="640" height="360" fill="<?= e($region[3]) ?>"/>
                <circle cx="480" cy="90" r="46" fill="<?= e($region[2]) ?>" opacity=".9"/>
                <path d="M0 230 C 120 180, 200 250, 320 210 S 520 160, 640 200 V 360 H 0 Z" fill="#0a0e13" opacity=".78"/>
                <path d="M0 250 H640" stroke="<?= e($region[2]) ?>" stroke-width="4" stroke-dasharray="16 18" opacity=".8"/>
                <rect x="70" y="196" width="120" height="36" rx="4" fill="#141b24" stroke="<?= e($region[2]) ?>" stroke-width="3"/>
              </svg>
              <figcaption>
                <?= shield($region[0]) ?>
                <strong><?= e($region[1]) ?></strong>
              </figcaption>
            </figure>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section section--flat" id="ustav">
      <div class="container">
        <header class="section-head">
          <p class="kicker">Устав плеча</p>
          <h2>Коротко, чтобы не путать своих с чужими</h2>
        </header>
        <div class="rules">
          <article class="rule">
            <p class="rule__n">01</p>
            <h3>Как есть</h3>
            <p>Где стоите, что видели, чем можете помочь. Приукрашенный отчёт опаснее молчания.</p>
          </article>
          <article class="rule">
            <p class="rule__n">02</p>
            <h3>Не кормить пустым «еду»</h3>
            <p>Можете подъехать — примите сигнал. Не можете — не обещайте.</p>
          </article>
          <article class="rule">
            <p class="rule__n">03</p>
            <h3>Без предоплаты</h3>
            <p>Липовый груз, задаток незнакомцу, паспорт в личку. За это закрываем, без дискуссии.</p>
          </article>
          <article class="rule">
            <p class="rule__n">04</p>
            <h3>Рекламе здесь не место</h3>
            <p>Рядом с просьбой о помощи не стоят чужие ссылки и флуд.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="section section--flat">
      <div class="container">
        <header class="section-head">
          <p class="kicker">Эфир</p>
          <h2>Так говорят на обочине</h2>
          <p class="lede">Записи ниже — образцы тона, не сводка на сегодня. Свой текст появится в ленте, когда займёте место.</p>
        </header>
        <div class="cards">
          <?php foreach ($posts as $post): ?>
            <article class="post">
              <div class="post__top">
                <?= shield($post['route']) ?>
                <?php if ($post['demo']): ?><span class="sample">Образец</span><?php endif; ?>
              </div>
              <p><?= e($post['body']) ?></p>
              <p class="meta"><?= e($post['author']['name'] ?? '') ?> · <?= e($post['ago']) ?></p>
            </article>
          <?php endforeach; ?>
        </div>
        <div class="actions"><a class="btn btn--ghost" href="/channel.php">Читать эфир</a></div>
      </div>
    </section>

    <section class="section section--flat">
      <div class="container">
        <header class="section-head">
          <p class="kicker">Партнёры</p>
          <h2>Плечо, которое не уезжает</h2>
          <p class="lede">СТО, колонка, койка. Сейчас на витрине образец карточки: так партнёр выглядит ночью. Живые адреса появятся после договора.</p>
        </header>
        <div class="partner-row">
          <?php foreach (array_slice(repo_partners([]), 0, 4) as $partner): ?>
            <a class="wordmark" href="/partners.php">
              <b><?= e($partner['name']) ?></b>
              <span class="meta"><?= e($partner['type_label']) ?> · <?= e($partner['city']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section section--flat faq">
      <div class="container narrow">
        <header class="section-head">
          <p class="kicker">Вопросы</p>
          <h2>Прежде чем выйти на связь</h2>
        </header>
        <div class="faq">
          <details>
            <summary>Это вместо скорой и полиции?</summary>
            <p>Нет. Если жизнь под угрозой или есть пострадавшие — звоните 112. Здесь свои на трассе: колесо, топливо, ночлег, компания до сервиса.</p>
          </details>
          <details>
            <summary>Это бесплатно?</summary>
            <p>Занять место и передать сигнал можно бесплатно. Если позже появится платный инструмент, об этом скажут до оплаты, а не в сноске.</p>
          </details>
          <details>
            <summary>Кто видит мой номер?</summary>
            <p>Никто, пока сигнал не принят. Телефон открывается только вам и тому, кто едет на этот сигнал.</p>
          </details>
          <details>
            <summary>Что с форумом?</summary>
            <p>Длинный разговор о рейсах по-прежнему живёт отдельно, на forum.rosnavigation.ru. Здесь — помощь, которая нужна сейчас.</p>
          </details>
        </div>
      </div>
    </section>

    <section class="cta">
      <div class="container cta__in">
        <p class="kicker">Приём</p>
        <h2>Занять место в колонне</h2>
        <p class="lede">Имя, телефон и пароль. Машину можно добавить потом, на стоянке.</p>
        <div class="actions">
          <a class="btn" href="/auth.php">Встать на приём</a>
          <a class="btn btn--ghost" href="/sos.php">Мне нужна помощь сейчас</a>
        </div>
      </div>
    </section>
<?php
render_footer($meta);
