<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · Админка Монолит-Вест</title>
<link rel="stylesheet" href="<?= e(asset('assets/panel.css')) ?>">
</head>
<body>
<header class="top">
  <a class="brand" href="<?= e(url('panel/')) ?>"><b>Монолит-Вест</b><small>админка</small></a>
  <nav class="nav">
    <?php foreach ($nav as $k => $label): ?>
      <a href="<?= e(url('panel/' . $k)) ?>"<?= $active === $k ? ' class="on"' : '' ?>><?= $label ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="me">
    <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">Сайт ↗</a>
    <a href="<?= e(url('panel/account')) ?>"<?= $active === 'account' ? ' class="on"' : '' ?>><?= e($u['name'] ?? '') ?></a>
    <form method="post" action="<?= e(url('panel/logout')) ?>"><?= csrf_field() ?><button class="link">Выйти</button></form>
  </div>
</header>
<main class="wrap">
  <h1><?= e($title) ?></h1>
  <?php foreach (flash() as [$type, $msg]): ?>
    <div class="flash <?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
  <?= $body ?>
</main>
<script src="<?= e(asset('assets/panel.js')) ?>"></script>
</body>
</html>
