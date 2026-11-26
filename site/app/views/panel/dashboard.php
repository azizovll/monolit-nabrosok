<div class="stats">
  <?php foreach ($stats as $k => $v): ?><div class="stat"><b><?= $v ?></b><span><?= e($k) ?></span></div><?php endforeach; ?>
</div>

<?php if ($failed): ?>
  <div class="flash err">Писем с заявками не отправилось: <?= $failed ?>. Заявки сохранены, смотрите раздел «Заявки». Проверьте настройку почты.</div>
<?php endif; ?>

<div class="grid2">
  <section class="card">
    <h2>Последние заявки <a class="small" href="<?= e(url('panel/leads')) ?>">все →</a></h2>
    <?php if (!$leads): ?><p class="muted">Заявок пока нет.</p><?php endif; ?>
    <ul class="list">
      <?php foreach ($leads as $l): ?>
        <li><a href="<?= e(url('panel/leads/' . $l['id'])) ?>">
          <span class="tag st-<?= e($l['status']) ?>"><?= e(LEAD_STATUSES[$l['status']] ?? $l['status']) ?></span>
          <b><?= e($l['name'] ?: 'Без имени') ?></b> <?= e($l['phone']) ?>
          <small class="muted"><?= e(LEAD_TYPES[$l['type']] ?? $l['type']) ?> · <?= e(date('d.m H:i', strtotime($l['created_at']))) ?></small>
        </a></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="card">
    <h2>Проверить в каталоге (<?= count($notes) ?>)</h2>
    <p class="muted small">Заметки к товарам: что уточнить. Когда исправите, очистите поле «Заметка» в товаре.</p>
    <ul class="list">
      <?php foreach ($notes as $n): ?>
        <li><a href="<?= e(url('panel/products/' . $n['id'])) ?>"><b><?= e($n['name']) ?></b><small class="muted"><?= e($n['note']) ?></small></a></li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>

<section class="card">
  <h2>Быстрые действия</h2>
  <div class="row">
    <a class="btn" href="<?= e(url('panel/paving')) ?>">Цены на брусчатку</a>
    <a class="btn ghost" href="<?= e(url('panel/products')) ?>">Цены на ЖБИ</a>
    <a class="btn ghost" href="<?= e(url('panel/products/new')) ?>">Добавить товар</a>
    <a class="btn ghost" href="<?= e(url('panel/settings')) ?>">Телефон, MAX, реквизиты</a>
  </div>
</section>
