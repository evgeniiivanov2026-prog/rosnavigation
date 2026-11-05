<?php
declare(strict_types=1);
http_response_code(503);
header('Retry-After: 300');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Эфир на паузе — РосНавигация</title>
  <link rel="stylesheet" href="/css/app.css">
</head>
<body class="page">
  <main class="container narrow narrow--pad">
    <p class="kicker">Связь</p>
    <h1>Эфир на паузе</h1>
    <p class="lede">База не отвечает. Это не конец рейса: обнови страницу через несколько минут. Если человеку плохо — звони 112, не жди сайт.</p>
  </main>
</body>
</html>
