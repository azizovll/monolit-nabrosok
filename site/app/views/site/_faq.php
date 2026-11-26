<?php if (!empty($faq)): ?>
<section class="sec faq-sec" id="voprosy">
  <div class="wrap faq">
    <div>
      <span class="eyebrow">Вопросы</span>
      <h2><?= e($title ?? 'Частые вопросы') ?></h2>
      <p class="lead">Не нашли ответ? Позвоните, подскажем за пару минут: <a href="tel:<?= e(setting('phone_tel')) ?>" data-goal="phone"><?= e(setting('phone')) ?></a></p>
    </div>
    <div>
      <?php foreach ($faq as $i => $f): ?>
        <details<?= $i === 0 ? ' open' : '' ?>><summary><?= e($f['q']) ?></summary><div class="ans"><?= md($f['a']) ?></div></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
