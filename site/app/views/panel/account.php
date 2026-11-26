<section class="card form narrow">
  <p>Логин: <b><?= e($u['login']) ?></b></p>
  <h2>Сменить пароль</h2>
  <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <?= field('current', 'Текущий пароль', '', ['type' => 'password', 'autocomplete' => 'current-password', 'required' => true]) ?>
    <?= field('new', 'Новый пароль', '', ['type' => 'password', 'autocomplete' => 'new-password', 'required' => true, 'hint' => 'Не короче 10 символов']) ?>
    <?= field('repeat', 'Новый пароль ещё раз', '', ['type' => 'password', 'autocomplete' => 'new-password', 'required' => true]) ?>
    <button class="btn">Сменить пароль</button>
  </form>
</section>
