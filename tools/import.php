<?php
declare(strict_types=1);
/*
 * Первичное наполнение базы из каталога старого сайта (data/catalog.json).
 * Запуск: tools/php.sh tools/import.php --fresh
 *   --fresh  удалить базу и загруженные фото товаров и собрать заново
 * Только для первого наполнения: после запуска сайта источник правды это админка.
 */

require __DIR__ . '/../site/app/bootstrap.php';

$fresh = in_array('--fresh', $argv, true);
$src = dirname(__DIR__) . '/data';
if ($fresh) {
    @unlink(cfg('db_file'));
    foreach (['products', 'paving'] as $d) foreach (glob(UPLOAD_DIR . "/$d/*") ?: [] as $f) @unlink($f);
}
if ((int)val('SELECT COUNT(*) FROM products') > 0) exit("В базе уже есть товары. Для пересборки: --fresh\n");

$catalog = json_decode(file_get_contents("$src/catalog.json"), true);
$t0 = microtime(true);
db()->beginTransaction();

/* ---------- настройки ---------- */
$defaults = [
    'company'        => 'ООО «Монолит-Вест»',
    'brand'          => 'Монолит-Вест',
    'phone'          => '8 (921) 215-26-43',
    'phone_tel'      => '+79212152643',
    'email'          => 'monolit-west@yandex.ru',
    'lead_email'     => 'monolit-west@yandex.ru',
    'address'        => 'Псков, ул. Карбышева, 26а',
    'hours'          => 'Пн–Сб 9:00–18:00',
    'geo'            => '57.8136,28.3496',
    'max_url'        => '',
    'inn'            => '',
    'ogrn'           => '',
    'metrika_id'     => '109301632',
    'metrika_on'     => '0',            // включаем при переезде в корень, чтобы /new не портил статистику
    'noindex'        => '1',            // пока сайт в /new
    'site_url'       => 'https://monolitwest.ru/new',
    'price_date'     => date('Y-m-d'),
    'disclaimer'     => 'Цвета и внешний вид изделий на экране ознакомительные и могут отличаться от реальной продукции. Перед покупкой посмотрите образцы на площадке: Псков, ул. Карбышева, 26а.',
    'disclaimer_short' => 'Цвета на экране ознакомительные, образцы смотрите на площадке.',
    'delivery_regions' => 'Псков, Псковская, Новгородская и Ленинградская области',
];
foreach ($defaults as $k => $v) set_setting($k, $v);

/* ---------- разделы ---------- */
$cats = [
    ['kolca',              'zhbi', 'Кольца колодезные',          'Кольца колодезные от производителя'],
    ['kolca-s-dnom',       'zhbi', 'Кольца с дном',              'Кольца колодезные с дном'],
    ['elementy-kolodcev',  'zhbi', 'Элементы колодцев',          'Днища, плиты перекрытия и опорные кольца'],
    ['luki',               'zhbi', 'Люки',                       'Люки полимерные и чугунные'],
    ['bordyury',           'zhbi', 'Бордюры',                    'Бордюрный камень'],
    ['fbs',                'zhbi', 'Фундаментные блоки ФБС',     'Фундаментные блоки ФБС'],
    ['gazonnaya-reshetka', 'lawn', 'Газонная решётка',           'Газонная решётка'],
];
$catId = [];
foreach ($cats as $i => [$slug, $kind, $name, $h1]) {
    $catId[$slug] = insert('categories', ['slug' => $slug, 'kind' => $kind, 'name' => $name, 'h1' => $h1, 'sort' => $i * 10]);
}

/* ---------- фото: каждый одинаковый файл обрабатываем один раз ---------- */
$imgDone = [];
function import_img(string $rel, string $subdir, string $name): string {
    global $imgDone, $src;
    $file = "$src/$rel";
    if (!is_file($file)) return '';
    $h = md5_file($file);
    if (!isset($imgDone[$h])) $imgDone[$h] = img_store($file, $subdir, $name);
    return $imgDone[$h];
}

/* ---------- характеристики ---------- */
function mark_height_cm(string $name): ?int {
    // КС-10-9, КСД-15-6, КС-10-9Д: последнее число в дециметрах
    if (preg_match('~КСД?-\d+-(\d+)~u', $name, $m)) return (int)$m[1] * 10;
    return null;
}
function fbs_dims(string $name): ?string {
    if (!preg_match('~ФБС-(\d+)-(\d+)-(\d+)~u', $name, $m)) return null;
    $L = ['24' => 2380, '12' => 1180, '9' => 880, '6' => 580][$m[1]] ?? null;
    $W = ['3' => 300, '4' => 400, '5' => 500, '6' => 600][$m[2]] ?? null;
    $H = ['6' => 580, '3' => 280][$m[3]] ?? null;
    return ($L && $W && $H) ? "{$L}×{$W}×{$H} мм" : null;
}
function load_class(string $name): ?string {
    $cls = ['А15' => 'А15, до 1,5 т (пешеходные зоны, газоны)', 'В125' => 'В125, до 12,5 т (тротуары, парковки легковых машин)', 'С250' => 'С250, до 25 т (обочины, дворы с проездом грузовых машин)'];
    $out = [];
    foreach ($cls as $k => $v) if (mb_strpos($name, $k) !== false) $out[] = $v;
    return $out ? implode('; ', $out) : null;
}
function product_slug(string $name): string {
    $n = str_replace('Бручатка', 'Брусчатка', $name);
    if (preg_match('~((?:КСД?|ПП|ПД|КО|ФБС|БР)[\s-]?[\d.]+.*)$~u', $n, $m)) return slugify($m[1]);
    return slugify($n);
}

/* ---------- товары ЖБИ ---------- */
$usedSlugs = [];
$redirects = [];
$skipped = [];
foreach ($catalog as $p) {
    $cats = $p['cats'];
    if (isset($p['attrs']['Форма'])) continue;               // брусчатка отдельно
    $name = $p['name'];
    $has = fn($c) => in_array($c, $cats, true);
    [$cat, $sub] = match (true) {
        $has('Блоки фундаментные') => ['fbs', ''],
        $has('Бордюры') => ['bordyury', ''],
        $has('Кольца колодезные с дном') => ['kolca-s-dnom', ''],
        $has('Кольца колодезные дренажные') => ['kolca', 'Дренажные'],
        $has('Кольца с отверстиями для труб') => ['kolca', 'С отверстиями для труб'],
        $has('Кольца колодезные сквозные') || $has('Кольца колодезные') => ['kolca', 'Сквозные'],
        $has('Днища колодцев') => ['elementy-kolodcev', 'Днища'],
        $has('Плиты перекрытия') => ['elementy-kolodcev', 'Плиты перекрытия'],
        $has('Плиты опорные') => ['elementy-kolodcev', 'Опорные плиты и кольца'],
        $has('Люки полимерные') => ['luki', 'Полимерные'],
        $has('Люки чугунные') => ['luki', 'Чугунные'],
        default => [null, ''],
    };
    if (!$cat) { $skipped[] = $name; continue; }

    $s = $p['specs'] ?? [];
    $specs = [];
    $note = [];
    if (isset($s['Внутренний диаметр'])) {
        $specs[] = ['Внутренний диаметр', $s['Внутренний диаметр']];
        $specs[] = ['Наружный диаметр', $s['Наружный диаметр'] ?? ''];
        if ($h = mark_height_cm($name)) $specs[] = ['Высота', "$h см"];
        $specs[] = ['Толщина стенки', $s['Толщина стенки'] ?? ''];
        if (!empty($s['Вес'])) $specs[] = ['Вес', $s['Вес']];
    }
    if (isset($s['Длина'])) {                                // бордюры
        $specs[] = ['Размеры', trim(($s['Длина'] ?? '') . ' × ' . ($s['Высота'] ?? '') . ' × ' . ($s['Толщина'] ?? ''))];
        foreach (['Количество шт. в 1 поддоне' => 'Штук на поддоне', 'Класс бетона изделий по прочности на сжатие' => 'Класс бетона', 'Марка бетона по морозостойкости' => 'Морозостойкость', 'Водопоглощение' => 'Водопоглощение', 'Прокрас' => 'Прокрас', 'Лицевой слой' => 'Лицевой слой', 'Фактура' => 'Фактура'] as $k => $label)
            if (!empty($s[$k])) $specs[] = [$label, $s[$k]];
        if (!empty($s['Вес'])) { $specs[] = ['Вес поддона', $s['Вес']]; $note[] = 'Вес 1200 кг на старом сайте: проверить, это вес поддона?'; }
    }
    if ($d = fbs_dims($name)) { $specs[] = ['Размеры (Д×Ш×В)', $d]; $specs[] = ['Стандарт', 'ГОСТ 13579-2018']; $note[] = 'Размеры ФБС взяты по ГОСТ из маркировки, подтвердить у заказчика.'; }
    if ($lc = load_class($name)) $specs[] = ['Класс нагрузки', $lc];
    // ГОСТ/бетон из описаний колец
    $desc = $p['desc'];
    if (preg_match('~Бетон\s+(М\d+(?:\s*[–-]\s*М\d+)?)~u', $desc, $m)) $specs[] = ['Бетон', $m[1]];
    if (mb_stripos($desc, 'еврозамок') !== false) $specs[] = ['Соединение', 'еврозамок (паз-гребень)'];
    if (preg_match('~ГОСТ\s+[\d-]+~u', $desc, $m) && !array_filter($specs, fn($x) => $x[0] === 'Стандарт')) $specs[] = ['Стандарт', $m[0]];

    $price = ($p['price'] ?? 0) > 0 ? (int)$p['price'] : null;
    if ($price === null) $note[] = 'На старом сайте цена 0 ₽: уточнить цену.';
    if ($name === 'Кольцо колодезное с днищем КСД-15-9') $note[] = 'Внутренний диаметр указан 100 см, по маркировке 150: уточнить.';
    if ($name === 'Кольцо колодезное с днищем КСД-15-6') $note[] = 'В описании стенка 15 см, в характеристиках 10 см: уточнить.';

    $slug = product_slug($name);
    $base = $slug; $i = 2;
    while (isset($usedSlugs[$slug])) $slug = $base . '-' . $i++;
    $usedSlugs[$slug] = true;

    $images = [];
    foreach ($p['local_images'] as $rel) if ($b = import_img($rel, 'products', $slug)) $images[] = $b;

    $id = insert('products', [
        'category_id' => $catId[$cat], 'subgroup' => $sub, 'slug' => $slug, 'name' => $name,
        'description' => $desc, 'price' => $price, 'price_unit' => 'шт',
        'specs' => jenc(array_values(array_filter($specs, fn($x) => $x[1] !== ''))),
        'images' => jenc($images), 'sort' => count($usedSlugs) * 10,
        'old_url' => $p['url'], 'note' => implode(' ', $note), 'updated_at' => now(),
    ]);
    $redirects[rawurldecode(parse_url($p['url'], PHP_URL_PATH))] = "/zhbi/$cat/$slug/";
}

/* газонная решётка: новая позиция, фото пока нет */
insert('products', [
    'category_id' => $catId['gazonnaya-reshetka'], 'slug' => 'gazonnaya-reshetka', 'name' => 'Газонная решётка',
    'price' => 1100, 'price_unit' => 'шт', 'specs' => jenc([['Высота', '80 мм']]), 'images' => '[]', 'sort' => 10,
    'note' => 'Уточнить: цена за штуку или за м², размер элемента, вес, цвета, нагрузка. Нужна иллюстрация.', 'updated_at' => now(),
]);

/* ---------- брусчатка ---------- */
$forms = [
    // slug, название, элементы мм, м² на поддоне
    ['kirpich',      'Кирпич',       [[200, 100]],                          10.0],
    ['novyj-gorod',  'Новый город',  [[240, 160], [160, 160], [80, 160]],   10.1],
    ['staryj-gorod', 'Старый город', [[200, 132], [132, 132], [132, 66]],   10.5],
    ['parket',       'Паркет',       [[240, 80]],                           10.1],
];
$commonSpecs = [
    ['Класс бетона', 'В25'], ['Морозостойкость', 'F₂200'], ['Водопоглощение', 'не более 6%'],
    ['Истираемость', 'G1'], ['Стандарт', 'ГОСТ 17608-2017'],
];
$formId = [];
foreach ($forms as $i => [$slug, $name, $el, $m2]) {
    $formId[$name] = insert('paving_forms', [
        'slug' => $slug, 'name' => $name, 'elements' => jenc($el), 'm2_per_pallet' => $m2, 'pallet_kg' => 1200,
        'specs' => jenc($commonSpecs), 'sort' => $i * 10,
    ]);
}

// палитры колормиксов взяты из примерочной (подобраны по фото образцов), одноцветные приблизительные: калибруются по фото Николая
$colors = [
    ['belyj',      'Белый',      'mono', [['#dcd8d2', 3], ['#d1ccc5', 2], ['#e6e2dc', 2]]],
    ['zheltyj',    'Желтый',     'mono', [['#d4ae4e', 3], ['#dcb95f', 2], ['#c9a144', 2]]],
    ['krasnyj',    'Красный',    'mono', [['#9b3f31', 3], ['#a8493b', 2], ['#8b372b', 2]]],
    ['korichnevyj','Коричневый', 'mono', [['#6e4b37', 3], ['#7a5541', 2], ['#62412f', 2]]],
    ['chernyj',    'Черный',     'mono', [['#3b3937', 3], ['#454341', 2], ['#333130', 2]]],
    ['seryj',      'Серый',      'mono', [['#8f8c88', 3], ['#9a9793', 2], ['#85827e', 2]]],
    ['kapuchino',  'Капучино',   'mix',  [['#c9a59a', 3], ['#dcb7aa', 2], ['#b5958d', 2], ['#c19a8d', 2], ['#a8877d', 1]]],
    ['klinker',    'Клинкер',    'mix',  [['#a66f55', 3], ['#bf8b6e', 2], ['#d29674', 2], ['#8a5a47', 2], ['#7a6156', 1]]],
    ['noch',       'Ночь',       'mix',  [['#6e6a67', 3], ['#938e8b', 2], ['#4f4c4a', 2], ['#aea8a4', 2], ['#3c3a39', 1]]],
    ['osen',       'Осень',      'mix',  [['#c98a5e', 3], ['#dda670', 2], ['#b57f5c', 2], ['#e7b780', 1], ['#94603f', 1]]],
    ['step',       'Степь',      'mix',  [['#c8a777', 3], ['#b8925f', 2], ['#d9bc8e', 2], ['#9c7b55', 1]]],
    ['yantar',     'Янтарь',     'mix',  [['#c99d6c', 3], ['#ac8966', 2], ['#e1ae72', 2], ['#856f5b', 2], ['#9a7a55', 1]]],
];
$colorId = [];
foreach ($colors as $i => [$slug, $name, $grp, $pal]) {
    $colorId[$name] = insert('paving_colors', ['slug' => $slug, 'name' => $name, 'grp' => $grp, 'palette' => jenc($pal), 'sort' => $i * 10]);
}

$formImg = [];
foreach ($catalog as $p) {
    if (!isset($p['attrs']['Форма'])) continue;
    $form = $p['attrs']['Форма'][0]; $color = $p['attrs']['Цвета'][0];
    $fid = $formId[$form]; $cid = $colorId[$color];
    $forming = mb_strtolower($p['specs']['Формование'] ?? '');
    $img = $p['local_images'] ? import_img($p['local_images'][0], 'paving', slugify("$form $color")) : '';
    $formSlug = slugify($form);
    $redirects[rawurldecode(parse_url($p['url'], PHP_URL_PATH))] = "/bruschatka/$formSlug/?cvet=" . slugify($color);
    if ($p['type'] === 'variable') {                          // серый «Кирпич» 60 и 80 мм
        foreach ([[60, 1000], [80, 1200]] as [$th, $pr]) {
            q('INSERT OR IGNORE INTO paving_prices(form_id,color_id,thickness,price,forming,image,old_url) VALUES(?,?,?,?,?,?,?)', [$fid, $cid, $th, $pr, $forming, $img, $p['url']]);
        }
        continue;
    }
    // дубли (желтый и черный «Кирпич» заведены дважды): INSERT OR IGNORE оставляет первую запись
    q('INSERT OR IGNORE INTO paving_prices(form_id,color_id,thickness,price,forming,image,old_url) VALUES(?,?,?,?,?,?,?)',
      [$fid, $cid, 60, (int)$p['price'] ?: null, $forming, $img, $p['url']]);
    if ($img && !isset($formImg[$fid])) $formImg[$fid] = $img;
}
foreach ($formImg as $fid => $img) update('paving_forms', ['images' => jenc([$img])], 'id = ?', [$fid]);

/* ---------- тексты по умолчанию ---------- */
require APP_DIR . '/site/content.php';
foreach (CONTENT_FORMS as $slug => $t) update('paving_forms', $t, 'slug = ?', [$slug]);
foreach (CONTENT_CATS as $slug => $t) update('categories', $t, 'slug = ?', [$slug]);
foreach (CONTENT_FAQ as $scope => $list) foreach ($list as $i => [$qq, $aa]) insert('faq', ['scope' => $scope, 'q' => $qq, 'a' => $aa, 'sort' => $i * 10]);

/* ---------- страницы ---------- */
$old = json_decode(file_get_contents("$src/old-pages.json"), true);
$privacy = '';
foreach ($old as $pg) if ($pg['slug'] === 'privacy-policy') $privacy = $pg['content']['rendered'];
$privacy = strip_tags($privacy, '<p><h2><h3><h4><ul><ol><li><strong><b><em><br>');
$privacy = preg_replace('~\s(class|style|id)="[^"]*"~', '', $privacy);
foreach ([
    ['politika', 'Политика конфиденциальности', $privacy],
    ['dostavka', 'Доставка и оплата', CONTENT_PAGES['dostavka']],
    ['o-kompanii', 'О компании', CONTENT_PAGES['o-kompanii']],
    ['kontakty', 'Контакты', ''],
] as [$slug, $title, $body]) insert('pages', ['slug' => $slug, 'title' => $title, 'h1' => $title, 'body' => $body, 'updated_at' => now()]);

/* ---------- редиректы со старого сайта ---------- */
$oldPages = [
    '/каталог/' => '/bruschatka/', '/жби/' => '/zhbi/', '/галерея/' => '/', '/contacts/' => '/kontakty/',
    '/about-company/' => '/o-kompanii/', '/mission-vision/' => '/dostavka/', '/privacy-policy/' => '/politika/',
    '/cart/' => '/', '/shop/' => '/bruschatka/', '/checkout/' => '/', '/my-account/' => '/', '/оформление-заказа/' => '/',
    '/product-category/брусчатка/' => '/bruschatka/', '/product-category/кольца-колодезные/' => '/zhbi/kolca/',
    '/product-category/кольца-колодезные-с-дном/' => '/zhbi/kolca-s-dnom/', '/product-category/элементы-колодцев/' => '/zhbi/elementy-kolodcev/',
    '/product-category/люки/' => '/zhbi/luki/', '/product-category/бордюры/' => '/zhbi/bordyury/', '/product-category/блоки-фундаментные/' => '/zhbi/fbs/',
];
foreach (json_decode(file_get_contents("$src/old-products_categories.json"), true) as $c) {
    $path = rawurldecode(parse_url($c['permalink'], PHP_URL_PATH));
    if (isset($oldPages[$path])) continue;
    $name = mb_strtolower(html_entity_decode($c['name']));
    $to = match (true) {
        str_contains($name, 'кирпич') => '/bruschatka/kirpich/', str_contains($name, 'новый город') => '/bruschatka/novyj-gorod/',
        str_contains($name, 'старый город') => '/bruschatka/staryj-gorod/', str_contains($name, 'паркет') => '/bruschatka/parket/',
        str_contains($name, 'бордюр') => '/zhbi/bordyury/', str_contains($name, 'люки') => '/zhbi/luki/',
        str_contains($name, 'кольца') => '/zhbi/kolca/', default => '/zhbi/elementy-kolodcev/',
    };
    $oldPages[$path] = $to;
}
foreach ($oldPages + $redirects as $from => $to) q('INSERT OR REPLACE INTO redirects(from_path,to_path) VALUES(?,?)', [$from, $to]);

db()->commit();

/* ---------- отчёт ---------- */
printf("Готово за %.1f с\n", microtime(true) - $t0);
printf("  разделов: %d, товаров: %d (без фото: %d)\n", val('SELECT COUNT(*) FROM categories'), val('SELECT COUNT(*) FROM products'), val("SELECT COUNT(*) FROM products WHERE images='[]'"));
printf("  брусчатка: форм %d, цветов %d, цен %d\n", val('SELECT COUNT(*) FROM paving_forms'), val('SELECT COUNT(*) FROM paving_colors'), val('SELECT COUNT(*) FROM paving_prices'));
printf("  уникальных фото обработано: %d, редиректов: %d, заметок для проверки: %d\n", count($imgDone), val('SELECT COUNT(*) FROM redirects'), val("SELECT COUNT(*) FROM products WHERE note<>''"));
if ($skipped) echo "  ПРОПУЩЕНО (нет раздела): " . implode(', ', $skipped) . "\n";
$sz = 0; foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(UPLOAD_DIR, FilesystemIterator::SKIP_DOTS)) as $f) $sz += $f->getSize();
printf("  фото на диске: %.1f МБ\n", $sz / 1048576);
