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
        <button type="button" class="tbtn" data-photo aria-pressed="false" title="Примерить плитку на фото своего двора"><span class="to-long">На своём фото</span><span class="to-short">Своё фото</span></button>
        <button type="button" class="tbtn" data-shot title="Сохранить картинку"><span class="to-long">Картинка</span><span class="to-short">Скачать</span></button>
      </div>
      <div class="to-v" data-3d-only>
        <button type="button" class="tbtn" data-view title="Сменить вид">Общий вид</button>
        <button type="button" class="tbtn" data-light title="Освещение">Солнце</button>
      </div>
    </div>
    <div class="to-photo" data-photo-ui hidden>
      <canvas data-pc></canvas>
      <svg class="to-ph-svg" data-ph-svg></svg>
      <div class="to-ph-h" data-ph="0" title="Ближний левый угол"></div><div class="to-ph-h" data-ph="1" title="Ближний правый угол"></div>
      <div class="to-ph-h" data-ph="2" title="Дальний правый угол"></div><div class="to-ph-h" data-ph="3" title="Дальний левый угол"></div>
      <div class="to-ph-cur" data-ph-cur hidden></div>
      <canvas class="to-ph-loupe" data-ph-loupe width="240" height="240" hidden></canvas>
      <div class="to-ph-empty" data-ph-empty>
        <div>
          <b>Примерьте плитку на фото своего двора</b>
          <p>Сфотографируйте место, где будет дорожка или площадка, лучше с высоты роста и при дневном свете. Потом отметьте 4 угла, и плитка ляжет в перспективе.</p>
          <label class="btn btn-brand">Выбрать или снять фото<input type="file" accept="image/*" data-ph-file hidden></label>
          <small>Фото не загружается на сервер. Менеджер увидит его, только если вы отправите проект.</small>
        </div>
      </div>
      <div class="to-ph-bar" data-ph-bar hidden>
        <div class="to-ph-row">
          <span class="to-ph-q" data-ph-quads></span>
          <label><span class="to-long">Ширина</span><span class="to-short">Ш</span><input type="number" min="0.5" max="60" step="0.1" value="3" data-ph-w>м</label>
          <label><span class="to-long">Глубина</span><span class="to-short">Г</span><input type="number" min="0.5" max="60" step="0.1" value="4" data-ph-d>м</label>
          <label class="to-ph-chk"><input type="checkbox" data-ph-kerb checked>Бордюр</label>
        </div>
        <div class="to-ph-row">
          <span class="seg to-ph-seg"><button type="button" data-ph-brush="erase" aria-pressed="false" title="Закрасьте то, что стоит поверх плитки: машину, людей, кусты">Стереть</button><button type="button" data-ph-brush="restore" aria-pressed="false">Вернуть</button></span>
          <label class="to-ph-rng" data-ph-bsw hidden>Кисть<input type="range" min="0.01" max="0.12" step="0.005" value="0.04" data-ph-bs></label>
          <label class="to-ph-rng">Яркость<input type="range" min="0.5" max="1.6" step="0.05" value="1" data-ph-g></label>
          <button type="button" class="tbtn" data-ph-orig title="Удерживайте, чтобы увидеть исходное фото">Было</button>
          <label class="tbtn">Другое фото<input type="file" accept="image/*" data-ph-file hidden></label>
        </div>
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
