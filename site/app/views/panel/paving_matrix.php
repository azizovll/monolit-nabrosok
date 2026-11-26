<div class="row">
  <div class="tabs">
    <?php foreach ($ths as $t): ?><a class="<?= $t === $th ? 'on' : '' ?>" href="<?= e(url('panel/paving?th=' . $t)) ?>"><?= $t ?> мм</a><?php endforeach; ?>
  </div>
  <form method="get" class="inline">
    <input name="add_th" type="number" min="40" max="120" step="10" placeholder="толщина, мм" aria-label="Добавить толщину">
    <button class="btn ghost small">+ толщина</button>
  </form>
  <a class="btn ghost small" href="<?= e(url('panel/paving/colors')) ?>">Цвета</a>
</div>

<p class="muted small">Цена за м², толщина <b><?= $th ?> мм</b>. Пустая ячейка = такого сочетания нет, на сайте оно не показывается. Галочка = есть в наличии. Цены сразу обновятся на сайте, в калькуляторе и в 3D.</p>

<form method="post" data-dirty-guard>
  <?= csrf_field() ?><input type="hidden" name="th" value="<?= $th ?>">
  <div class="table-wrap">
  <table class="tbl matrix">
    <thead><tr><th>Цвет</th>
      <?php foreach ($forms as $f): ?><th><a href="<?= e(url('panel/paving/form/' . $f['id'])) ?>"><?= e($f['name']) ?></a></th><?php endforeach; ?>
    </tr></thead>
    <tbody>
    <?php $grp = null; foreach ($colors as $c): ?>
      <?php if ($grp !== $c['grp']): $grp = $c['grp']; ?><tr class="grp"><td colspan="<?= count($forms) + 1 ?>"><?= $grp === 'mix' ? 'Колормикс' : 'Одноцветная' ?></td></tr><?php endif; ?>
      <tr>
        <th class="color"><i class="sw" style="background:<?= e(jdec($c['palette'])[0][0] ?? '#ccc') ?>"></i><?= e($c['name']) ?></th>
        <?php foreach ($forms as $f): $cell = $cells[$f['id']][$c['id']] ?? null; ?>
          <td class="cell<?= $cell ? '' : ' empty' ?>">
            <input class="price" name="price[<?= $f['id'] ?>][<?= $c['id'] ?>]" value="<?= $cell && $cell['price'] !== null ? e($cell['price']) : '' ?>" inputmode="numeric" placeholder="нет" aria-label="<?= e($f['name'] . ', ' . $c['name']) ?>">
            <label class="av" title="В наличии"><input type="checkbox" name="avail[<?= $f['id'] ?>][<?= $c['id'] ?>]" value="1"<?= !$cell || $cell['available'] ? ' checked' : '' ?>><span>есть</span></label>
          </td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <div class="sticky-save"><button class="btn">Сохранить цены</button></div>
</form>

<section class="card">
  <h2>Формы</h2>
  <div class="row">
    <?php foreach ($forms as $f): ?><a class="btn ghost" href="<?= e(url('panel/paving/form/' . $f['id'])) ?>"><?= e($f['name']) ?></a><?php endforeach; ?>
  </div>
  <p class="muted small">В форме: описание, характеристики, размеры камней для 3D, м² на поддоне, фото.</p>
</section>
