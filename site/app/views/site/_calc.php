<?php
/* калькулятор брусчатки: всё выбирается касанием, данные из базы */
$icons = [
    'kirpich' => '<svg viewBox="0 0 44 28" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-width="1.6"><rect x="1" y="1" width="20" height="8" rx="1"/><rect x="23" y="1" width="20" height="8" rx="1"/><rect x="-9" y="10" width="20" height="8" rx="1"/><rect x="13" y="10" width="20" height="8" rx="1"/><rect x="35" y="10" width="20" height="8" rx="1"/><rect x="1" y="19" width="20" height="8" rx="1"/><rect x="23" y="19" width="20" height="8" rx="1"/></g></svg>',
    'novyj-gorod' => '<svg viewBox="0 0 44 28" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-width="1.6"><rect x="1" y="1" width="16" height="11" rx="1"/><rect x="19" y="1" width="11" height="11" rx="1"/><rect x="32" y="1" width="11" height="11" rx="1"/><rect x="1" y="14" width="8" height="13" rx="1"/><rect x="11" y="14" width="16" height="13" rx="1"/><rect x="29" y="14" width="14" height="13" rx="1"/></g></svg>',
    'staryj-gorod' => '<svg viewBox="0 0 44 28" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-width="1.6"><rect x="1" y="1" width="14" height="10" rx="3"/><rect x="17" y="1" width="10" height="10" rx="3"/><rect x="29" y="1" width="14" height="10" rx="3"/><rect x="1" y="13" width="9" height="14" rx="3"/><rect x="12" y="13" width="14" height="14" rx="3"/><rect x="28" y="13" width="15" height="14" rx="3"/></g></svg>',
    'parket' => '<svg viewBox="0 0 44 28" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-width="1.6"><rect x="1" y="1" width="6" height="16" rx="1"/><rect x="9" y="1" width="16" height="6" rx="1"/><rect x="9" y="9" width="6" height="16" rx="1"/><rect x="17" y="9" width="16" height="6" rx="1"/><rect x="27" y="1" width="16" height="6" rx="1"/><rect x="35" y="9" width="8" height="16" rx="1"/><rect x="17" y="17" width="16" height="6" rx="1"/></g></svg>',
];
?>
<div class="calc" data-calc>
  <div class="calc-l">
    <span class="eyebrow">Калькулятор</span>
    <h2>Сколько стоит плитка для вашего участка</h2>
    <p class="lead">Выберите форму, цвет и площадь: посчитаем количество с запасом на подрезку, поддоны и вес. Точный расчёт с доставкой пришлём за 15 минут.</p>
    <ul class="ticks"><li>Запас на подрезку уже учтён</li><li>Подскажем толщину под машину или дорожку</li><li>Посчитаем бордюры и доставку</li></ul>
    <p class="calc-call">Не хотите считать? <a href="tel:<?= e(setting('phone_tel')) ?>" data-goal="phone"><?= e(setting('phone')) ?></a></p>
  </div>
  <div class="calc-box">
    <div class="cb-step">
      <span class="cb-l">Форма</span>
      <div class="ftiles" data-c-forms>
        <?php foreach ($calc as $i => $f): $min = min(array_filter(array_merge(...array_map(fn($c) => array_column($c['v'], 'p'), $f['colors']))) ?: [0]); ?>
          <button type="button" class="ftile" data-fi="<?= $i ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>"><?= $icons[$f['slug']] ?? '' ?><span><?= e($f['name']) ?></span><small>от <?= number_format($min, 0, ',', ' ') ?> ₽</small></button>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="cb-step">
      <div class="cb-head"><span class="cb-l">Цвет</span><span class="cb-val" data-c-cname></span></div>
      <div class="dots" data-c-colors></div>
    </div>
    <div class="cb-row2">
      <div class="cb-step"><span class="cb-l">Толщина</span><div class="seg" data-c-th></div></div>
      <div class="cb-step"><span class="cb-l">Запас на подрезку</span>
        <div class="seg" data-c-reserve><button type="button" data-r="5" aria-pressed="false" title="Прямые дорожки">5%</button><button type="button" data-r="7" aria-pressed="true" title="Обычный двор">7%</button><button type="button" data-r="10" aria-pressed="false" title="Сложная форма, ёлочка">10%</button></div></div>
    </div>
    <div class="cb-step">
      <div class="cb-head"><span class="cb-l">Площадь</span><label class="area-in"><input type="number" min="1" max="2000" value="40" inputmode="decimal" data-c-area-num aria-label="Площадь, м²"><span>м²</span></label></div>
      <input type="range" min="5" max="400" step="1" value="40" data-c-area aria-label="Площадь, м²">
    </div>
    <div class="cb-sum">
      <div class="cb-meta"><b data-c-m2>0 м²</b> с запасом<br><b data-c-pal>0</b> · <b data-c-kg>0 т</b></div>
      <div class="cb-total"><small>Примерно, без доставки</small><b data-c-total>0 ₽</b></div>
    </div>
    <button class="btn btn-brand btn-wide" type="button" data-c-lead>Получить точный расчёт</button>
    <?= render('site/_disc', ['short' => true]) ?>
  </div>
  <script type="application/json" data-c-data><?= json_encode($calc, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
</div>
