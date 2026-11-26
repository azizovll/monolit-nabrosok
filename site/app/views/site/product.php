<section class="sec page-top">
  <div class="wrap prod">
    <div class="prod-gal">
      <div class="prod-img"><?= picture($p['images_a'][0] ?? null, $p['name'], '(max-width: 900px) 100vw, 520px', ['eager' => true]) ?></div>
      <?php if (count($p['images_a']) > 1): ?><div class="prod-thumbs"><?php foreach ($p['images_a'] as $b): ?><?= picture($b, $p['name'], '96px') ?><?php endforeach; ?></div><?php endif; ?>
    </div>
    <div class="prod-info">
      <?php if ($p['subgroup']): ?><span class="eyebrow"><?= e($c['name']) ?> · <?= e($p['subgroup']) ?></span><?php endif; ?>
      <h1><?= e($p['name']) ?></h1>
      <?php if ($p['short']): ?><p class="lead"><?= e($p['short']) ?></p><?php endif; ?>
      <div class="pf-price"><?= $p['price'] ? '<b>' . number_format((int)$p['price'], 0, ',', ' ') . ' ₽</b><span>/' . e($p['price_unit']) . '</span>' : '<b>Цена по запросу</b>' ?>
        <span class="tag <?= $p['in_stock'] ? 'ok' : '' ?>"><?= $p['in_stock'] ? 'в наличии' : 'под заказ' ?></span></div>
      <?php if ($p['specs_a']): ?>
        <dl class="specs"><?php foreach ($p['specs_a'] as [$k, $v]): ?><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd><?php endforeach; ?></dl>
      <?php endif; ?>
      <div class="cta-row">
        <button class="btn btn-brand" type="button" data-lead="product" data-product="<?= e($p['name']) ?>" data-qty="1"><?= $p['price'] ? 'Оставить заявку' : 'Узнать цену' ?></button>
        <a class="btn btn-line" href="tel:<?= e(setting('phone_tel')) ?>" data-goal="phone">Позвонить</a>
      </div>
      <ul class="ticks small"><li>Доставка по Пскову и области, выгрузка манипулятором</li><li>Можно забрать самому с площадки на Карбышева, 26а</li><li><?= e(price_date_text()) ?></li></ul>
    </div>
  </div>
</section>

<?php if ($p['description']): ?><section class="sec pt0"><div class="wrap text narrow-text"><h2>Описание</h2><?= md($p['description']) ?></div></section><?php endif; ?>

<?php if ($related): ?>
<section class="sec">
  <div class="wrap">
    <h2>С этим берут, чтобы собрать колодец</h2>
    <div class="ptable">
      <?php foreach ($related as $r): ?>
        <div class="prow"><span class="tag"><?= e($r['why']) ?></span><a class="pname" href="<?= e(url($r['url'])) ?>"><?= e($r['name']) ?></a>
          <?= price_html($r['price'] ? (int)$r['price'] : null, $r['price_unit']) ?>
          <button class="btn btn-line btn-sm" type="button" data-lead="product" data-product="<?= e($p['name'] . ' + ' . $r['name']) ?>">Заявка на комплект</button></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($siblings): ?>
<section class="sec">
  <div class="wrap">
    <h2>Похожие</h2>
    <div class="ptable">
      <?php foreach ($siblings as $s): ?>
        <div class="prow"><a class="pname" href="<?= e(url($s['url'])) ?>"><?= e($s['name']) ?></a><span class="pspec"><?= e(spec_line($s)) ?></span><?= price_html($s['price'] ? (int)$s['price'] : null, $s['price_unit']) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?= render('site/_cta', ['h' => 'Посчитаем с доставкой и монтажом', 'type' => 'product']) ?>
