<?php
$nav = [
    'bruschatka/' => 'Брусчатка', 'gazonnaya-reshetka/' => 'Газонная решётка', 'zhbi/' => 'ЖБИ',
    'raschet/' => 'Расчёт', '3d/' => '3D-примерочная', 'dostavka/' => 'Доставка', 'kontakty/' => 'Контакты',
];
$cur = ltrim($path, '/');
$max = setting('max_url');
$tel = setting('phone_tel');
$draft = setting('noindex', '1') === '1';
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($meta['title_full']) ?></title>
<?php if ($meta['description']): ?><meta name="description" content="<?= e($meta['description']) ?>"><?php endif; ?>
<?php if ($draft || $meta['noindex']): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
<link rel="canonical" href="<?= e($meta['canonical_url']) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(setting('brand')) ?>">
<meta property="og:locale" content="ru_RU">
<meta property="og:title" content="<?= e($meta['title']) ?>">
<?php if ($meta['description']): ?><meta property="og:description" content="<?= e($meta['description']) ?>"><?php endif; ?>
<meta property="og:url" content="<?= e($meta['canonical_url']) ?>">
<meta property="og:image" content="<?= e($meta['og_image']) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#8f141c">
<link rel="icon" href="<?= e(url('assets/img/emblema.png')) ?>">
<link rel="preload" href="<?= e(url('assets/fonts/onest-cyrillic.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('assets/site.css')) ?>">
<?php foreach ($meta['schema_json'] as $s): ?>
<script type="application/ld+json"><?= json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endforeach; ?>
<script>document.documentElement.classList.add('js');</script>
</head>
<body>
<?php if ($draft): ?><div class="draft">Новая версия сайта «Монолит-Вест» в разработке. Цены и наличие уточняйте по телефону <?= e(setting('phone')) ?>.</div><?php endif; ?>

<header class="hdr">
  <div class="wrap hdr-in">
    <a class="logo" href="<?= e(url('/')) ?>" aria-label="Монолит-Вест, на главную">
      <img src="<?= e(url('assets/img/emblema.png')) ?>" alt="" width="48" height="40">
      <span><b>МОНОЛИТ-ВЕСТ</b><small>Брусчатка и ЖБИ · Псков</small></span>
    </a>
    <nav class="menu" id="menu" aria-label="Разделы">
      <?php foreach ($nav as $u => $label): ?>
        <a href="<?= e(url($u)) ?>"<?= str_starts_with($cur, $u) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
      <a class="menu-extra" href="<?= e(url('o-kompanii/')) ?>">О компании</a>
      <div class="menu-call"><a class="btn btn-line" href="tel:<?= e(setting('phone_tel')) ?>" data-goal="phone">Позвонить</a><a class="btn btn-brand" href="<?= e(url('raschet/')) ?>">Рассчитать</a></div>
    </nav>
    <div class="hdr-r">
      <a class="hdr-phone" href="tel:<?= e($tel) ?>" data-goal="phone"><b><?= e(setting('phone')) ?></b><small><i></i><?= e(setting('hours')) ?></small></a>
      <?php if ($max): ?><a class="btn btn-max btn-sm" href="<?= e($max) ?>" target="_blank" rel="noopener" data-goal="max">MAX</a><?php endif; ?>
      <a class="btn btn-brand btn-sm hdr-cta" href="<?= e(url('raschet/')) ?>">Рассчитать</a>
      <button class="burger" type="button" aria-controls="menu" aria-expanded="false" aria-label="Меню"><span></span></button>
    </div>
  </div>
</header>

<main>
<?php if ($meta['crumbs']): ?>
  <nav class="crumbs wrap" aria-label="Навигация"><a href="<?= e(url('/')) ?>">Главная</a><?php foreach ($meta['crumbs'] as $i => [$n, $u]): ?><span>›</span><?= $i === count($meta['crumbs']) - 1 ? '<b>' . e($n) . '</b>' : '<a href="' . e(url($u)) . '">' . e($n) . '</a>' ?><?php endforeach; ?></nav>
<?php endif; ?>
<?= $body ?>
</main>

<footer class="ftr">
  <div class="wrap ftr-in">
    <div>
      <a class="logo logo-w" href="<?= e(url('/')) ?>"><img src="<?= e(url('assets/img/emblema-belaya.png')) ?>" alt="" width="44" height="36"><span><b>МОНОЛИТ-ВЕСТ</b></span></a>
      <p>Производство брусчатки и ЖБИ в Пскове.<br><?= e(setting('company')) ?><?= setting('inn') ? '<br>ИНН ' . e(setting('inn')) : '' ?><?= setting('ogrn') ? ', ОГРН ' . e(setting('ogrn')) : '' ?></p>
    </div>
    <div><b>Брусчатка</b><?php foreach (forms_all() as $f): ?><a href="<?= e(url($f['url'])) ?>"><?= e($f['name']) ?></a><?php endforeach; ?><a href="<?= e(url('gazonnaya-reshetka/')) ?>">Газонная решётка</a><a href="<?= e(url('3d/')) ?>">3D-примерочная</a></div>
    <div><b>ЖБИ</b><?php foreach (cats('zhbi') as $c): ?><a href="<?= e(url($c['url'])) ?>"><?= e($c['name']) ?></a><?php endforeach; ?></div>
    <div><b>Контакты</b>
      <a href="tel:<?= e($tel) ?>" data-goal="phone"><?= e(setting('phone')) ?></a>
      <?php if ($max): ?><a href="<?= e($max) ?>" target="_blank" rel="noopener" data-goal="max">Написать в MAX</a><?php endif; ?>
      <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a>
      <span><?= e(setting('address')) ?></span><span><?= e(setting('hours')) ?></span>
      <a href="<?= e(url('dostavka/')) ?>">Доставка и оплата</a><a href="<?= e(url('o-kompanii/')) ?>">О компании</a>
    </div>
  </div>
  <div class="wrap ftr-b">
    <p class="disc-f"><?= setting('disclaimer_pages', '1') === '1' ? e(setting('disclaimer_short')) . ' ' : '' ?>Информация на сайте не является публичной офертой, цены и наличие уточняйте у менеджера.</p>
    <p>© <?= date('Y') ?> <?= e(setting('company')) ?> · <a href="<?= e(url('politika/')) ?>">Политика конфиденциальности</a></p>
  </div>
</footer>

<div class="mbar">
  <a class="btn btn-line" href="tel:<?= e($tel) ?>" data-goal="phone">Позвонить</a>
  <?php if ($max): ?><a class="btn btn-max" href="<?= e($max) ?>" target="_blank" rel="noopener" data-goal="max">MAX</a><?php endif; ?>
  <a class="btn btn-brand" href="<?= e(url('raschet/')) ?>">Рассчитать</a>
</div>

<?= render('site/_lead_modal') ?>

<script>window.MW={base:<?= json_encode(base_path()) ?>,metrika:<?= setting('metrika_on') === '1' ? (int)setting('metrika_id') : 0 ?>};</script>
<script src="<?= e(asset('assets/site.js')) ?>" defer></script>
<?php if (setting('metrika_on') === '1' && setting('metrika_id')): $mid = (int)setting('metrika_id'); ?>
<script>(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})(window,document,"script","https://mc.yandex.ru/metrika/tag.js","ym");ym(<?= $mid ?>,"init",{clickmap:true,trackLinks:true,accurateTrackBounce:true,webvisor:true});</script>
<noscript><div><img src="https://mc.yandex.ru/watch/<?= $mid ?>" style="position:absolute;left:-9999px;" alt=""></div></noscript>
<?php endif; ?>
</body>
</html>
