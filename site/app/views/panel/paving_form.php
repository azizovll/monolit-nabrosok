<?php
$specs = jdec($f['specs']);
$el = implode('; ', array_map(fn($x) => $x[0] . '×' . $x[1], jdec($f['elements'])));
$imgs = jdec($f['images']);
?>
<p class="small"><a href="<?= e(url('panel/paving')) ?>">← к ценам</a> · <a href="<?= e(url('bruschatka/' . $f['slug'] . '/')) ?>" target="_blank" rel="noopener">открыть на сайте ↗</a></p>

<form method="post" class="card form" data-dirty-guard>
  <?= csrf_field() ?><input type="hidden" name="act" value="save">
  <div class="grid3">
    <?= field('name', 'Название', $f['name'], ['required' => true]) ?>
    <?= field('m2_per_pallet', 'м² на поддоне', $f['m2_per_pallet'] ?? '', ['inputmode' => 'decimal']) ?>
    <?= field('pallet_kg', 'Вес поддона, кг', $f['pallet_kg'] ?? '', ['inputmode' => 'numeric']) ?>
  </div>
  <?= field('elements', 'Размеры камней в мм (для характеристик и 3D)', $el, ['hint' => 'Через точку с запятой: 240×160; 160×160; 80×160']) ?>
  <div class="row">
    <?= field('visible', 'Показывать на сайте', (int)$f['visible'], ['type' => 'checkbox']) ?>
    <?= field('sort', 'Порядок', $f['sort'], ['type' => 'number']) ?>
  </div>

  <h3>Характеристики</h3>
  <div class="specs" data-specs>
    <?php foreach (array_merge($specs, [['', '']]) as [$k, $v]): ?>
      <div class="spec-row"><input name="spec_k[]" value="<?= e($k) ?>" placeholder="Название"><input name="spec_v[]" value="<?= e($v) ?>" placeholder="Значение"><button type="button" class="x" data-del-row aria-label="Удалить строку">×</button></div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn ghost small" data-add-row>+ строка</button>

  <h3>Тексты</h3>
  <?= field('short', 'Коротко (под названием)', $f['short'], ['type' => 'textarea', 'rows' => 2]) ?>
  <?= field('description', 'Описание', $f['description'], ['type' => 'textarea', 'rows' => 5]) ?>
  <?= field('seo_text', 'Текст для поиска (внизу страницы)', $f['seo_text'], ['type' => 'textarea', 'rows' => 6]) ?>
  <details class="seo"><summary>SEO: заголовок и описание для Яндекса</summary>
    <?= field('seo_title', 'Title', $f['seo_title']) ?>
    <?= field('seo_description', 'Description', $f['seo_description'], ['type' => 'textarea', 'rows' => 2]) ?>
  </details>
  <button class="btn">Сохранить</button>
</form>

<section class="card">
  <h2>Фото формы</h2>
  <div class="photos">
    <?php foreach ($imgs as $b): ?>
      <figure><img src="<?= e(url($b . '-400.webp')) ?>" alt="">
        <figcaption><form method="post" data-confirm="Удалить фото?"><?= csrf_field() ?><input type="hidden" name="act" value="delete_img"><input type="hidden" name="base" value="<?= e($b) ?>"><button class="link small danger">удалить</button></form></figcaption></figure>
    <?php endforeach; ?>
  </div>
  <form method="post" enctype="multipart/form-data" class="upload"><?= csrf_field() ?><input type="hidden" name="act" value="upload">
    <input type="file" name="files[]" accept="image/jpeg,image/png,image/webp" multiple required><button class="btn ghost">Загрузить</button></form>
</section>
