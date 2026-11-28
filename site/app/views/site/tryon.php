<?php $tv = fn($f) => asset('assets/tryon/' . $f); ?>
<section class="tryon" data-tryon>
  <div class="to-view" id="to-view">
    <div class="to-tools">
      <div class="seg to-mode"><button type="button" data-mode="3d" aria-pressed="true">3D</button><button type="button" data-mode="plan" aria-pressed="false">План</button></div>
      <div class="to-draw" data-plan-only hidden>
        <button type="button" class="tbtn" data-draw="pave" aria-pressed="false">+ Площадка</button>
        <button type="button" class="tbtn" data-draw="grid" aria-pressed="false">+ <span class="to-long">Газонная решётка</span><span class="to-short">Решётка</span></button>
      </div>
      <div class="to-r">
        <button type="button" class="tbtn ico" data-undo title="Отменить (Ctrl+Z)" aria-label="Отменить">↶</button>
        <button type="button" class="tbtn ico" data-redo title="Повторить (Ctrl+Y)" aria-label="Повторить">↷</button>
        <button type="button" class="tbtn" data-shot title="Сохранить картинку"><span class="to-long">Картинка</span><span class="to-short">Фото</span></button>
      </div>
      <div class="to-v" data-3d-only>
        <button type="button" class="tbtn" data-view title="Сменить вид">Общий вид</button>
        <button type="button" class="tbtn" data-light title="Освещение">Солнце</button>
      </div>
    </div>
    <div class="to-hint" data-hint hidden></div>
    <div class="to-swap" data-swap hidden>
      <button type="button" data-sw="-1" aria-label="Предыдущий цвет">‹</button>
      <span><small data-swt></small><b data-swn></b></span>
      <button type="button" data-sw="1" aria-label="Следующий цвет">›</button>
    </div>
    <div class="to-loader" data-loader><div><i></i>Строим участок… <span data-lp></span></div></div>
    <p class="to-disc"><?= e(setting('disclaimer')) ?></p>
  </div>

  <aside class="to-panel">
    <h1 class="to-h1">3D-примерочная брусчатки</h1>
    <div class="to-tabs" role="tablist">
      <button type="button" role="tab" data-tab="plot" aria-selected="true">Участок</button>
      <button type="button" role="tab" data-tab="house" aria-selected="false">Дом</button>
      <button type="button" role="tab" data-tab="fence" aria-selected="false">Забор</button>
      <button type="button" role="tab" data-tab="zone" aria-selected="false">Плитка</button>
      <button type="button" role="tab" data-tab="sum" aria-selected="false">Смета</button>
    </div>
    <div class="to-body" data-body></div>
    <div class="to-foot" data-foot></div>
  </aside>
</section>

<script type="importmap">
{"imports":{
  "three":"<?= e(url('vendor/three/three.module.js')) ?>",
  "three/addons/":"<?= e(url('vendor/three/addons/')) ?>",
  "tryon/paving":"<?= e($tv('paving.js')) ?>"
}}
</script>
<script type="application/json" id="tryon-data"><?= json_encode($data + ['worker' => $tv('worker.js'), 'paving' => $tv('paving.js')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<script type="module" src="<?= e($tv('app.js')) ?>"></script>
