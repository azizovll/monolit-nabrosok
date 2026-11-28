<?php
$diams = [];
foreach ($items as $p) if (preg_match('~^(\d+)~', spec($p, 'Внутренний диаметр'), $m)) $diams[(int)$m[1]] = true;
ksort($diams);
?>
<section class="sec page-top">
  <div class="wrap">
    <h1><?= e($c['h1'] ?: $c['name']) ?></h1>
    <p class="lead"><?= e($c['intro']) ?></p>
    <div class="cat-tabs"><?php foreach ($cats as $x): ?><a href="<?= e(url($x['url'])) ?>"<?= $x['id'] === $c['id'] ? ' aria-current="page"' : '' ?>><?= e($x['name']) ?></a><?php endforeach; ?></div>
  </div>
</section>

<section class="sec pt0">
  <div class="wrap">
    <?php if ($c['slug'] === 'kolca'): ?>
      <div class="pick-hint">
        <div><b>Колодец для воды</b><span>кольца КС-10 или КС-15, внизу плита днища, сверху плита перекрытия и люк</span></div>
        <div><b>Септик</b><span>кольца КС-15 или КС-20, нижнее с днищем (КСД), плита и люк</span></div>
        <div><b>Дренаж</b><span>дренажные кольца с отверстиями, у них в названии буква «Д»</span></div>
      </div>
    <?php endif; ?>
    <?php if (count($diams) > 1): ?>
      <div class="filter" data-filter><span>Диаметр:</span><button type="button" aria-pressed="true" data-d="">все</button><?php foreach (array_keys($diams) as $d): ?><button type="button" aria-pressed="false" data-d="<?= $d ?>"><?= $d ?> см</button><?php endforeach; ?></div>
    <?php endif; ?>
    <?php foreach ($groups as $g => $list): ?>
      <?php if ($g !== '' && count($groups) > 1): ?><h2 class="h3"><?= e($g) ?></h2><?php endif; ?>
      <div class="ptable">
        <?php foreach ($list as $p): $d = preg_match('~^(\d+)~', spec($p, 'Внутренний диаметр'), $m) ? $m[1] : ''; ?>
          <div class="prow" data-diam="<?= e($d) ?>">
            <a class="pthumb" href="<?= e(url($p['url'])) ?>"><?= picture($p['images_a'][0] ?? null, $p['name'], '80px') ?></a>
            <a class="pname" href="<?= e(url($p['url'])) ?>"><?= e($p['name']) ?></a>
            <span class="pspec"><?= e(spec_line($p)) ?></span>
            <?= price_html($p['price'] ? (int)$p['price'] : null, $p['price_unit']) ?>
            <button class="btn btn-line btn-sm" type="button" data-lead="product" data-product="<?= e($p['name']) ?>" data-qty="1"><?= $p['price'] ? 'Заявка' : 'Узнать цену' ?></button>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    <p class="muted small"><?= e(price_date_text()) ?>. Доставка по Пскову и области, разгрузка манипулятором.</p>
  </div>
</section>

<?php if (trim($c['seo_text'])): ?>
<section class="sec"><div class="wrap text narrow-text"><?= md($c['seo_text']) ?></div></section>
<?php endif; ?>
<?= render('site/_faq', ['faq' => $faq]) ?>
<?= render('site/_cta', ['h' => 'Посчитаем ' . mb_strtolower($c['name']) . ' с доставкой', 'type' => 'product']) ?>
