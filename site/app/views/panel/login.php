<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Вход · Админка Монолит-Вест</title>
<link rel="stylesheet" href="<?= e(asset('assets/panel.css')) ?>">
</head>
<body class="login">
<form class="card login-box" method="post">
  <div class="brand big"><b>Монолит-Вест</b><small>админка сайта</small></div>
  <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
  <?= csrf_field() ?>
  <?= field('login', 'Логин', $_POST['login'] ?? '', ['autocomplete' => 'username', 'required' => true]) ?>
  <?= field('password', 'Пароль', '', ['type' => 'password', 'autocomplete' => 'current-password', 'required' => true]) ?>
  <button class="btn">Войти</button>
</form>
</body>
</html>
