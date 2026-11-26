<p class="small"><a href="<?= e(url('panel/paving')) ?>">← к ценам</a></p>
<p class="muted small">Палитра задаёт цвет образца на сайте и камней в 3D-примерочной: оттенки и их доля. Формат: <code>#c98a5e 3, #dda670 2</code>. Подбираем по фото образцов.</p>

<form method="post" class="card" data-dirty-guard>
  <?= csrf_field() ?>
  <div class="table-wrap">
  <table class="tbl">
    <thead><tr><th>Образец</th><th>Название</th><th>Группа</th><th>Палитра</th><th>Порядок</th></tr></thead>
    <tbody>
    <?php foreach ($colors as $c): $pal = jdec($c['palette']); ?>
      <tr>
        <td><span class="pal"><?php foreach ($pal as [$hex, $w]): ?><i style="background:<?= e($hex) ?>;flex:<?= (int)$w ?>"></i><?php endforeach; ?></span></td>
        <td><input name="name[<?= $c['id'] ?>]" value="<?= e($c['name']) ?>"></td>
        <td><select name="grp[<?= $c['id'] ?>]"><option value="mono"<?= $c['grp'] === 'mono' ? ' selected' : '' ?>>Одноцветная</option><option value="mix"<?= $c['grp'] === 'mix' ? ' selected' : '' ?>>Колормикс</option></select></td>
        <td><input class="wide" name="palette[<?= $c['id'] ?>]" value="<?= e(palette_str($c['palette'])) ?>"></td>
        <td><input class="num-s" type="number" name="sort[<?= $c['id'] ?>]" value="<?= (int)$c['sort'] ?>"></td>
      </tr>
    <?php endforeach; ?>
      <tr class="grp"><td colspan="5">Новый цвет</td></tr>
      <tr>
        <td></td>
        <td><input name="new_name" placeholder="Название"></td>
        <td><select name="new_grp"><option value="mono">Одноцветная</option><option value="mix">Колормикс</option></select></td>
        <td><input class="wide" name="new_palette" placeholder="#aaaaaa 3, #bbbbbb 2"></td>
        <td></td>
      </tr>
    </tbody>
  </table>
  </div>
  <button class="btn">Сохранить</button>
</form>
