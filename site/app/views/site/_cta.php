<section class="sec cta-sec">
  <div class="wrap">
    <div class="final">
      <div>
        <h2><?= e($h ?? 'Узнайте цену на свой участок сегодня') ?></h2>
        <p><?= e($p ?? 'Пришлите площадь или список изделий, и через 15 минут у вас будет расчёт с доставкой. Без колл-центров и рассылок.') ?></p>
        <div class="cta-row">
          <button class="btn btn-white" type="button" data-lead="<?= e($type ?? 'callback') ?>" data-title="Получить расчёт">Получить расчёт</button>
          <a class="btn btn-ghost" href="tel:<?= e(setting('phone_tel')) ?>" data-goal="phone">Позвонить</a>
        </div>
      </div>
      <div class="contacts">
        <a href="tel:<?= e(setting('phone_tel')) ?>" data-goal="phone"><b><?= e(setting('phone')) ?></b><small><?= e(setting('hours')) ?></small></a>
        <?php if (setting('max_url')): ?><a href="<?= e(setting('max_url')) ?>" target="_blank" rel="noopener" data-goal="max"><b>Написать в MAX</b><small>Ответим в рабочее время</small></a><?php endif; ?>
        <a href="<?= e(url('kontakty/')) ?>"><b><?= e(setting('address')) ?></b><small>Площадка с образцами всех цветов</small></a>
      </div>
    </div>
  </div>
</section>
