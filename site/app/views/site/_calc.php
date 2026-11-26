<?php /* калькулятор брусчатки: площадь → м² с запасом, поддоны, вес, сумма; данные из базы */ ?>
<div class="calc" data-calc>
  <div class="calc-l">
    <span class="eyebrow">Калькулятор</span>
    <h2>Сколько стоит плитка для вашего участка</h2>
    <p class="lead">Укажите форму, цвет и площадь: посчитаем количество с запасом на подрезку, число поддонов и вес. Точный расчёт с доставкой пришлём за 15 минут.</p>
    <ul class="ticks"><li>Запас на подрезку уже учтён</li><li>Подскажем толщину под машину или дорожку</li><li>Посчитаем бордюры и доставку</li></ul>
  </div>
  <div class="calc-box">
    <label class="f"><span>Форма плитки</span><select data-c-form><?php foreach ($calc as $i => $f): ?><option value="<?= $i ?>"><?= e($f['name']) ?></option><?php endforeach; ?></select></label>
    <div class="f"><span>Цвет</span><div class="sw-row" data-c-colors></div></div>
    <div class="f" data-c-th-wrap hidden><span>Толщина</span><div class="seg" data-c-th></div></div>
    <div class="f"><div class="range-row"><span>Площадь</span><output data-c-area-out>40 м²</output></div>
      <input type="range" min="5" max="400" step="1" value="40" data-c-area aria-label="Площадь, м²"></div>
    <label class="f"><span>Запас на подрезку</span><select data-c-reserve><option value="5">5%: прямые дорожки</option><option value="7" selected>7%: обычный двор</option><option value="10">10%: сложная форма, ёлочка</option></select></label>
    <div class="res">
      <div><span>С запасом</span><b data-c-m2>0 м²</b></div>
      <div><span>Поддонов</span><b data-c-pal>0</b></div>
      <div><span>Вес</span><b data-c-kg>0 т</b></div>
    </div>
    <div class="total"><span>Примерно, без доставки</span><b data-c-total>0 ₽</b></div>
    <button class="btn btn-brand btn-wide" type="button" data-c-lead>Получить точный расчёт</button>
    <?= render('site/_disc') ?>
  </div>
  <script type="application/json" data-c-data><?= json_encode($calc, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
</div>
