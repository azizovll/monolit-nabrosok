<dialog class="modal" id="lead" aria-labelledby="lead-t">
  <form class="lead-form" method="post" action="<?= e(url('api/lead')) ?>" data-lead-form novalidate>
    <button type="button" class="modal-x" data-close aria-label="Закрыть">×</button>
    <h2 id="lead-t" data-lead-title>Оставить заявку</h2>
    <p class="muted" data-lead-sub>Перезвоним в течение 15 минут в рабочее время и посчитаем стоимость с доставкой.</p>
    <div class="lead-what" data-lead-what hidden></div>
    <input type="hidden" name="type" value="callback">
    <input type="hidden" name="product"><input type="hidden" name="form"><input type="hidden" name="color"><input type="hidden" name="thickness"><input type="hidden" name="area"><input type="hidden" name="total">
    <label class="f"><span>Телефон *</span><input name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__" required></label>
    <label class="f"><span>Имя</span><input name="name" autocomplete="name" placeholder="Как к вам обращаться"></label>
    <label class="f" data-qty hidden><span>Количество</span><input name="qty" inputmode="numeric" placeholder="Например, 4 шт или 60 м²"></label>
    <label class="f"><span>Адрес доставки или комментарий</span><textarea name="comment" rows="2" placeholder="Город, улица. Или ваш вопрос"></textarea></label>
    <label class="consent"><input type="checkbox" name="consent" value="1" required> <span>Согласен на обработку персональных данных по <a href="<?= e(url('politika/')) ?>" target="_blank">политике</a></span></label>
    <input class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
    <div class="form-err" data-err role="alert" hidden></div>
    <button class="btn btn-brand btn-wide" data-submit>Получить расчёт</button>
    <p class="micro">Без спама и рассылок. Можно сразу позвонить: <a href="tel:<?= e(setting('phone_tel')) ?>" data-goal="phone"><?= e(setting('phone')) ?></a></p>
  </form>
</dialog>
