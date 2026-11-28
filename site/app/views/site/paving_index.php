<section class="sec page-top">
  <div class="wrap">
    <span class="eyebrow">Брусчатка от производителя</span>
    <h1>Брусчатка в Пскове: 4 формы, <?= count($colors) ?> цветов, от <?= number_format($min, 0, ',', ' ') ?> ₽/м²</h1>
    <p class="lead">Своё производство на Карбышева, 26а. Бетон В25, морозостойкость F₂200, ГОСТ 17608-2017. Привезём на объект и выгрузим манипулятором.</p>
    <div class="cta-row">
      <button class="btn btn-brand" type="button" data-lead="paving" data-title="Подобрать плитку">Подобрать плитку</button>
      <a class="btn btn-line" href="<?= e(url('3d/')) ?>">Примерить в 3D</a>
    </div>
  </div>
</section>

<section class="sec pt0">
  <div class="wrap">
    <div class="forms-list">
      <?php foreach ($forms as $f): $img = jdec($f['images'])[0] ?? ''; $fc = form_colors((int)$f['id']); ?>
        <article class="form-row">
          <a class="pic" href="<?= e(url($f['url'])) ?>"><?= picture($img, 'Брусчатка ' . $f['name'], '(max-width: 700px) 100vw, 420px') ?></a>
          <div class="body">
            <h2><a href="<?= e(url($f['url'])) ?>">«<?= e($f['name']) ?>»</a></h2>
            <p><?= e($f['short']) ?></p>
            <p class="meta">Элементы <?= e(implode(', ', array_map(fn($x) => $x[0] . '×' . $x[1], jdec($f['elements'])))) ?> мм · <?= e(str_replace('.', ',', (string)$f['m2_per_pallet'])) ?> м² на поддоне</p>
            <div class="sw-mini" aria-label="Цвета">
              <?php foreach ($fc as $c): ?><a href="<?= e(url($f['url'] . '?cvet=' . $c['slug'])) ?>" title="<?= e($c['name']) ?>" style="background:<?= e(swatch_css($c['palette'])) ?>"></a><?php endforeach; ?>
            </div>
            <div class="foot"><span class="price"><small>от</small> <?= number_format($f['min_price'], 0, ',', ' ') ?> ₽<small>/м²</small></span>
              <a class="btn btn-brand btn-sm" href="<?= e(url($f['url'])) ?>">Выбрать цвет</a></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?= render('site/_disc') ?>
  </div>
</section>

<section class="sec">
  <div class="wrap">
    <span class="eyebrow">Цены</span>
    <h2>Цены за м² по формам и цветам</h2>
    <p class="lead"><?= e(price_date_text()) ?>. Пустая ячейка: такого сочетания нет.</p>
    <div class="tbl-wrap">
      <table class="tbl">
        <thead><tr><th>Цвет</th><?php foreach ($forms as $f): ?><th><a href="<?= e(url($f['url'])) ?>"><?= e($f['name']) ?></a></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php foreach ($colors as $c): ?>
          <tr><th><i class="dot" style="background:<?= e(swatch_css(jdec($c['palette']))) ?>"></i><?= e($c['name']) ?></th>
            <?php foreach ($forms as $f): $pr = rows('SELECT thickness, price FROM paving_prices WHERE form_id = ? AND color_id = ? ORDER BY thickness', [$f['id'], $c['id']]); ?>
              <td><?= $pr ? implode('<br>', array_map(fn($x) => $x['price'] ? '<a href="' . e(url($f['url'] . '?cvet=' . $c['slug'])) . '">' . number_format((int)$x['price'], 0, ',', ' ') . ' ₽</a>' . (count($pr) > 1 ? ' <small>' . $x['thickness'] . ' мм</small>' : '') : 'по запросу', $pr)) : '<span class="muted">—</span>' ?></td>
            <?php endforeach; ?></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?= render('site/_faq', ['faq' => $faq]) ?>
<?= render('site/_cta', ['h' => 'Посчитаем плитку под ваш участок', 'type' => 'paving']) ?>
