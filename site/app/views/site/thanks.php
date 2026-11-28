<section class="sec page-top">
  <div class="wrap narrow-text thanks">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#1f8a4c" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg>
    <h1>Заявка отправлена</h1>
    <p class="lead">Перезвоним в течение 15 минут в рабочее время (<?= e(setting('hours')) ?>). Если срочно, звоните: <a href="tel:<?= e(setting('phone_tel')) ?>" data-goal="phone"><?= e(setting('phone')) ?></a>.</p>
    <div class="lead-what" data-thanks-what hidden></div>
    <h2>Чтобы расчёт был точнее, подготовьте</h2>
    <ul class="ticks"><li>площадь или размеры участка, лучше с планом;</li><li>адрес доставки: от него зависит цена перевозки;</li><li>удобный день для доставки.</li></ul>
    <?= render('site/_disc') ?>
    <div class="cta-row"><a class="btn btn-line" href="<?= e(url('3d/')) ?>">Пока ждёте: примерьте плитку в 3D или на фото двора</a><a class="btn btn-line" href="<?= e(url('/')) ?>">На главную</a></div>
  </div>
</section>
