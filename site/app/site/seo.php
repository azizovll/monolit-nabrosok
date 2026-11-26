<?php
declare(strict_types=1);

/*
 * Сборка страницы: шаблон + общий макет + мета-теги и микроразметка.
 * $meta: title, description, h1, canonical (путь), image (основа картинки), noindex, schema (массив JSON-LD), crumbs [[название, путь], ...]
 */
function site_page(string $tpl, array $vars, array $meta): bool {
    $path = $meta['canonical'] ?? current_path();
    $meta += ['description' => '', 'image' => '', 'noindex' => false, 'schema' => [], 'crumbs' => []];
    $meta['canonical_url'] = abs_url($path);
    $meta['title_full'] = $meta['title'] . (str_contains($meta['title'], setting('brand')) ? '' : ' · ' . setting('brand'));
    $meta['og_image'] = $meta['image'] ? abs_url($meta['image'] . '-1200.jpg') : abs_url('assets/img/og.jpg');
    $schema = array_merge([schema_org()], $meta['schema']);
    if ($meta['crumbs']) $schema[] = schema_crumbs($meta['crumbs']);
    $meta['schema_json'] = $schema;
    $body = render('site/' . $tpl, $vars + ['meta' => $meta]);
    header('Content-Type: text/html; charset=utf-8');
    echo render('site/layout', ['body' => $body, 'meta' => $meta, 'path' => $path]);
    return true;
}

function current_path(): string {
    $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $b = base_path();
    if ($b !== '' && str_starts_with($uri, $b)) $uri = substr($uri, strlen($b));
    return '/' . ltrim($uri, '/');
}

function schema_org(): array {
    [$lat, $lng] = array_map('floatval', explode(',', setting('geo', '57.8136,28.3496')) + [0, 0]);
    return [
        '@context' => 'https://schema.org', '@type' => ['Organization', 'LocalBusiness'],
        'name' => setting('company'), 'alternateName' => setting('brand'),
        'url' => abs_url('/'), 'logo' => abs_url('assets/img/emblema.png'), 'image' => abs_url('assets/img/og.jpg'),
        'telephone' => setting('phone_tel'), 'email' => setting('email'),
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'ул. Карбышева, 26а', 'addressLocality' => 'Псков', 'addressRegion' => 'Псковская область', 'addressCountry' => 'RU'],
        'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $lat, 'longitude' => $lng],
        'openingHours' => 'Mo-Sa 09:00-18:00',
        'areaServed' => ['Псков', 'Псковская область', 'Новгородская область', 'Ленинградская область'],
    ];
}

function schema_crumbs(array $crumbs): array {
    $items = [];
    foreach (array_merge([['Главная', '/']], $crumbs) as $i => [$name, $p]) {
        $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => abs_url($p)];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

function schema_product(string $name, string $desc, ?string $img, ?int $price, string $path, bool $inStock = true, ?int $high = null): array {
    $s = ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $name, 'description' => $desc ?: $name,
          'brand' => ['@type' => 'Brand', 'name' => setting('brand')], 'url' => abs_url($path)];
    if ($img) $s['image'] = abs_url($img . '-800.jpg');
    if ($price) {
        $offer = ['@type' => $high ? 'AggregateOffer' : 'Offer', 'priceCurrency' => 'RUB', 'availability' => 'https://schema.org/' . ($inStock ? 'InStock' : 'PreOrder'),
                  'seller' => ['@type' => 'Organization', 'name' => setting('company')]];
        if ($high) { $offer['lowPrice'] = $price; $offer['highPrice'] = $high; } else { $offer['price'] = $price; $offer['url'] = abs_url($path); }
        $s['offers'] = $offer;
    }
    return $s;
}

function schema_faq(array $faq): array {
    return ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($f) => [
        '@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f['a'])]], $faq)];
}

/* обрезка описания для meta description */
function meta_desc(string $s, int $len = 160): string {
    $s = trim(preg_replace('~\s+~u', ' ', strip_tags($s)));
    return mb_strlen($s) > $len ? rtrim(mb_substr($s, 0, $len - 1), ' ,.;') . '…' : $s;
}

function sitemap_xml(): void {
    header('Content-Type: application/xml; charset=utf-8');
    $urls = ['/' => 1.0, '/bruschatka/' => 0.9, '/zhbi/' => 0.9, '/gazonnaya-reshetka/' => 0.7, '/raschet/' => 0.7, '/dostavka/' => 0.6, '/o-kompanii/' => 0.5, '/kontakty/' => 0.6];
    foreach (forms_all() as $f) $urls['/' . $f['url']] = 0.9;
    foreach (cats('zhbi') as $c) $urls['/' . $c['url']] = 0.8;
    foreach (rows("SELECT p.slug, c.slug cs FROM products p JOIN categories c ON c.id = p.category_id WHERE p.visible = 1 AND c.kind = 'zhbi'") as $p) $urls["/zhbi/{$p['cs']}/{$p['slug']}/"] = 0.7;
    $lm = date('Y-m-d', strtotime(setting('price_date') ?: 'today'));
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u => $pr) echo '<url><loc>' . e(abs_url($u)) . '</loc><lastmod>' . $lm . '</lastmod><priority>' . $pr . "</priority></url>\n";
    echo '</urlset>';
}

function robots_txt(): void {
    header('Content-Type: text/plain; charset=utf-8');
    if (setting('noindex', '1') === '1') { echo "User-agent: *\nDisallow: /\n"; return; }
    echo "User-agent: *\nDisallow: " . base_path() . "/panel/\nDisallow: " . base_path() . "/api/\nDisallow: /*?utm_\n\nSitemap: " . abs_url('sitemap.xml') . "\n";
}
