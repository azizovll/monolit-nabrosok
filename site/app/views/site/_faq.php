<?php if (!empty($faq)): ?>
<section class="sec faq-sec" id="voprosy">
  <div class="wrap faq">
    <div>
      <span class="eyebrow">Вопросы</span>
      <h2><?= e($title ?? 'Частые вопросы') ?></h2>
      <p class="lead faq-lead">Не нашли ответ? Позвоните, подскажем за пару минут: <a href="tel:<?= e(setting('phone_tel')) ?>" data-goal="phone"><?= e(setting('phone')) ?></a></p>
      <button class="btn btn-line faq-ask" type="button" data-lead="callback" data-title="Задать вопрос" data-sub="Перезвоним и ответим, обычно за 15 минут в рабочее время.">Задать вопрос</button>
    </div>
    <div>
      <?php foreach ($faq as $i => $f): ?>
        <details<?= $i === 0 ? ' open' : '' ?>><summary><?= e($f['q']) ?></summary><div class="ans"><?= md($f['a']) ?></div></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
