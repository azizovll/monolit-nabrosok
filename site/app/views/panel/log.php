<?php $names = ['price' => 'цена', 'update' => 'изменение', 'create' => 'создание', 'delete' => 'удаление', 'images' => 'фото', 'setting' => 'настройка', 'status' => 'статус заявки', 'password' => 'смена пароля', 'reset_password' => 'новый пароль', 'save' => 'изменение', 'upload' => 'загрузка фото', 'delete_img' => 'удаление фото']; ?>
<?php if (!$items): ?><p class="muted">Изменений пока нет.</p><?php else: ?>
<div class="table-wrap">
<table class="tbl">
  <thead><tr><th>Когда</th><th>Кто</th><th>Действие</th><th>Что</th></tr></thead>
  <tbody>
  <?php foreach ($items as $a): ?>
    <tr>
      <td class="nowrap small"><?= e(date('d.m.Y H:i', strtotime($a['at']))) ?></td>
      <td><?= e($a['uname'] ?? 'система') ?></td>
      <td><?= e($names[$a['action']] ?? $a['action']) ?></td>
      <td class="small"><?= e($a['details'] ?: $a['entity'] . ($a['entity_id'] ? ' #' . $a['entity_id'] : '')) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
