<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

http_response_code(404);
$GLOBALS['page'] = 'missing';
$meta = [
    'title' => 'Свернули не туда — РосНавигация',
    'description' => 'Такой страницы нет. Вернитесь на трассу.',
    'canonical' => APP_URL . '/',
];
render_header($meta);
?>
    <section class="section container miss">
      <p class="kicker">404</p>
      <h1>Свернули не туда. Бывает.</h1>
      <p class="lede">Этой страницы нет. Трасса — на главной, сигнал — в одном нажатии.</p>
      <div class="actions">
        <a class="btn" href="/">Вернуться на трассу</a>
        <a class="btn btn--ghost" href="/map.php">Открыть карту</a>
      </div>
    </section>
<?php
render_footer($meta);
