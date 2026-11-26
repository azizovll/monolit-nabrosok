<form class="filters" method="get">
  <select name="cat" onchange="this.form.submit()">
    <option value="0">Все разделы</option>
    <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>"<?= $cat === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
  </select>
  <input type="search" name="s" value="<?= e($s) ?>" placeholder="Поиск: КС-10, люк, ФБС…">
  <button class="btn ghost">Найти</button>
  <a class="btn" href="<?= e(url('panel/products/new' . ($cat ? '?cat=' . $cat : ''))) ?>">+ Добавить товар</a>
</form>

<p class="muted small">Цены можно менять прямо в таблице и нажать «Сохранить цены». Пустая цена = «цена по запросу». Брусчатка на отдельной странице: <a href="<?= e(url('panel/paving')) ?>">Брусчатка</a>.</p>

<form method="post" action="<?= e(url('panel/products/prices')) ?>" data-dirty-guard>
  <?= csrf_field() ?>
  <input type="hidden" name="back" value="<?= e(http_build_query(['cat' => $cat ?: null, 's' => $s ?: null])) ?>">
  <div class="table-wrap">
  <table class="tbl">
    <thead><tr><th></th><th>Товар</th><th>Раздел</th><th class="num">Цена, ₽</th><th>Ед.</th><th></th></tr></thead>
    <tbody>
    <?php $prev = null; foreach ($items as $p): $imgs = jdec($p['images']); ?>
      <?php if ($prev !== $p['cname'] . $p['subgroup']): $prev = $p['cname'] . $p['subgroup']; ?>
        <tr class="grp"><td colspan="6"><?= e($p['cname']) ?><?= $p['subgroup'] ? ' · ' . e($p['subgroup']) : '' ?></td></tr>
      <?php endif; ?>
      <tr class="<?= $p['visible'] ? '' : 'hidden-row' ?>">
        <td class="thumb"><?= $imgs ? '<img src="' . e(url($imgs[0] . '-400.webp')) . '" alt="" loading="lazy">' : '<span class="noimg-s"></span>' ?></td>
        <td><a href="<?= e(url('panel/products/' . $p['id'])) ?>"><b><?= e($p['name']) ?></b></a>
          <?php if (!$p['visible']): ?><span class="tag">скрыт</span><?php endif; ?>
          <?php if (!$p['in_stock']): ?><span class="tag">нет в наличии</span><?php endif; ?>
          <?php if ($p['note']): ?><span class="tag warn" title="<?= e($p['note']) ?>">проверить</span><?php endif; ?>
        </td>
        <td class="muted small"><?= e($p['cname']) ?></td>
        <td class="num"><input class="price" name="price[<?= $p['id'] ?>]" value="<?= $p['price'] === null ? '' : e($p['price']) ?>" inputmode="numeric" placeholder="по запросу" aria-label="Цена <?= e($p['name']) ?>"></td>
        <td class="muted small"><?= e($p['price_unit']) ?></td>
        <td><a class="small" href="<?= e(url('panel/products/' . $p['id'])) ?>">изменить</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <div class="sticky-save"><button class="btn">Сохранить цены</button></div>
</form>
