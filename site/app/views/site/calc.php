<section class="sec page-top calc-sec calc-page">
  <div class="wrap">
    <h1 class="sr-only">Калькулятор брусчатки</h1>
    <?= render('site/_calc', ['calc' => $calc, 'compact' => false]) ?>
  </div>
</section>
<?php if ($kerbs): ?>
<section class="sec">
  <div class="wrap">
    <h2>Бордюр по краю</h2>
    <p class="lead">Без бордюра крайние ряды плитки со временем расходятся. Длина бордюра примерно равна периметру площадки.</p>
    <div class="ptable">
      <?php foreach ($kerbs as $k): ?>
        <div class="prow"><span class="pname"><?= e($k['name']) ?></span><span class="pspec"><?= e(implode(' · ', array_map(fn($s) => $s[1], array_slice(jdec($k['specs']), 0, 2)))) ?></span><?= price_html($k['price'] ? (int)$k['price'] : null, 'шт') ?>
          <button class="btn btn-line btn-sm" type="button" data-lead="product" data-product="<?= e($k['name']) ?>">Заявка</button></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?= render('site/_cta', ['h' => 'Пришлите план участка: посчитаем точно', 'type' => 'paving']) ?>
