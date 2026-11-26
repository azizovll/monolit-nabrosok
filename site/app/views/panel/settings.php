<form method="post" class="form" data-dirty-guard>
  <?= csrf_field() ?>
  <?php foreach ($form as $group => $fields): ?>
    <section class="card">
      <h2><?= e($group) ?></h2>
      <div class="grid2">
        <?php foreach ($fields as $k => [$label, $o]): ?>
          <?= field($k, $label, ($o['type'] ?? '') === 'checkbox' ? (int)setting($k) : setting($k), $o) ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
  <div class="sticky-save"><button class="btn">Сохранить настройки</button></div>
</form>
