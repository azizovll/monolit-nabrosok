<?php
$catOpts = ['' => 'Выберите раздел'];
foreach ($cats as $c) $catOpts[$c['id']] = $c['name'];
$specs = jdec($p['specs']);
$imgs = jdec($p['images']);
$cs = null; foreach ($cats as $c) if ((int)$c['id'] === (int)$p['category_id']) $cs = $c;
$pub = $p['id'] && $cs ? ($cs['kind'] === 'lawn' ? 'gazonnaya-reshetka/' : 'zhbi/' . $cs['slug'] . '/' . $p['slug'] . '/') : null;
?>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<?php if (!empty($p['note'])): ?><div class="flash warn"><b>Проверить:</b> <?= e($p['note']) ?></div><?php endif; ?>
<?php if ($pub): ?><p class="small"><a href="<?= e(url($pub)) ?>" target="_blank" rel="noopener">Открыть на сайте ↗</a></p><?php endif; ?>

<form method="post" class="card form" data-dirty-guard>
  <?= csrf_field() ?>
  <div class="grid2">
    <?= field('name', 'Название', $p['name'], ['required' => true]) ?>
    <?= field('category_id', 'Раздел', $p['category_id'], ['type' => 'select', 'options' => $catOpts, 'required' => true]) ?>
    <?= field('subgroup', 'Подгруппа', $p['subgroup'], ['hint' => 'Например «Сквозные», «Чугунные». Варианты: ' . e(implode(', ', $subgroups))]) ?>
    <?= field('slug', 'Адрес страницы (латиницей)', $p['slug'], ['hint' => 'Если пусто, соберётся из названия. Меняйте осторожно: старый адрес перестанет открываться']) ?>
  </div>
  <div class="grid3">
    <?= field('price', 'Цена, ₽', $p['price'] ?? '', ['inputmode' => 'numeric', 'placeholder' => 'по запросу', 'hint' => 'Пусто = «цена по запросу»']) ?>
    <?= field('price_unit', 'За что цена', $p['price_unit'], ['hint' => 'шт, м², п.м.']) ?>
    <?= field('sort', 'Порядок в списке', $p['sort'], ['type' => 'number']) ?>
  </div>
  <div class="row">
    <?= field('in_stock', 'В наличии', (int)$p['in_stock'], ['type' => 'checkbox']) ?>
    <?= field('visible', 'Показывать на сайте', (int)$p['visible'], ['type' => 'checkbox']) ?>
  </div>

  <h3>Характеристики</h3>
  <div class="specs" data-specs>
    <?php foreach (array_merge($specs, [['', '']]) as [$k, $v]): ?>
      <div class="spec-row">
        <input name="spec_k[]" value="<?= e($k) ?>" placeholder="Название, например «Вес»" list="spec-names">
        <input name="spec_v[]" value="<?= e($v) ?>" placeholder="Значение, например «600 кг»">
        <button type="button" class="x" data-del-row aria-label="Удалить строку">×</button>
      </div>
    <?php endforeach; ?>
  </div>
  <datalist id="spec-names"><?php foreach (['Внутренний диаметр', 'Наружный диаметр', 'Высота', 'Толщина стенки', 'Вес', 'Размеры', 'Класс нагрузки', 'Бетон', 'Стандарт', 'Штук на поддоне'] as $n): ?><option value="<?= e($n) ?>"><?php endforeach; ?></datalist>
  <button type="button" class="btn ghost small" data-add-row>+ строка</button>

  <h3>Тексты</h3>
  <?= field('short', 'Коротко (под названием)', $p['short'], ['type' => 'textarea', 'rows' => 2]) ?>
  <?= field('description', 'Описание', $p['description'], ['type' => 'textarea', 'rows' => 5]) ?>

  <details class="seo"><summary>SEO: заголовок и описание для Яндекса</summary>
    <?= field('seo_title', 'Title', $p['seo_title'], ['hint' => 'Если пусто, соберётся сам: «Название купить в Пскове, цена · Монолит-Вест»']) ?>
    <?= field('seo_description', 'Description', $p['seo_description'], ['type' => 'textarea', 'rows' => 2]) ?>
  </details>

  <?= field('note', 'Заметка для себя (на сайте не видна)', $p['note'], ['type' => 'textarea', 'rows' => 2]) ?>

  <div class="row between">
    <button class="btn">Сохранить</button>
    <a class="small" href="<?= e(url('panel/products' . ($p['category_id'] ? '?cat=' . (int)$p['category_id'] : ''))) ?>">← к списку</a>
  </div>
</form>

<?php if ($p['id']): ?>
<section class="card" id="photos">
  <h2>Фото</h2>
  <div class="photos">
    <?php foreach ($imgs as $i => $b): ?>
      <figure>
        <img src="<?= e(url($b . '-400.webp')) ?>" alt="">
        <figcaption>
          <?php if ($i === 0): ?><span class="tag">главное</span><?php else: ?>
          <form method="post" action="<?= e(url('panel/products/' . $p['id'] . '/image')) ?>"><?= csrf_field() ?><input type="hidden" name="act" value="first"><input type="hidden" name="base" value="<?= e($b) ?>"><button class="link small">сделать главным</button></form>
          <?php endif; ?>
          <form method="post" action="<?= e(url('panel/products/' . $p['id'] . '/image')) ?>" data-confirm="Удалить это фото?"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="base" value="<?= e($b) ?>"><button class="link small danger">удалить</button></form>
        </figcaption>
      </figure>
    <?php endforeach; ?>
    <?php if (!$imgs): ?><p class="muted">Фото нет.</p><?php endif; ?>
  </div>
  <form method="post" enctype="multipart/form-data" action="<?= e(url('panel/products/' . $p['id'] . '/image')) ?>" class="upload">
    <?= csrf_field() ?><input type="hidden" name="act" value="upload">
    <input type="file" name="files[]" accept="image/jpeg,image/png,image/webp" multiple required>
    <button class="btn ghost">Загрузить</button>
    <small class="hint">JPG, PNG или WebP до 25 МБ. Сжимаются автоматически, можно грузить прямо с телефона.</small>
  </form>
</section>

<section class="card danger-zone">
  <form method="post" action="<?= e(url('panel/products/' . $p['id'] . '/delete')) ?>" data-confirm="Удалить товар «<?= e($p['name']) ?>»? Это нельзя отменить. Если нужно временно убрать, снимите галочку «Показывать на сайте».">
    <?= csrf_field() ?><button class="btn danger">Удалить товар</button>
  </form>
</section>
<?php endif; ?>
