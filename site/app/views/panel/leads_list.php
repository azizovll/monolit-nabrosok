<div class="row between">
  <div class="tabs">
    <a class="<?= $st === '' ? 'on' : '' ?>" href="<?= e(url('panel/leads')) ?>">Все</a>
    <?php foreach (LEAD_STATUSES as $k => $v): ?>
      <a class="<?= $st === $k ? 'on' : '' ?>" href="<?= e(url('panel/leads?status=' . $k)) ?>"><?= e($v) ?> <small><?= $counts[$k] ?? 0 ?></small></a>
    <?php endforeach; ?>
  </div>
  <a class="btn ghost small" href="<?= e(url('panel/leads/export')) ?>">Выгрузить в Excel</a>
</div>

<?php if (!$items): ?><p class="muted">Заявок нет.</p><?php else: ?>
<div class="table-wrap">
<table class="tbl leads">
  <thead><tr><th>№</th><th>Дата</th><th>Клиент</th><th>Что</th><th>Статус</th></tr></thead>
  <tbody>
  <?php foreach ($items as $l): ?>
    <tr class="<?= $l['status'] === 'new' ? 'is-new' : '' ?>">
      <td><a href="<?= e(url('panel/leads/' . $l['id'])) ?>"><?= $l['id'] ?></a></td>
      <td class="nowrap"><?= e(date('d.m.Y H:i', strtotime($l['created_at']))) ?></td>
      <td><a href="<?= e(url('panel/leads/' . $l['id'])) ?>"><b><?= e($l['name'] ?: 'Без имени') ?></b></a><br><a href="tel:<?= e(preg_replace('~[^\d+]~', '', $l['phone'])) ?>"><?= e($l['phone']) ?></a></td>
      <td><span class="muted small"><?= e(LEAD_TYPES[$l['type']] ?? $l['type']) ?></span><br><?= e(mb_strimwidth(lead_summary($l), 0, 120, '…')) ?></td>
      <td><span class="tag st-<?= e($l['status']) ?>"><?= e(LEAD_STATUSES[$l['status']] ?? $l['status']) ?></span><?php if (!$l['mail_ok'] && $l['status'] !== 'spam'): ?><br><span class="tag warn">письмо не ушло</span><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
