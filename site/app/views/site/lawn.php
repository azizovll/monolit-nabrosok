<section class="sec page-top">
  <div class="wrap prod">
    <div class="prod-gal">
      <div class="prod-img"><?php if ($p && $p['images_a']): ?><?= picture($p['images_a'][0], 'Газонная решётка', '(max-width: 900px) 100vw, 520px', ['eager' => true]) ?>
        <?php else: ?><div class="lawn-ill" role="img" aria-label="Иллюстрация газонной решётки"></div><?php endif; ?></div>
      <p class="muted small">Иллюстрация. Внешний вид продукции может отличаться, образцы смотрите на площадке.</p>
    </div>
    <div class="prod-info">
      <span class="eyebrow">Новинка</span>
      <h1><?= e($c['h1'] ?: 'Газонная решётка') ?></h1>
      <p class="lead"><?= e($c['intro']) ?></p>
      <?php if ($p): ?>
        <div class="pf-price"><?= $p['price'] ? '<b>' . number_format((int)$p['price'], 0, ',', ' ') . ' ₽</b><span>/' . e($p['price_unit']) . '</span>' : '<b>Цена по запросу</b>' ?></div>
        <?php if ($p['specs_a']): ?><dl class="specs"><?php foreach ($p['specs_a'] as [$k, $v]): ?><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd><?php endforeach; ?></dl><?php endif; ?>
        <div class="cta-row">
          <button class="btn btn-brand" type="button" data-lead="product" data-product="Газонная решётка" data-qty="1">Оставить заявку</button>
          <a class="btn btn-line" href="<?= e(url('3d/')) ?>">Примерить в 3D</a>
        </div>
      <?php endif; ?>
      <ul class="ticks small"><li>Парковка на газоне без луж и колеи</li><li>Трава растёт сквозь ячейки</li><li>Доставка манипулятором по Пскову и области</li></ul>
    </div>
  </div>
</section>
<?php if (trim($c['seo_text'])): ?><section class="sec pt0"><div class="wrap text narrow-text"><?= md($c['seo_text']) ?></div></section><?php endif; ?>
<?= render('site/_cta', ['h' => 'Посчитаем газонную решётку под вашу парковку', 'type' => 'product']) ?>
