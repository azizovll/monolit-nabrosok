<?php [$lat, $lng] = array_map('trim', explode(',', setting('geo', '57.8136,28.3496')) + [1 => '']); ?>
<section class="sec page-top">
  <div class="wrap contacts-page">
    <div>
      <h1><?= e($title) ?></h1>
      <dl class="specs big">
        <dt>Телефон</dt><dd><a href="tel:<?= e(setting('phone_tel')) ?>" data-goal="phone"><?= e(setting('phone')) ?></a></dd>
        <?php if (setting('max_url')): ?><dt>MAX</dt><dd><a href="<?= e(setting('max_url')) ?>" target="_blank" rel="noopener" data-goal="max">Написать в MAX</a></dd><?php endif; ?>
        <dt>Почта</dt><dd><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></dd>
        <dt>Адрес</dt><dd><?= e(setting('address')) ?><br><small class="muted">Офис и площадка с образцами</small></dd>
        <dt>Часы</dt><dd><?= e(setting('hours')) ?></dd>
        <?php if (setting('inn')): ?><dt>Реквизиты</dt><dd><?= e(setting('company')) ?>, ИНН <?= e(setting('inn')) ?><?= setting('ogrn') ? ', ОГРН ' . e(setting('ogrn')) : '' ?></dd><?php endif; ?>
      </dl>
      <?= md($pg['body']) ?>
      <div class="cta-row"><button class="btn btn-brand" type="button" data-lead="contact" data-title="Написать нам" data-sub="Ответим в рабочее время. Можно задать вопрос или попросить расчёт.">Написать нам</button>
        <a class="btn btn-line" href="https://yandex.ru/maps/?rtext=~<?= e($lat . ',' . $lng) ?>&rtt=auto" target="_blank" rel="noopener">Построить маршрут</a></div>
    </div>
    <div class="map" data-map data-lat="<?= e($lat) ?>" data-lng="<?= e($lng) ?>">
      <a class="map-ph" href="https://yandex.ru/maps/?pt=<?= e($lng . ',' . $lat) ?>&z=16&l=map" target="_blank" rel="noopener">
        <b>Показать на Яндекс Картах</b><span><?= e(setting('address')) ?></span></a>
    </div>
  </div>
</section>
