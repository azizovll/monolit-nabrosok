<?php
declare(strict_types=1);

function page_home() {
    $forms = forms_all();
    $cats = cats('zhbi');
    $lawn = cats('lawn')[0] ?? null;
    $popular = [];
    foreach (['ks-10-9', 'ks-15-9', 'ksd-10-9', 'pp-10-1', 'lyuk-polimernyj-srednij-a15-v125', 'br100-20-8', 'fbs-24-4-6', 'ks-10-9d'] as $s) {
        $p = row('SELECT * FROM products WHERE slug = ? AND visible = 1', [$s]);
        if ($p) { product_prepare($p); $popular[] = $p; }
    }
    $minPave = (int)val('SELECT MIN(price) FROM paving_prices WHERE price IS NOT NULL');
    $faq = faq('home');
    return site_page('home', compact('forms', 'cats', 'lawn', 'popular', 'minPave', 'faq'), [
        'title' => 'Брусчатка и ЖБИ от производителя в Пскове · Монолит-Вест',
        'description' => 'Тротуарная плитка от ' . number_format($minPave, 0, ',', ' ') . ' ₽/м², кольца колодезные, плиты, люки, бордюры и ФБС с завода в Пскове. Расчёт за 15 минут, доставка манипулятором по области.',
        'canonical' => '/', 'schema' => $faq ? [schema_faq($faq)] : [],
    ]);
}

function page_paving_index() {
    $forms = forms_all();
    $colors = colors_all();
    $faq = faq('paving');
    $min = (int)val('SELECT MIN(price) FROM paving_prices WHERE price IS NOT NULL');
    return site_page('paving_index', compact('forms', 'colors', 'faq', 'min'), [
        'title' => 'Брусчатка в Пскове от производителя, цена от ' . number_format($min, 0, ',', ' ') . ' ₽/м²',
        'description' => 'Тротуарная плитка «Кирпич», «Новый город», «Старый город» и «Паркет» в 12 цветах. Бетон В25, F₂200, ГОСТ 17608-2017. Образцы на площадке, доставка манипулятором.',
        'crumbs' => [['Брусчатка', '/bruschatka/']], 'schema' => $faq ? [schema_faq($faq)] : [],
    ]);
}

function page_paving_form(string $slug) {
    $f = form_by_slug($slug);
    if (!$f) return false;
    $colors = form_colors((int)$f['id']);
    if (!$colors) return false;
    $prices = array_filter(array_merge(...array_map(fn($c) => array_column($c['variants'], 'price'), $colors)));
    $min = $prices ? min($prices) : null; $max = $prices ? max($prices) : null;
    $el = jdec($f['elements']);
    $size = implode(', ', array_map(fn($x) => $x[0] . '×' . $x[1], $el));
    $faq = faq('paving');
    $others = array_values(array_filter(forms_all(), fn($x) => $x['slug'] !== $slug));
    $img = jdec($f['images'])[0] ?? ($colors[0]['variants'][0]['image'] ?? '');
    return site_page('paving_form', compact('f', 'colors', 'min', 'max', 'size', 'faq', 'others', 'el'), [
        'title' => $f['seo_title'] ?: 'Брусчатка «' . $f['name'] . '» ' . $size . ' купить в Пскове, цена от ' . number_format((int)$min, 0, ',', ' ') . ' ₽/м²',
        'description' => $f['seo_description'] ?: meta_desc('Брусчатка «' . $f['name'] . '» от производителя: ' . count($colors) . ' ' . plural(count($colors), 'цвет', 'цвета', 'цветов') . ', от ' . number_format((int)$min, 0, ',', ' ') . ' ₽/м². ' . $f['short']),
        'image' => $img,
        'crumbs' => [['Брусчатка', '/bruschatka/'], [$f['name'], '/' . $f['url']]],
        'schema' => array_values(array_filter([schema_product('Брусчатка «' . $f['name'] . '»', $f['short'], $img, $min, '/' . $f['url'], true, $max !== $min ? $max : null), $faq ? schema_faq($faq) : null])),
    ]);
}

function page_lawn() {
    $c = cats('lawn')[0] ?? null;
    if (!$c) return false;
    $items = products_in((int)$c['id']);
    $p = $items[0] ?? null;
    return site_page('lawn', compact('c', 'items', 'p'), [
        'title' => $c['seo_title'] ?: 'Газонная решётка в Пскове от производителя' . ($p && $p['price'] ? ', цена ' . number_format((int)$p['price'], 0, ',', ' ') . ' ₽' : ''),
        'description' => $c['seo_description'] ?: meta_desc($c['intro']),
        'image' => $p['images_a'][0] ?? '',
        'crumbs' => [['Газонная решётка', '/gazonnaya-reshetka/']],
        'schema' => $p ? [schema_product('Газонная решётка', $c['intro'], $p['images_a'][0] ?? null, $p['price'] ? (int)$p['price'] : null, '/gazonnaya-reshetka/')] : [],
    ]);
}

function page_zhbi_index() {
    $cats = cats('zhbi');
    $faq = faq('zhbi');
    return site_page('zhbi_index', compact('cats', 'faq'), [
        'title' => 'ЖБИ в Пскове от производителя: кольца, плиты, люки, бордюры, ФБС',
        'description' => 'Кольца колодезные, кольца с дном, днища и плиты перекрытия, люки, бордюры и фундаментные блоки с завода в Пскове. Доставка и монтаж манипулятором.',
        'crumbs' => [['ЖБИ', '/zhbi/']], 'schema' => $faq ? [schema_faq($faq)] : [],
    ]);
}

function page_zhbi_cat(string $slug) {
    $c = cat_by_slug($slug);
    if (!$c || $c['kind'] !== 'zhbi') return false;
    $items = products_in((int)$c['id']);
    // группы в понятном порядке, внутри по маркировке с учётом чисел (КС-7, КС-8, КС-10...)
    $order = ['Сквозные', 'Дренажные', 'С отверстиями для труб', 'Днища', 'Плиты перекрытия', 'Опорные плиты и кольца', 'Полимерные', 'Чугунные'];
    usort($items, fn($a, $b) => [array_search($a['subgroup'], $order) === false ? 99 : array_search($a['subgroup'], $order), $a['sort'] ? 0 : 1] <=> [array_search($b['subgroup'], $order) === false ? 99 : array_search($b['subgroup'], $order), $b['sort'] ? 0 : 1] ?: strnatcmp($a['name'], $b['name']));
    $groups = [];
    foreach ($items as $p) $groups[$p['subgroup'] ?: ''][] = $p;
    $cats = cats('zhbi');
    $faq = faq('zhbi');
    return site_page('zhbi_cat', compact('c', 'items', 'groups', 'cats', 'faq'), [
        'title' => $c['seo_title'] ?: $c['name'] . ' в Пскове от производителя' . ($c['min_price'] ? ', цена от ' . number_format($c['min_price'], 0, ',', ' ') . ' ₽' : ''),
        'description' => $c['seo_description'] ?: meta_desc($c['intro'] . ' Цены производителя, доставка манипулятором.'),
        'image' => $c['img'],
        'crumbs' => [['ЖБИ', '/zhbi/'], [$c['name'], '/' . $c['url']]],
    ]);
}

function page_product(string $catSlug, string $slug) {
    $c = cat_by_slug($catSlug);
    if (!$c || $c['kind'] !== 'zhbi') return false;
    $p = product_by_slug((int)$c['id'], $slug);
    if (!$p) return false;
    $related = related_for($p);
    $siblings = array_values(array_filter(products_in((int)$c['id']), fn($x) => $x['id'] !== $p['id'] && $x['subgroup'] === $p['subgroup']));
    $siblings = array_slice($siblings, 0, 6);
    $priceTxt = $p['price'] ? ', цена ' . number_format((int)$p['price'], 0, ',', ' ') . ' ₽' : '';
    $specLine = implode(', ', array_map(fn($s) => mb_strtolower($s[0]) . ' ' . $s[1], array_slice($p['specs_a'], 0, 4)));
    return site_page('product', compact('p', 'c', 'related', 'siblings'), [
        'title' => $p['seo_title'] ?: $p['name'] . ' купить в Пскове' . $priceTxt,
        'description' => $p['seo_description'] ?: meta_desc($p['name'] . ' от производителя' . $priceTxt . ($specLine ? '. ' . ucfirst($specLine) : '') . '. Доставка манипулятором по Пскову и области.'),
        'image' => $p['images_a'][0] ?? '',
        'crumbs' => [['ЖБИ', '/zhbi/'], [$c['name'], '/' . $c['url']], [$p['name'], '/' . $p['url']]],
        'schema' => [schema_product($p['name'], $p['description'] ?: $specLine, $p['images_a'][0] ?? null, $p['price'] ? (int)$p['price'] : null, '/' . $p['url'], (bool)$p['in_stock'])],
    ]);
}

/* данные для калькулятора: формы, цвета с ценами по толщинам, м² и вес поддона */
function calc_data(): array {
    $data = [];
    foreach (forms_all() as $f) {
        $colors = array_map(fn($c) => ['slug' => $c['slug'], 'name' => $c['name'], 'grp' => $c['grp'], 'sw' => swatch_css($c['palette']),
            'v' => array_map(fn($v) => ['t' => $v['thickness'], 'p' => $v['price'], 'a' => $v['available']], $c['variants'])], form_colors((int)$f['id']));
        $data[] = ['slug' => $f['slug'], 'name' => $f['name'], 'm2' => (float)$f['m2_per_pallet'], 'kg' => (int)$f['pallet_kg'], 'colors' => $colors];
    }
    return $data;
}

function page_calc() {
    $data = calc_data();
    $kerbs = rows("SELECT p.name, p.price, p.specs FROM products p JOIN categories c ON c.id = p.category_id WHERE c.slug = 'bordyury' AND p.visible = 1 ORDER BY price");
    return site_page('calc', ['calc' => $data, 'kerbs' => $kerbs], [
        'title' => 'Калькулятор брусчатки: сколько плитки и поддонов нужно, цена',
        'description' => 'Посчитайте площадь, количество поддонов, вес и стоимость брусчатки с запасом на подрезку. Точный расчёт с доставкой пришлём за 15 минут.',
        'crumbs' => [['Расчёт', '/raschet/']],
    ]);
}

function page_info(string $slug, string $title, string $desc, string $tpl = 'info') {
    $pg = page_row($slug);
    return site_page($tpl, ['pg' => $pg, 'title' => $pg['h1'] ?: $title], [
        'title' => $pg['seo_title'] ?: $title,
        'description' => $pg['seo_description'] ?: $desc,
        'crumbs' => [[$pg['h1'] ?: $title, '/' . $slug . '/']],
    ]);
}
function page_delivery() { return page_info('dostavka', 'Доставка и оплата', 'Доставка брусчатки и ЖБИ по Пскову, Псковской, Новгородской и Ленинградской областям. Свои краны-манипуляторы, разгрузка и монтаж. Оплата наличными или по счёту.'); }
function page_about() { return page_info('o-kompanii', 'О компании', 'ООО «Монолит-Вест»: производитель брусчатки и ЖБИ в Пскове больше 20 лет. Своё производство, ГОСТ, доставка манипулятором.'); }
function page_contacts() { return page_info('kontakty', 'Контакты', 'Телефон 8 (921) 215-26-43, Псков, ул. Карбышева, 26а. Площадка с образцами брусчатки, Пн–Сб 9:00–18:00.', 'contacts'); }
function page_privacy() { return page_info('politika', 'Политика конфиденциальности', 'Политика обработки персональных данных ООО «Монолит-Вест».'); }

function page_thanks() {
    return site_page('thanks', [], ['title' => 'Заявка отправлена', 'noindex' => true]);
}
