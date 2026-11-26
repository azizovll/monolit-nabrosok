<?php $tel = setting('phone_tel'); ?>
<section class="hero">
  <div class="hero-bg" aria-hidden="true"><picture><source type="image/webp" srcset="<?= e(url('assets/img/foto-1.webp')) ?>"><img src="<?= e(url('assets/img/foto-1.jpg')) ?>" alt="" width="800" height="524" fetchpriority="high"></picture></div>
  <div class="wrap hero-in">
    <div>
      <span class="badge"><i></i>Своё производство в Пскове · более 20 лет</span>
      <h1>Брусчатка и ЖБИ <span>от производителя</span> без наценки посредников</h1>
      <p class="sub">Тротуарная плитка от <?= number_format($minPave, 0, ',', ' ') ?> ₽/м², кольца колодезные, плиты, люки и бордюры с завода на Карбышева, 26а. Посчитаем за 15 минут и привезём манипулятором.</p>
      <div class="cta">
        <a class="btn btn-brand" href="<?= e(url('raschet/')) ?>">Рассчитать стоимость</a>
        <a class="btn btn-ghost" href="tel:<?= e($tel) ?>" data-goal="phone">Позвонить</a>
      </div>
      <ul class="chips">
        <li>Плитка по ГОСТ 17608-2017</li><li>Образцы всех цветов на площадке</li><li>Доставка и выгрузка манипулятором</li>
      </ul>
    </div>
    <div class="price-card">
      <div class="pic"><picture><source type="image/webp" srcset="<?= e(url('assets/img/foto-4.webp')) ?>"><img src="<?= e(url('assets/img/foto-4.jpg')) ?>" alt="Брусчатка Монолит-Вест на площадке" width="800" height="524" loading="lazy"></picture><em>Цены завода</em></div>
      <div class="body">
        <h3>Прайс на сегодня</h3>
        <?php foreach ($forms as $f): ?><a class="row" href="<?= e(url($f['url'])) ?>">Брусчатка «<?= e($f['name']) ?>»<b>от <?= number_format($f['min_price'], 0, ',', ' ') ?> ₽/м²</b></a><?php endforeach; ?>
        <?php foreach (array_slice($popular, 0, 2) as $p): ?><a class="row" href="<?= e(url($p['url'])) ?>"><?= e($p['name']) ?><b><?= $p['price'] ? number_format((int)$p['price'], 0, ',', ' ') . ' ₽' : 'по запросу' ?></b></a><?php endforeach; ?>
        <button class="btn btn-brand btn-wide" type="button" data-lead="callback" data-title="Расчёт на мой участок">Получить расчёт на мой участок</button>
        <small class="muted"><?= e(price_date_text()) ?></small>
      </div>
    </div>
  </div>
</section>

<section class="stats">
  <div class="wrap stats-in">
    <div class="stat"><b data-count="20">20+</b><span>лет производим брусчатку и ЖБИ</span></div>
    <div class="stat"><b>1 из 2</b><span>сертифицированных производителей в области</span></div>
    <div class="stat"><b><?= count(colors_all()) ?> цветов</b><span>и <?= count($forms) ?> формы плитки</span></div>
    <div class="stat"><b>15 минут</b><span>и у вас расчёт с доставкой</span></div>
  </div>
</section>

<section class="sec" id="katalog">
  <div class="wrap">
    <div class="sec-head">
      <div><span class="eyebrow">Брусчатка</span><h2>Подберите плитку для двора, дорожек и парковки</h2>
        <p class="lead">Цена за м² без скрытых доплат. Бетон В25, морозостойкость F₂200: не крошится после зимы.</p></div>
      <a class="btn btn-line" href="<?= e(url('bruschatka/')) ?>">Все формы и цвета</a>
    </div>
    <div class="cards4">
      <?php foreach ($forms as $f): $img = jdec($f['images'])[0] ?? ''; ?>
        <a class="card-p" href="<?= e(url($f['url'])) ?>">
          <div class="pic"><?= picture($img, 'Брусчатка ' . $f['name'], '(max-width: 700px) 50vw, 300px') ?></div>
          <div class="body"><h3>«<?= e($f['name']) ?>»</h3><p class="meta"><?= $f['colors_n'] ?> <?= plural($f['colors_n'], 'цвет', 'цвета', 'цветов') ?> · <?= e(implode(', ', array_map(fn($x) => $x[0] . '×' . $x[1], jdec($f['elements'])))) ?> мм</p>
            <div class="foot"><span class="price"><small>от</small> <?= number_format($f['min_price'], 0, ',', ' ') ?> ₽<small>/м²</small></span><span class="go">Подобрать цвет →</span></div></div>
        </a>
      <?php endforeach; ?>
    </div>
    <?php if ($lawn): ?>
      <a class="strip-link" href="<?= e(url('gazonnaya-reshetka/')) ?>"><b>Новинка: газонная решётка</b><span>Парковка на газоне без луж и колеи<?= $lawn['min_price'] ? ', ' . number_format($lawn['min_price'], 0, ',', ' ') . ' ₽' : '' ?> →</span></a>
    <?php endif; ?>
  </div>
</section>

<section class="sec try3d-sec">
  <div class="wrap">
    <a class="try3d" href="<?= e(url('3d/')) ?>">
      <div class="try3d-pic" aria-hidden="true"></div>
      <div class="try3d-t"><span class="eyebrow">3D-примерочная</span><b>Посмотрите плитку у своего дома до покупки</b>
        <p>Выберите дом, цвет фасада и крыши, нарисуйте дорожки и площадку, сравните формы и цвета плитки и сразу получите смету.</p></div>
      <span class="btn btn-white">Открыть примерочную</span>
    </a>
  </div>
</section>

<section class="sec calc-sec" id="raschet">
  <div class="wrap">
    <?= render('site/_calc', ['calc' => calc_data(), 'compact' => true]) ?>
  </div>
</section>

<section class="sec" id="zhbi">
  <div class="wrap">
    <div class="sec-head">
      <div><span class="eyebrow">ЖБИ</span><h2>Кольца, плиты, люки, бордюры и ФБС</h2>
        <p class="lead">Для колодцев, септиков, фундаментов и дорог. Доставим и опустим кольца манипулятором.</p></div>
      <a class="btn btn-line" href="<?= e(url('zhbi/')) ?>">Весь каталог ЖБИ</a>
    </div>
    <div class="cats">
      <?php foreach ($cats as $c): ?>
        <a class="cat" href="<?= e(url($c['url'])) ?>"><div class="pic"><?= picture($c['img'], $c['name'], '(max-width: 700px) 50vw, 200px') ?></div><b><?= e($c['name']) ?></b><span><?= $c['min_price'] ? 'от ' . number_format($c['min_price'], 0, ',', ' ') . ' ₽' : 'цена по запросу' ?></span></a>
      <?php endforeach; ?>
    </div>
    <h3 class="h3">Популярное</h3>
    <div class="ptable">
      <?php foreach ($popular as $p): ?>
        <div class="prow">
          <a class="pname" href="<?= e(url($p['url'])) ?>"><?= e($p['name']) ?></a>
          <span class="pspec"><?= e(spec_line($p)) ?></span>
          <?= price_html($p['price'] ? (int)$p['price'] : null, $p['price_unit']) ?>
          <button class="btn btn-line btn-sm" type="button" data-lead="product" data-product="<?= e($p['name']) ?>" data-qty="1">Заявка</button>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="sec why-sec">
  <div class="wrap why">
    <div class="why-pic"><picture><source type="image/webp" srcset="<?= e(url('assets/img/foto-7.webp')) ?>"><img src="<?= e(url('assets/img/foto-7.jpg')) ?>" alt="Производство Монолит-Вест" width="800" height="524" loading="lazy"></picture>
      <div class="cert"><b>Сертифицированная продукция</b><small>Один из двух сертифицированных производителей в области</small></div></div>
    <div>
      <span class="eyebrow">Почему у нас</span>
      <h2>Покупаете у завода, а не у перекупщика</h2>
      <div class="feat">
        <div><b>Плитка не крошится после зимы</b><p>Бетон класса В25, морозостойкость F₂200, водопоглощение не более 6%, ГОСТ 17608-2017.</p></div>
        <div><b>Цена завода</b><p>Своё производство в Пскове, без наценки магазинов и посредников.</p></div>
        <div><b>Привезём и выгрузим</b><p>Свои краны-манипуляторы: выгрузим куда скажете, опустим кольца, уложим плиты.</p></div>
        <div><b>Выбор вживую</b><p>На площадке уложены образцы всех цветов: цвет на экране всегда немного отличается.</p></div>
      </div>
    </div>
  </div>
</section>

<section class="sec">
  <div class="wrap">
    <span class="eyebrow">Как заказать</span>
    <h2>От заявки до плитки во дворе</h2>
    <ol class="steps">
      <li><b>Заявка или звонок</b><p>Оставьте телефон или площадь. Можно прислать план участка.</p></li>
      <li><b>Расчёт за 15 минут</b><p>Пришлём цену с запасом на подрезку, бордюрами и доставкой.</p></li>
      <li><b>Выбор цвета</b><p>По фото, в 3D-примерочной или вживую на площадке.</p></li>
      <li><b>Доставка</b><p>Привозим на объект в удобный день и выгружаем манипулятором.</p></li>
    </ol>
  </div>
</section>

<section class="sec yard">
  <div class="wrap">
    <span class="eyebrow">Площадка с образцами</span>
    <h2>Приезжайте и выберите цвет вживую</h2>
    <p class="lead">На площадке у завода уложены все цвета и формы. На экране оттенок выглядит иначе, а здесь видно, как плитка ляжет у вас во дворе.</p>
    <div class="gal">
      <?php foreach ([['foto-2', 'Стенды с образцами'], ['foto-4', 'Ёлочка, колормикс'], ['foto-6', 'Въезд во двор'], ['foto-3', 'Все цвета рядом'], ['foto-5', 'Серый «Старый город»']] as [$f, $cap]): ?>
        <figure><picture><source type="image/webp" srcset="<?= e(url("assets/img/$f.webp")) ?>"><img src="<?= e(url("assets/img/$f.jpg")) ?>" alt="<?= e($cap) ?>" width="800" height="524" loading="lazy"></picture><figcaption><?= e($cap) ?></figcaption></figure>
      <?php endforeach; ?>
    </div>
    <div class="visit"><div><b><?= e(setting('address')) ?></b><p><?= e(setting('hours')) ?>. Покажем образцы и посчитаем на месте.</p></div>
      <a class="btn btn-brand" href="<?= e(url('kontakty/')) ?>">Как добраться</a></div>
  </div>
</section>

<?= render('site/_faq', ['faq' => $faq]) ?>
<?= render('site/_cta', []) ?>
