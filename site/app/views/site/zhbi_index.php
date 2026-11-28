<section class="sec page-top">
  <div class="wrap">
    <span class="eyebrow">ЖБИ от производителя</span>
    <h1>Железобетонные изделия в Пскове</h1>
    <p class="lead">Кольца колодезные, кольца с дном, днища, плиты перекрытия, люки, бордюры и фундаментные блоки. Цены завода, доставка и монтаж своими манипуляторами.</p>
    <div class="cta-row"><button class="btn btn-brand" type="button" data-lead="product" data-title="Подобрать комплект для колодца" data-product="Комплект для колодца">Подобрать комплект для колодца</button>
      <a class="btn btn-line" href="tel:<?= e(setting('phone_tel')) ?>" data-goal="phone">Позвонить</a></div>
  </div>
</section>
<section class="sec pt0">
  <div class="wrap">
    <div class="cats cats-big">
      <?php foreach ($cats as $c): ?>
        <a class="cat" href="<?= e(url($c['url'])) ?>"><div class="pic"><?= picture($c['img'], $c['name'], '(max-width: 700px) 50vw, 280px') ?></div>
          <b><?= e($c['name']) ?></b><span><?= $c['n'] ?> <?= plural($c['n'], 'позиция', 'позиции', 'позиций') ?><?= $c['min_price'] ? ' · от ' . number_format($c['min_price'], 0, ',', ' ') . ' ₽' : '' ?></span>
          <p><?= e($c['intro']) ?></p></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section class="sec">
  <div class="wrap hint-box">
    <h2>Как собрать колодец за одну заявку</h2>
    <ol class="steps steps-sm">
      <li><b>Кольца</b><p>Сквозные КС для колодца и смотровых, дренажные КС-Д для септика, с отверстиями В1/В2 для ввода трубы.</p></li>
      <li><b>Дно</b><p>Плита днища ПД или сразу кольцо с дном КСД: так снизу герметично.</p></li>
      <li><b>Перекрытие</b><p>Плита ПП того же диаметра, можно с влитым люком.</p></li>
      <li><b>Люк</b><p>Полимерный А15 для газона, В125 если заезжает машина.</p></li>
    </ol>
  </div>
</section>
<?= render('site/_faq', ['faq' => $faq]) ?>
<?= render('site/_cta', ['h' => 'Посчитаем ЖБИ с доставкой и монтажом', 'type' => 'product']) ?>
