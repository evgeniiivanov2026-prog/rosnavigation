<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
http_response_code(404);
$page = [
    'title' => 'Свернули не туда — РосНавигация',
    'description' => 'Такой страницы нет. Вернитесь на трассу.',
    'path' => '/404',
    'nav' => '',
    'body' => 'page-miss',
    'script' => '',
    'robots' => 'noindex,follow',
];
require __DIR__ . '/templates/header.php';
?>
<main id="main" class="miss">
  <div>
    <p class="kicker">404</p>
    <h1>Свернул не туда. Бывает.</h1>
    <p class="lede">Этой страницы нет. Трасса — на главной.</p>
    <p class="stack-top"><a class="btn btn--brand" href="/">Вернуться на трассу</a></p>
  </div>
</main>
<?php require __DIR__ . '/templates/footer.php'; ?>
