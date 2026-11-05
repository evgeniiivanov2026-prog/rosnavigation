<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/templates/scene.php';

$stats = repo_stats();
$signals = repo_signals();
$drivers = repo_drivers();
$partners = array_slice(repo_partners(), 0, 4);
$pageTitle = 'РосНавигация — свои на трассе';
$pageDescription = 'Всероссийская сеть взаимовыручки дальнобойщиков. Сигнал своим, карта помощи и эфир. Ты не один на трассе.';
$pageCanonical = APP_URL . '/';
$pageActive = 'home';
$bodyClass = 'page-home';
$pageScript = '/js/pages/home.js';
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'РосНавигация',
    'url' => APP_URL,
    'slogan' => 'На 15-м — свои. Ты не один на трассе.',
    'email' => 'info@rosnavigation.ru',
    'logo' => APP_URL . '/assets/icons/logo.svg',
    'description' => 'Всероссийская сеть взаимовыручки дальнобойщиков',
    'areaServed' => 'RU',
];

require __DIR__ . '/templates/header.php';
?>
<section class="hero">
  <div class="container hero__grid">
    <div class="hero__copy">
      <p class="kicker">Ночная смена · Россия</p>
      <h1>Рассвет. Трасса. Свои.</h1>
      <p class="lede">Всероссийская сеть взаимовыручки на 15-м канале. Ты не один на трассе.</p>
      <div class="actions">
        <a class="btn btn--danger" href="/sos">Выйти на связь</a>
        <a class="btn btn--primary" href="/auth">Встать на приём</a>
      </div>
      <p class="fine">Своих не бросаем.</p>
    </div>
    <div class="scene"><?= dawn_scene() ?></div>
  </div>
</section>

<section class="air" aria-label="Живой эфир">
  <div class="container air__grid">
    <div class="air__cell">
      <p class="air__live fine">сейчас</p>
      <p class="air__num" id="live-count" aria-live="polite"><?= (int) $stats['online'] ?></p>
      <p class="air__label">на связи</p>
    </div>
    <div class="air__cell">
      <p class="fine">статус</p>
      <p class="air__num" id="live-air"><?= (int) $stats['on_air'] ?></p>
      <p class="air__label">на приёме</p>
    </div>
    <div class="air__cell">
      <p class="fine">за всё время</p>
      <p class="air__num" id="live-helped"><?= (int) $stats['helped'] ?></p>
      <p class="air__label">выручили друг друга</p>
    </div>
  </div>
</section>

<section class="section" id="how">
  <div class="container">
    <header class="section__head">
      <p class="kicker">Маршрут</p>
      <h2>Три шага, без анкеты на десять экранов</h2>
    </header>
    <ol class="steps">
      <li class="step">
        <p class="step__n">01</p>
        <h3>Занять место в колонне</h3>
        <p>Имя, номер и пара слов о машине. Чтобы свои понимали, кто вышел на связь.</p>
      </li>
      <li class="step">
        <p class="step__n">02</p>
        <h3>Передать сигнал</h3>
        <p>Свои в радиусе 15 км видят, что случилось, и могут ответить. Не лента — человек рядом.</p>
      </li>
      <li class="step">
        <p class="step__n">03</p>
        <h3>Выручили друг друга</h3>
        <p>Закрыл сигнал — помощь остаётся в профиле. Репутация растёт от дела, не от регистрации.</p>
      </li>
    </ol>
  </div>
</section>

<section class="section" id="map-teaser">
  <div class="container">
    <header class="section__head">
      <p class="kicker">Плечо</p>
      <h2>Кто сейчас на трассе</h2>
      <p class="lede">Схема эфира, не навигатор. Мосты, рамки и высоту сверкай с отчётами своих и с головой.</p>
    </header>
    <div class="teaser">
      <div class="teaser__map" aria-hidden="true">
        <div class="teaser__roads"></div>
        <?php foreach (array_slice($signals, 0, 3) as $signal): ?>
          <?php [$x, $y] = teaser_point((float) $signal['lat'], (float) $signal['lng']); ?>
          <span class="pin pin--sos" style="left: <?= e((string) $x) ?>%; top: <?= e((string) $y) ?>%"><span><?= e($signal['route'] ?: 'SOS') ?></span></span>
        <?php endforeach; ?>
        <?php foreach (array_slice($drivers, 0, 5) as $driver): ?>
          <?php if (!isset($driver['lat'])) continue; ?>
          <?php [$x, $y] = teaser_point((float) $driver['lat'], (float) $driver['lng']); ?>
          <span class="pin" style="left: <?= e((string) $x) ?>%; top: <?= e((string) $y) ?>%"><span><?= e($driver['route'] ?: '') ?></span></span>
        <?php endforeach; ?>
      </div>
      <div>
        <h3>Карта помощи</h3>
        <p>Сигналы, кто на приёме и кто на маршруте. Ночью, со слабой связью, список остаётся даже если подложка карты молчит.</p>
        <div class="actions">
          <a class="btn btn--primary" href="/map">Открыть полную карту</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="quote-band" id="ether">
  <div class="container">
    <div class="quote-band__box">
      <p class="kicker">Голос</p>
      <h2>15-й канал</h2>
      <figure class="quote">
        <blockquote>Встал ночью под Казанью. Свои ответили через четыре минуты.</blockquote>
        <figcaption>Голос с М-7</figcaption>
      </figure>
      <a class="btn btn--ghost" href="/channel">Читать эфир</a>
    </div>
  </div>
</section>

<section class="section" id="roads">
  <div class="container">
    <header class="section__head">
      <p class="kicker">Страна</p>
      <h2>Красота трассы</h2>
      <p class="lede">Шесть дорог, на которых братство и держится. Листай — это не открытки, это смена.</p>
    </header>
    <div class="gallery">
      <?php
      $roads = [
          ['m7.svg', 'М-7', 'Волга, рассвет', 'Рисунок рассвета на М-7: фура у реки'],
          ['kola.svg', 'Р-21', 'Кола, закат', 'Рисунок заката на Р-21: сосны и дорога'],
          ['don.svg', 'М-4', 'Дон, ночь', 'Рисунок ночной М-4: огни трассы'],
          ['ural.svg', 'М-5', 'Урал, снег', 'Рисунок зимней М-5: снег и перевал'],
          ['baikal.svg', 'Р-258', 'Байкал', 'Рисунок Р-258: берег и холодная вода'],
          ['caucasus.svg', 'Р-217', 'Кавказ', 'Рисунок Р-217: хребет и дорога в долине'],
      ];
      foreach ($roads as [$file, $code, $caption, $alt]): ?>
        <figure>
          <img src="/assets/img/routes/<?= e($file) ?>" alt="<?= e($alt) ?>" width="640" height="400" class="photo-trucker" loading="lazy">
          <figcaption><span class="route"><?= e($code) ?></span> <?= e($caption) ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="services">
  <div class="container">
    <header class="section__head">
      <p class="kicker">Плечо у дороги</p>
      <h2>Кто пускает фуру</h2>
      <p class="lede">Сервис, колонка, ночлег. Серые, пока не наведёшь: цвет — это внимание, не реклама.</p>
    </header>
    <ul class="marks">
      <?php foreach ($partners as $partner): ?>
        <li><?= e($partner['name']) ?></li>
      <?php endforeach; ?>
    </ul>
    <div class="actions gap">
      <a class="btn btn--ghost" href="/partners">Весь список</a>
    </div>
  </div>
</section>

<section class="section" id="rules">
  <div class="container">
    <header class="section__head">
      <p class="kicker">Устав</p>
      <h2>Короткие правила</h2>
      <p class="lede">Их мало. На трассе и так хватает чужих.</p>
    </header>
    <div class="charter">
      <article>
        <h3>Честность</h3>
        <p>Пиши, как есть: где стоишь, что видел, чем можешь помочь. Приукрашенный отчёт опаснее молчания.</p>
      </article>
      <article>
        <h3>Взаимопомощь</h3>
        <p>Можешь подъехать — скажи. Не можешь — не корми человека пустым «уже еду».</p>
      </article>
      <article>
        <h3>Никакого развода</h3>
        <p>Фейковый груз, предоплата незнакомцу, выманивание документов. За это бан, без дискуссии.</p>
      </article>
      <article>
        <h3>Без помойки</h3>
        <p>Реклама и чужие ссылки не стоят рядом с просьбой о помощи.</p>
      </article>
    </div>
  </div>
</section>

<section class="section" id="faq">
  <div class="container">
    <header class="section__head">
      <p class="kicker">Вопросы</p>
      <h2>До того как выйти на связь</h2>
    </header>
    <div class="faq">
      <details>
        <summary>Что такое РосНавигация?</summary>
        <p>Сеть взаимовыручки дальнобойщиков. Сигнал, карта своих и эфир. Девиз один: своих не бросаем.</p>
      </details>
      <details>
        <summary>Это навигатор для фуры?</summary>
        <p>Нет. Здесь люди и отчёты, не высота моста в метрах. Маршрут с габаритом сверкай отдельно, сайт не подменяет голову.</p>
      </details>
      <details>
        <summary>Это бесплатно?</summary>
        <p>Занять место в колонне и пользоваться эфиром можно бесплатно. Если позже появится платный инструмент, об этом скажут до оплаты, а не в сноске.</p>
      </details>
      <details>
        <summary>Где длинный разговор?</summary>
        <p>Короткое — здесь. Темы, которые живут не один рейс, остаются на форуме братства.</p>
      </details>
    </div>
  </div>
</section>

<section class="finale" id="join">
  <div class="container finale__box">
    <p class="kicker">Приём</p>
    <h2>Занять место в колонне</h2>
    <p>Имя, номер и пара слов о машине. Дальше — эфир, а не рассылка.</p>
    <a class="btn btn--primary" href="/auth">Встать на приём</a>
  </div>
</section>
<?php require __DIR__ . '/templates/footer.php';