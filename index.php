<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$viewer = current_user();
$stats = repo_stats();
$signals = array_slice(repo_sos_near(null, null, null), 0, 3);
$posts = repo_posts(isset($viewer['id']) ? (int) $viewer['id'] : null);
$quote = $posts[0] ?? null;
$partners = array_slice(repo_partners(), 0, 6);

$page = [
    'title' => 'РосНавигация — ты не один на трассе',
    'description' => 'Всероссийская сеть взаимовыручки дальнобойщиков. Сигнал своим, карта помощи и эфир 15-го канала. Своих не бросаем.',
    'path' => '/',
    'nav' => 'home',
    'body' => 'page-home',
    'script' => 'home',
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'РосНавигация',
        'url' => APP_URL,
        'slogan' => 'На 15-м — свои. Ты не один на трассе.',
        'email' => APP_EMAIL,
        'logo' => APP_URL . '/favicon.svg',
        'description' => 'Всероссийская сеть взаимовыручки дальнобойщиков',
        'areaServed' => 'RU',
    ],
];
require __DIR__ . '/templates/header.php';
?>
<main id="main">
  <section class="hero">
    <div class="horizon" aria-hidden="true">
      <div class="horizon__sky"></div>
      <div class="horizon__road"><div class="horizon__lane"></div></div>
      </div>
    <svg class="horizon__truck" viewBox="0 0 760 220" aria-hidden="true">
        <ellipse cx="380" cy="198" rx="240" ry="10" fill="rgba(0,0,0,.35)"/>
        <rect x="36" y="62" width="400" height="96" rx="8" fill="#1B2531" stroke="#FF7A1A" stroke-width="3"/>
        <path d="M78 62v96M150 62v96M222 62v96M294 62v96M366 62v96" stroke="#2E3D4C" stroke-width="2"/>
        <rect x="36" y="142" width="400" height="10" fill="#FF7A1A"/>
        <path d="M436 84h150l62 42v32H436Z" fill="#121A24" stroke="#F2F5F8" stroke-width="3"/>
        <path d="M560 92h52l30 30H560Z" fill="#cfe8ff" opacity=".45"/>
        <rect x="52" y="122" width="16" height="8" rx="2" fill="#FFC933"/>
        <rect x="620" y="136" width="16" height="8" rx="2" fill="#FFC933"/>
        <rect x="36" y="154" width="22" height="8" fill="#E74C3C"/>
        <g fill="#07090C" stroke="#D5DEE6" stroke-width="3">
          <circle cx="130" cy="172" r="28"/><circle cx="198" cy="172" r="28"/>
          <circle cx="508" cy="172" r="28"/><circle cx="584" cy="172" r="28"/>
        </g>
      </svg>
    <div class="hero__scrim" aria-hidden="true"></div>
    <div class="container hero__layout">
      <div class="hero__copy">
        <p class="kicker"><span class="live-dot" aria-hidden="true"></span> Ночная смена · Россия</p>
        <h1>Рассвет. Трасса. Свои.</h1>
        <p class="lede">Всероссийская сеть взаимовыручки на 15-м канале. Ты не один на трассе.</p>
        <div class="hero__actions">
          <a class="btn btn--danger btn--lg" href="/sos"><?= icon('radio', 'icon icon--sm') ?> Выйти на связь</a>
          <a class="btn btn--ghost btn--lg" href="/auth"><?= icon('truck', 'icon icon--sm') ?> Встать на приём</a>
        </div>
        <p class="hero__oath">Своих не бросаем.</p>
      </div>
      <aside class="ether" aria-label="Живой эфир">
        <p class="ether__kicker"><span class="live-dot" aria-hidden="true"></span> На связи</p>
        <p class="ether__num" id="live-online" data-live="online"><?= e(ru_num((int) $stats['online'])) ?></p>
        <p class="ether__label">водителей в эфире</p>
        <dl class="ether__stats">
          <div><dt>Сигналы</dt><dd id="live-sos" data-live="sos"><?= (int) $stats['sos'] ?></dd></div>
          <div><dt>Выручили</dt><dd id="live-helps" data-live="helps"><?= e(ru_num((int) $stats['helps'])) ?></dd></div>
        </dl>
        <ul class="ether__list" id="live-signals">
          <?php foreach ($signals as $signal): ?>
            <li>
              <a href="/sos?id=<?= (int) $signal['id'] ?>">
                <span><?= e($signal['type_label']) ?> · <?= e($signal['name']) ?></span>
                <span class="mono"><?= e($signal['route'] ?: 'трасса') ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
        <a class="ether__more" href="/map">Открыть полную карту</a>
      </aside>
    </div>
  </section>

  <div class="marquee">
    <p class="visually-hidden">Трассы сети: М-4 Дон, М-5 Урал, М-7 Волга, М-11 Нева, М-12 Восток, Р-21 Кола, Р-217 Кавказ, Р-258 Байкал.</p>
    <div class="marquee__track" aria-hidden="true">
      <?php for ($i = 0; $i < 2; $i++): ?>
        <p class="marquee__row">
          <span>М-4 «Дон»</span><span>М-5 «Урал»</span><span>М-7 «Волга»</span><span>М-11 «Нева»</span>
          <span>М-12 «Восток»</span><span>Р-21 «Кола»</span><span>Р-217 «Кавказ»</span><span>Р-258 «Байкал»</span>
          <span>Своих не бросаем</span>
        </p>
      <?php endfor; ?>
    </div>
  </div>

  <section class="section" id="how">
    <div class="container">
      <header class="section__head">
        <p class="kicker">Как это работает</p>
        <h2>Три шага, без анкеты на десять экранов</h2>
        <p class="lede">Связь на трассе слабая, время короткое. Поэтому путь короткий.</p>
      </header>
      <ol class="steps">
        <li><span class="steps__n">01</span><h3>Занять место в колонне</h3><p>Телефон, имя, город и кем выходите на связь: ищете помощь, помогаете или и то и то.</p></li>
        <li><span class="steps__n">02</span><h3>Сказать, что случилось</h3><p>Встал, обсох, притомился, ДТП. Точка с телефона или километр трассы. Удержание две секунды — чтобы не нажать случайно.</p></li>
        <li><span class="steps__n">03</span><h3>Дождаться своих рядом</h3><p>Сигнал видят те, кто на плече. Можно позвонить, принять и доехать. Потом — доброй дороги.</p></li>
      </ol>
    </div>
  </section>

  <section class="section section--next" id="roads">
    <div class="container">
      <header class="section__head">
        <p class="kicker">Дорога</p>
        <h2>На трассе нельзя остаться одному</h2>
        <p class="lede">Карты рисуют маршрут для легковушки. Чаты полнятся чужими. Когда машина встаёт, нужны свои.</p>
      </header>
      <div class="pain">
        <article><p class="pain__n">01</p><h3>Карты не знают фуру</h3><p>Высота моста, вес, рамка, запрет на въезд. В обычной навигации это либо врёт, либо молчит.</p></article>
        <article><p class="pain__n">02</p><h3>Один на тысячу километров</h3><p>Связь есть, а своих рядом нет. Пока ищете, кому написать, рейс уже стоит.</p></article>
        <article><p class="pain__n">03</p><h3>Помощь, которая не приезжает</h3><p>Совет в общем чате не поднимет кабину и не привезёт запаску. Нужен человек, который действительно рядом.</p></article>
        <article><p class="pain__n">04</p><h3>Мошенники говорят громче</h3><p>Липовые грузы, предоплата «диспетчеру», слитые номера. Здесь это не норма, а повод закрыть тему.</p></article>
      </div>
    </div>
  </section>

  <section class="section section--next" id="map-teaser">
    <div class="container">
      <div class="map-teaser">
        <svg viewBox="0 0 640 360" role="img" aria-label="Схема трасс: сигналы на М-7, М-4 и Р-21">
          <rect width="640" height="360" rx="24" fill="#0E1620"/>
          <path d="M40 300 C 160 300 180 80 300 80 C 430 80 470 220 600 40" fill="none" stroke="#2E3D4C" stroke-width="14" stroke-linecap="round"/>
          <path d="M80 40 C 140 140 220 200 300 230 C 400 270 480 250 600 300" fill="none" stroke="#FF7A1A" stroke-width="4" stroke-linecap="round"/>
          <g fill="#E74C3C"><circle cx="250" cy="120" r="8"/><circle cx="430" cy="250" r="8"/><circle cx="140" cy="70" r="8"/></g>
          <g fill="#2ECC71"><circle cx="320" cy="200" r="6"/><circle cx="520" cy="90" r="6"/><circle cx="200" cy="250" r="6"/></g>
          <g fill="#F2F5F8" font-family="ui-monospace, monospace" font-size="14">
            <text x="196" y="108">М-7</text><text x="444" y="242">М-4</text><text x="92" y="58">Р-21</text>
          </g>
        </svg>
        <div class="map-teaser__side">
          <p class="kicker">Карта помощи</p>
          <h2>Кто на плече</h2>
          <p class="lede">Фуры на приёме, на маршруте и те, кому плохо прямо сейчас.</p>
          <a class="btn btn--brand" href="/map">Открыть полную карту</a>
        </div>
      </div>
    </div>
  </section>

  <section class="broadcast" id="ether" aria-label="15-й канал">
    <div class="container">
      <p class="kicker">15-й канал</p>
      <?php if ($quote): ?>
        <blockquote class="quote"><?= e($quote['body']) ?></blockquote>
        <footer><?= e($quote['name']) ?><?= $quote['route'] ? ' · ' . e($quote['route']) : '' ?></footer>
      <?php else: ?>
        <blockquote class="quote">Помог сегодня на М-7. Шланг, двадцать минут, человек снова в рейсе.</blockquote>
        <footer>Волга · М-7</footer>
      <?php endif; ?>
      <p class="stack-top"><a class="btn btn--ghost" href="/channel">Слушать эфир</a></p>
    </div>
  </section>

  <section class="section" id="routes">
    <div class="container">
      <header class="section__head">
        <p class="kicker">Красота трассы</p>
        <h2>Шесть дорог, одна колонна</h2>
        <p class="lede">Рассветы и перевалы, по которым сеть узнаёт своих. Живые кадры встанут на эти места, когда их снимут свои.</p>
      </header>
      <div class="routes">
        <article class="route-card route-card--volga"><div class="route-card__body"><p class="mono">М-7</p><h3>Волга</h3><p>Рассвет над плёсом. Фуры идут цепочкой на Казань.</p></div></article>
        <article class="route-card route-card--kola"><div class="route-card__body"><p class="mono">Р-21</p><h3>Кола</h3><p>Закат короче разговора. Север не прощает пустой бак.</p></div></article>
        <article class="route-card route-card--don"><div class="route-card__body"><p class="mono">М-4</p><h3>Дон</h3><p>Ночь, рамка, мокрый асфальт. Хвост виден по стопам.</p></div></article>
        <article class="route-card route-card--ural"><div class="route-card__body"><p class="mono">М-5</p><h3>Урал</h3><p>Снег на перевале. Здесь не спорят с погодой.</p></div></article>
        <article class="route-card route-card--baikal"><div class="route-card__body"><p class="mono">Р-258</p><h3>Байкал</h3><p>Спуск к воде. Цепи — не украшение.</p></div></article>
        <article class="route-card route-card--kavkaz"><div class="route-card__body"><p class="mono">Р-217</p><h3>Кавказ</h3><p>Боковой ветер слышно раньше, чем видно.</p></div></article>
      </div>
    </div>
  </section>

  <section class="section section--next" id="partners">
    <div class="container">
      <header class="section__head">
        <p class="kicker">Плечо</p>
        <h2>Сервис, где встаёт фура</h2>
        <p class="lede">АЗС, ремонт, ночлег. Не витрина, а места, куда можно свернуть ночью.</p>
      </header>
      <div class="wordmarks">
        <?php foreach ($partners as $partner): ?>
          <a href="/partners?type=<?= e($partner['type']) ?>"><?= e($partner['name']) ?></a>
        <?php endforeach; ?>
      </div>
      <p class="stack-top"><a class="btn btn--ghost" href="/partners">Весь список</a></p>
    </div>
  </section>

  <section class="section section--next" id="forum">
    <div class="container">
      <header class="section__head">
        <p class="kicker">Форум</p>
        <h2>Длинный разговор живёт отдельно</h2>
        <p class="lede">Эфир — для того, что происходит сейчас. Тема, к которой возвращаются в следующем рейсе, лежит на форуме и не тонет через час.</p>
      </header>
      <div class="board">
        <?php
        $boards = [
            ['Помощь на дороге', 'Срочно, техпомощь, кто рядом'],
            ['Отчёты о дорогах', 'Перекрытия, рамки, посты, погода'],
            ['Стоянки и АЗС', 'Ночёвка, душ, цена, влезет ли фура'],
            ['Общение', 'Свои, без ленты и рекламы'],
            ['Логистика', 'Направления и документы, без серых схем'],
        ];
        foreach ($boards as $board): ?>
          <a class="board__link" href="<?= e(APP_FORUM) ?>">
            <span class="board__name"><?= e($board[0]) ?></span>
            <span class="board__desc"><?= e($board[1]) ?></span>
            <span class="board__go" aria-hidden="true">→</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="section section--next" id="rules">
    <div class="container">
      <header class="section__head">
        <p class="kicker">Устав</p>
        <h2>Короткие правила</h2>
        <p class="lede">Их мало. Нарушать незачем: на трассе и так хватает чужих.</p>
      </header>
      <ol class="charter">
        <li><h3>Честность</h3><p>Пишите, как есть: где стоите, что видели, чем можете помочь. Приукрашенный отчёт опаснее молчания.</p></li>
        <li><h3>Взаимопомощь</h3><p>Можете подъехать — напишите. Не можете — не кормите человека пустым «уже еду».</p></li>
        <li><h3>Запрет мошенничества</h3><p>Фейковые грузы, предоплата незнакомцу, выманивание документов и номеров. За это — бан, без дискуссии.</p></li>
        <li><h3>Запрет спама</h3><p>Реклама, флуд и чужие ссылки не стоят рядом с просьбой о помощи.</p></li>
      </ol>
    </div>
  </section>

  <section class="section section--next" id="faq">
    <div class="container faq">
      <header class="section__head">
        <p class="kicker">Вопросы</p>
        <h2>Спрашивают до того, как выйти на связь</h2>
      </header>
      <details><summary>Что такое РосНавигация?</summary><p>Сеть взаимовыручки дальнобойщиков. Сигнал своим, карта тех, кто рядом, и разговор по делу. Девиз один: своих не бросаем.</p></details>
      <details><summary>Это вместо 112?</summary><p>Нет. Если угроза жизни, авария с пострадавшими или нужна скорая — сначала 112. Сайт зовёт своих, он не служба спасения.</p></details>
      <details><summary>Это бесплатно?</summary><p>Занять место в колонне и пользоваться помощью, картой и эфиром можно бесплатно. Если позже появится платный инструмент, об этом скажут до оплаты, а не в сноске.</p></details>
      <details><summary>Где форум?</summary><p>Длинные темы — на <?= e(parse_url(APP_FORUM, PHP_URL_HOST) ?: 'форуме') ?>. Регистрация там отдельная: форма на этом сайте не заводит учётку форума.</p></details>
      <details><summary>Как здесь защищаются от мошенников?</summary><p>Имя, репутация выручек и жалоба модератору. «Переведи предоплату» и анонимный диспетчер не считаются нормой.</p></details>
    </div>
  </section>

  <section class="cta-band" id="join">
    <div class="container">
      <div class="cta-band__box">
        <p class="kicker">Приём</p>
        <h2>Занять место в колонне</h2>
        <p class="lede">Имя, телефон и пара слов о машине. Дальше вы на связи, даже если сегодня просто едете.</p>
        <a class="btn btn--brand btn--lg" href="/auth">Встать на приём</a>
      </div>
    </div>
  </section>
</main>
<?php require __DIR__ . '/templates/footer.php'; ?>
