<?php $p = jdec($l['payload']); $src = jdec($l['source']); ?>
<p class="small"><a href="<?= e(url('panel/leads')) ?>">← все заявки</a></p>
<div class="grid2">
  <section class="card">
    <h2><?= e($l['name'] ?: 'Без имени') ?></h2>
    <p class="big"><a href="tel:<?= e(preg_replace('~[^\d+]~', '', $l['phone'])) ?>"><?= e($l['phone']) ?></a></p>
    <?php if ($l['email']): ?><p><a href="mailto:<?= e($l['email']) ?>"><?= e($l['email']) ?></a></p><?php endif; ?>
    <dl class="kv">
      <dt>Тип</dt><dd><?= e(LEAD_TYPES[$l['type']] ?? $l['type']) ?></dd>
      <dt>Дата</dt><dd><?= e(date('d.m.Y H:i', strtotime($l['created_at']))) ?></dd>
      <?php foreach ($p as $k => $v): if (in_array($k, ['name', 'phone', 'email', 'consent'], true) || $v === '' || $v === null) continue; ?>
        <dt><?= e(LEAD_FIELDS[$k] ?? $k) ?></dt>
        <dd><?php if ($k === 'link' && is_string($v) && preg_match('~^https?://~i', $v)): ?><a href="<?= e($v) ?>" target="_blank" rel="noopener">открыть проект ↗</a><?php elseif (is_array($v)): ?><pre><?= e(json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre><?php else: ?><?= nl2br(e($v)) ?><?php endif; ?></dd>
      <?php endforeach; ?>
      <?php if (is_file(DATA_DIR . '/leads/' . $l['id'] . '.jpg')): ?><dt>Картинка</dt><dd><a href="<?= e(url('panel/leads/' . $l['id'] . '/shot')) ?>" target="_blank"><img src="<?= e(url('panel/leads/' . $l['id'] . '/shot')) ?>" alt="Проект" style="max-width:100%;border-radius:10px;border:1px solid var(--line)"></a></dd><?php endif; ?>
      <dt>Страница</dt><dd class="small"><?= e($l['page']) ?></dd>
      <?php if ($src): ?><dt>Источник</dt><dd class="small"><?= e(lead_source($l)) ?></dd><?php endif; ?>
      <dt>Письмо</dt><dd><?= $l['mail_ok'] ? 'отправлено' : '<span class="tag warn">не отправлено</span> <small class="muted">' . e($l['mail_error']) . '</small>' ?></dd>
    </dl>
  </section>
  <form method="post" class="card form">
    <?= csrf_field() ?>
    <h2>Работа с заявкой</h2>
    <?= field('status', 'Статус', $l['status'], ['type' => 'select', 'options' => LEAD_STATUSES]) ?>
    <?= field('note', 'Заметка', $l['note'], ['type' => 'textarea', 'rows' => 5, 'hint' => 'Например: перезвонили, ждёт расчёт с доставкой']) ?>
    <button class="btn">Сохранить</button>
  </form>
</div>
