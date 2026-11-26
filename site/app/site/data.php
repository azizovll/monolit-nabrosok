<?php
declare(strict_types=1);

/* Выборки для публичных страниц */

function forms_all(): array {
    static $f = null;
    if ($f === null) {
        $f = rows('SELECT * FROM paving_forms WHERE visible = 1 ORDER BY sort');
        foreach ($f as &$x) {
            $x['min_price'] = (int)val('SELECT MIN(price) FROM paving_prices WHERE form_id = ? AND price IS NOT NULL', [$x['id']]);
            $x['colors_n'] = (int)val('SELECT COUNT(DISTINCT color_id) FROM paving_prices WHERE form_id = ?', [$x['id']]);
            $x['url'] = 'bruschatka/' . $x['slug'] . '/';
        }
    }
    return $f;
}

function form_by_slug(string $slug): ?array {
    foreach (forms_all() as $f) if ($f['slug'] === $slug) return $f;
    return null;
}

/* цвета формы с ценами по толщинам: [color + 'variants' => [[thickness, price, available, forming, image]]] */
function form_colors(int $formId): array {
    $out = [];
    foreach (rows('SELECT c.*, p.thickness, p.price, p.available, p.forming, p.image
                   FROM paving_prices p JOIN paving_colors c ON c.id = p.color_id
                   WHERE p.form_id = ? ORDER BY c.sort, p.thickness', [$formId]) as $r) {
        $id = $r['id'];
        if (!isset($out[$id])) $out[$id] = ['id' => $id, 'slug' => $r['slug'], 'name' => $r['name'], 'grp' => $r['grp'], 'palette' => jdec($r['palette']), 'variants' => []];
        $out[$id]['variants'][] = ['thickness' => (int)$r['thickness'], 'price' => $r['price'] === null ? null : (int)$r['price'], 'available' => (int)$r['available'], 'forming' => $r['forming'], 'image' => $r['image']];
    }
    return array_values($out);
}

function colors_all(): array { return rows('SELECT * FROM paving_colors ORDER BY sort'); }

function cats(string $kind = 'zhbi'): array {
    $c = rows('SELECT * FROM categories WHERE visible = 1 AND kind = ? ORDER BY sort', [$kind]);
    foreach ($c as &$x) {
        $x['n'] = (int)val('SELECT COUNT(*) FROM products WHERE category_id = ? AND visible = 1', [$x['id']]);
        $x['min_price'] = (int)val('SELECT MIN(price) FROM products WHERE category_id = ? AND visible = 1 AND price IS NOT NULL', [$x['id']]);
        $img = val("SELECT images FROM products WHERE category_id = ? AND visible = 1 AND images <> '[]' ORDER BY sort LIMIT 1", [$x['id']]);
        $x['img'] = $x['image'] ?: (jdec((string)$img)[0] ?? '');
        $x['url'] = $kind === 'lawn' ? 'gazonnaya-reshetka/' : 'zhbi/' . $x['slug'] . '/';
    }
    return $c;
}

function cat_by_slug(string $slug): ?array {
    foreach (array_merge(cats('zhbi'), cats('lawn')) as $c) if ($c['slug'] === $slug) return $c;
    return null;
}

function products_in(int $catId): array {
    $p = rows('SELECT * FROM products WHERE category_id = ? AND visible = 1 ORDER BY subgroup, sort, name', [$catId]);
    foreach ($p as &$x) product_prepare($x);
    return $p;
}

function product_by_slug(int $catId, string $slug): ?array {
    $p = row('SELECT * FROM products WHERE category_id = ? AND slug = ? AND visible = 1', [$catId, $slug]);
    if ($p) product_prepare($p);
    return $p;
}

function product_prepare(array &$p): void {
    $p['specs_a'] = jdec($p['specs']);
    $p['images_a'] = jdec($p['images']);
    $cat = row('SELECT slug, kind FROM categories WHERE id = ?', [$p['category_id']]);
    $p['url'] = $cat['kind'] === 'lawn' ? 'gazonnaya-reshetka/' : 'zhbi/' . $cat['slug'] . '/' . $p['slug'] . '/';
}

function spec(array $p, string $k): string {
    foreach ($p['specs_a'] ?? jdec($p['specs'] ?? '[]') as [$kk, $v]) if ($kk === $k) return (string)$v;
    return '';
}

/* «С этим берут»: для кольца днище, плита перекрытия, опорное кольцо, люк того же диаметра */
function related_for(array $p): array {
    if (!preg_match('~КСД?-(\d+)~u', $p['name'], $m)) return [];
    $d = $m[1];
    $out = [];
    foreach ([['ПД-' . $d, 'Днище'], ['ПП ' . $d . '-1', 'Плита перекрытия'], ['ПП ' . $d . '-1 с влитым люком', 'Плита с люком']] as [$mark, $why]) {
        $r = row("SELECT * FROM products WHERE visible = 1 AND name LIKE ? LIMIT 1", ['%' . $mark]);
        if ($r) { product_prepare($r); $r['why'] = $why; $out[] = $r; }
    }
    $luk = row("SELECT * FROM products WHERE visible = 1 AND name LIKE '%Люк полимерный средний%' LIMIT 1") ?: null;
    if ($luk) { product_prepare($luk); $luk['why'] = 'Люк'; $out[] = $luk; }
    return $out;
}

function faq(string $scope): array { return rows('SELECT q, a FROM faq WHERE scope = ? ORDER BY sort, id', [$scope]); }

function page_row(string $slug): array {
    return row('SELECT * FROM pages WHERE slug = ?', [$slug]) ?? ['slug' => $slug, 'title' => '', 'h1' => '', 'body' => '', 'seo_title' => '', 'seo_description' => ''];
}

function price_html(?int $price, string $unit = 'шт', bool $from = false): string {
    if (!$price) return '<span class="price ask">Цена по запросу</span>';
    return '<span class="price">' . ($from ? '<small>от</small> ' : '') . number_format($price, 0, ',', ' ') . ' ₽<small>/' . e($unit) . '</small></span>';
}

function price_date_text(): string {
    $d = setting('price_date');
    return $d ? 'Цены на ' . date('d.m.Y', strtotime($d)) . ', без доставки' : 'Цены без доставки';
}

function swatch_css(array $palette): string {
    if (!$palette) return '#ccc';
    $tot = array_sum(array_column($palette, 1)) ?: 1;
    $stops = []; $acc = 0;
    foreach ($palette as [$hex, $w]) { $a = $acc / $tot * 100; $acc += $w; $b = $acc / $tot * 100; $stops[] = "$hex " . round($a) . '% ' . round($b) . '%'; }
    return 'conic-gradient(from 20deg, ' . implode(', ', $stops) . ')';
}

/* короткая подписанная строка характеристик для списков: «Ø 100 см · высота 90 см · 600 кг» */
function spec_line(array $p): string {
    $s = fn($k) => spec($p, $k);
    $num = fn($v) => preg_replace('~\s+~u', ' ', trim($v));
    $out = [];
    if ($s('Внутренний диаметр') !== '') {
        $out[] = 'Ø ' . $num($s('Внутренний диаметр'));
        if ($s('Высота') !== '') $out[] = 'высота ' . $num($s('Высота'));
        if ($s('Толщина стенки') !== '') $out[] = 'стенка ' . $num($s('Толщина стенки'));
        if ($s('Вес') !== '') $out[] = preg_replace_callback('~\d{4,}~', fn($m) => number_format((int)$m[0], 0, ',', ' '), $num($s('Вес')));
    } elseif ($s('Размеры (Д×Ш×В)') !== '') {
        $out[] = $s('Размеры (Д×Ш×В)');
    } elseif ($s('Размеры') !== '') {
        $out[] = str_replace(' см × ', '×', $num($s('Размеры')));
        if ($s('Штук на поддоне') !== '') $out[] = $num($s('Штук на поддоне')) . ' на поддоне';
    } elseif ($s('Класс нагрузки') !== '') {
        preg_match_all('~([АВСD]\d+), до ([\d,]+ т)~u', $s('Класс нагрузки'), $m, PREG_SET_ORDER);
        foreach ($m as $x) $out[] = $x[1] . ' (до ' . $x[2] . ')';
    } elseif ($s('Высота') !== '') {
        $out[] = 'высота ' . $num($s('Высота'));
    }
    return implode(' · ', $out);
}
