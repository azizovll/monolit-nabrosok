<?php
$c0 = $colors[0];
$v0 = $c0['variants'][0];
$img0 = $v0['image'] ?: (jdec($f['images'])[0] ?? '');
$data = ['form' => $f['name'], 'slug' => $f['slug'], 'm2' => (float)$f['m2_per_pallet'], 'kg' => (int)$f['pallet_kg'], 'colors' => array_map(fn($c) => [
    'slug' => $c['slug'], 'name' => $c['name'], 'grp' => $c['grp'], 'sw' => swatch_css($c['palette']),
    'v' => array_map(fn($v) => ['t' => $v['thickness'], 'p' => $v['price'], 'a' => $v['available'], 'f' => $v['forming'], 'img' => $v['image'] ? img_src_pair($v['image']) : null], $c['variants'])], $colors)];
$specs = array_merge([['Размеры элементов', implode(', ', array_map(fn($x) => $x[0] . '×' . $x[1], $el)) . ' мм'], ['Толщина', implode(', ', array_unique(array_merge(...array_map(fn($c) => array_column($c['variants'], 'thickness'), $colors)))) . ' мм'],
    ['На поддоне', str_replace('.', ',', (string)$f['m2_per_pallet']) . ' м²'], ['Вес поддона', 'около ' . number_format((int)$f['pallet_kg'], 0, ',', ' ') . ' кг']], jdec($f['specs']));
$groups = ['mono' => 'Одноцветная', 'mix' => 'Колормикс'];
?>
<section class="sec page-top pf" data-pf>
  <div class="wrap pf-in">
    <div class="pf-gal">
      <div class="pf-img" data-pf-img><?= picture($img0, 'Брусчатка ' . $f['name'] . ' ' . $c0['name'], '(max-width: 900px) 100vw, 560px', ['eager' => true]) ?></div>
      <p class="muted small">Фото образца.<?= setting('disclaimer_pages', '1') === '1' ? ' ' . e(setting('disclaimer_short')) : '' ?></p>
    </div>
    <div class="pf-info">
      <h1>Брусчатка «<?= e($f['name']) ?>»</h1>
      <p class="lead"><?= e($f['short']) ?></p>
      <div class="pf-price"><b data-pf-price><?= $v0['price'] ? number_format($v0['price'], 0, ',', ' ') . ' ₽' : 'по запросу' ?></b><span>/м²</span><span class="tag" data-pf-name><?= e($c0['name']) ?></span><span class="muted small"><?= e(price_date_text()) ?></span></div>

      <div class="f">
        <div class="cb-head"><span class="cb-l">Цвет</span><span class="cb-val" data-pf-cval></span></div>
        <div class="dots dots-lg">
          <?php $prevG = null; foreach ($colors as $c): ?>
            <?php if ($prevG !== null && $prevG !== $c['grp']): ?><span class="sep" aria-hidden="true"></span><?php endif; $prevG = $c['grp']; ?>
            <button type="button" class="dot-b" data-color="<?= e($c['slug']) ?>" aria-pressed="<?= $c['slug'] === $c0['slug'] ? 'true' : 'false' ?>" title="<?= e($c['name']) ?>" aria-label="<?= e($c['name']) ?>" style="background:<?= e(swatch_css($c['palette'])) ?>"></button>
          <?php endforeach; ?>
        </div>
        <p class="dots-legend muted small">Слева одноцветная, справа колормикс: камни разного тона, как натуральный камень.</p>
      </div>
      <div class="f" data-pf-th-wrap hidden><span>Толщина</span><div class="seg" data-pf-th></div></div>

      <div class="mini-calc">
        <label class="f"><span>Площадь, м²</span><input type="number" min="1" max="5000" value="40" inputmode="decimal" data-pf-area></label>
        <div class="res"><div><span>С запасом 7%</span><b data-pf-m2></b></div><div><span>Поддонов</span><b data-pf-pal></b></div><div><span>Примерно</span><b data-pf-total></b></div></div>
      </div>

      <div class="cta-row">
        <button class="btn btn-brand" type="button" data-pf-lead>Оставить заявку</button>
        <a class="btn btn-line" data-pf-3d href="<?= e(url('3d/?forma=' . $f['slug'] . '&cvet=' . $c0['slug'])) ?>">Примерить в 3D</a>
      </div>
      <?= render('site/_disc') ?>
      <ul class="ticks small"><li>Привезём и выгрузим манипулятором</li><li>Образцы всех цветов на площадке</li><li>Посчитаем бордюр и запас бесплатно</li></ul>
    </div>
  </div>
  <script type="application/json" data-pf-data><?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) ?></script>
</section>

<section class="sec">
  <div class="wrap grid2">
    <div>
      <h2>Характеристики</h2>
      <dl class="specs"><?php foreach ($specs as [$k, $v]): ?><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd><?php endforeach; ?></dl>
    </div>
    <div>
      <h2>Где применяется</h2>
      <div class="text"><?= md($f['description']) ?></div>
      <ul class="ticks"><li>Дорожки и отмостка: 60 мм</li><li>Двор и площадка: 60 мм</li><li>Въезд и парковка легковых машин: 60 мм на подготовленном основании<?= in_array(80, array_merge(...array_map(fn($c) => array_column($c['variants'], 'thickness'), $colors)), true) ? ', для большей нагрузки 80 мм' : '' ?></li></ul>
    </div>
  </div>
</section>

<?php if (trim($f['seo_text'])): ?>
<section class="sec pt0"><div class="wrap text narrow-text"><?= md($f['seo_text']) ?></div></section>
<?php endif; ?>

<section class="sec">
  <div class="wrap">
    <h2>Другие формы</h2>
    <div class="cards4 cards3">
      <?php foreach ($others as $o): ?>
        <a class="card-p card-pave" href="<?= e(url($o['url'])) ?>"><div class="pic"><?= picture(jdec($o['images'])[0] ?? '', 'Брусчатка ' . $o['name'], '(max-width: 700px) 50vw, 300px') ?></div>
          <div class="body"><h3>«<?= e($o['name']) ?>»</h3><div class="foot"><span class="price"><small>от</small> <?= number_format($o['min_price'], 0, ',', ' ') ?> ₽<small>/м²</small></span></div></div></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?= render('site/_faq', ['faq' => $faq]) ?>
<?= render('site/_cta', ['h' => 'Посчитаем «' . $f['name'] . '» под ваш участок', 'type' => 'paving']) ?>
