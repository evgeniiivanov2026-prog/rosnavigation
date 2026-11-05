<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

http_response_code(404);
$pageTitle = 'Свернул не туда — РосНавигация';
$pageDescription = 'Такой страницы нет. Вернись на трассу.';
$pageCanonical = APP_URL . '/';
$pageActive = '';
$bodyClass = 'page-lost';
require __DIR__ . '/templates/header.php';
?>
<div class="container lost">
  <p class="kicker">404</p>
  <h1>Свернул не туда. Бывает.</h1>
  <p class="lede">Этой страницы нет. Трасса — на главной, сигнал — если встал.</p>
  <a class="btn btn--primary" href="/">Вернуться на трассу</a>
</div>
<?php require __DIR__ . '/templates/footer.php';