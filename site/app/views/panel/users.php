<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<?php if ($created): ?>
  <div class="flash ok">Логин <b><?= e($created['login']) ?></b>, пароль <code><?= e($created['pass']) ?></code>. Передайте его человеку: больше он нигде не показывается. После входа пароль можно сменить в «Мой аккаунт».</div>
<?php endif; ?>

<div class="table-wrap">
<table class="tbl">
  <thead><tr><th>Логин</th><th>Имя</th><th>Роль</th><th>Последний вход</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($users as $x): ?>
    <tr>
      <td><b><?= e($x['login']) ?></b></td><td><?= e($x['name']) ?></td>
      <td><?= $x['role'] === 'admin' ? 'администратор' : 'редактор' ?></td>
      <td class="small"><?= $x['last_login'] ? e(date('d.m.Y H:i', strtotime($x['last_login']))) : '—' ?></td>
      <td class="row">
        <form method="post" data-confirm="Выдать новый пароль для <?= e($x['login']) ?>?"><?= csrf_field() ?><input type="hidden" name="act" value="reset"><input type="hidden" name="id" value="<?= $x['id'] ?>"><button class="link small">новый пароль</button></form>
        <?php if ((int)$x['id'] !== (int)$me['id']): ?>
        <form method="post" data-confirm="Удалить пользователя <?= e($x['login']) ?>?"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= $x['id'] ?>"><button class="link small danger">удалить</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<form method="post" class="card form narrow">
  <?= csrf_field() ?><input type="hidden" name="act" value="create">
  <h2>Добавить пользователя</h2>
  <?= field('login', 'Логин (латиницей)', '', ['required' => true, 'pattern' => '[a-z0-9_.\-]{3,32}']) ?>
  <?= field('name', 'Имя', '') ?>
  <?= field('role', 'Роль', 'editor', ['type' => 'select', 'options' => ['editor' => 'Редактор: цены, товары, заявки', 'admin' => 'Администратор: ещё и пользователи']]) ?>
  <button class="btn">Создать и показать пароль</button>
</form>
