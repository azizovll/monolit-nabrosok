<section class="sec page-top">
  <div class="wrap text narrow-text">
    <h1><?= e($title) ?></h1>
    <?= md($pg['body']) ?>
  </div>
</section>
<?php if ($pg['slug'] !== 'politika'): ?><?= render('site/_cta', []) ?><?php endif; ?>
