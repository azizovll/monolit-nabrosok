<?php
declare(strict_types=1);

require __DIR__ . '/data.php';
require __DIR__ . '/seo.php';
require __DIR__ . '/pages.php';
require __DIR__ . '/lead.php';

function site_route(array $seg, string $uri): void {
    $a = $seg[0] ?? '';
    $n = count($seg);

    if ($a === 'api' && ($seg[1] ?? '') === 'lead') { lead_handle(); return; }
    if ($a === 'sitemap.xml') { sitemap_xml(); return; }
    if ($a === 'robots.txt') { robots_txt(); return; }

    // адреса без завершающего слэша приводим к виду со слэшем (одна каноническая версия)
    if ($n && !str_ends_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') && !str_contains($a, '.')) {
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        redirect('/' . implode('/', $seg) . '/' . ($qs !== '' ? '?' . $qs : ''), 301);
    }

    if (page_cache_get($uri)) return;
    ob_start();
    $ok = match (true) {
        $n === 0                                    => page_home(),
        $a === 'bruschatka' && $n === 1             => page_paving_index(),
        $a === 'bruschatka' && $n === 2             => page_paving_form($seg[1]),
        $a === 'gazonnaya-reshetka' && $n === 1     => page_lawn(),
        $a === 'zhbi' && $n === 1                   => page_zhbi_index(),
        $a === 'zhbi' && $n === 2                   => page_zhbi_cat($seg[1]),
        $a === 'zhbi' && $n === 3                   => page_product($seg[1], $seg[2]),
        $a === 'raschet' && $n === 1                => page_calc(),
        $a === 'dostavka' && $n === 1               => page_delivery(),
        $a === 'o-kompanii' && $n === 1             => page_about(),
        $a === 'kontakty' && $n === 1               => page_contacts(),
        $a === 'politika' && $n === 1               => page_privacy(),
        $a === 'spasibo' && $n === 1                => page_thanks(),
        default                                     => false,
    };
    $html = ob_get_clean();
    if ($ok === false) { site_not_found($uri); return; }
    if ($ok === 'nocache') { echo $html; return; }
    page_cache_put($uri, $html);
    echo $html;
}

/* ---- кэш готовых страниц: сбрасывается при любом сохранении в админке ---- */
function page_cache_key(string $uri): ?string {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return null;
    $q = $_GET;
    foreach (array_keys($q) as $k) if (str_starts_with($k, 'utm_') || in_array($k, ['yclid', 'cvet', 'th'], true)) unset($q[$k]);
    if ($q) return null;
    return DATA_DIR . '/cache/' . md5($uri) . '.html';
}
function page_cache_get(string $uri): bool {
    $f = page_cache_key($uri);
    if (!$f || !is_file($f)) return false;
    header('Content-Type: text/html; charset=utf-8');
    header('X-Cache: HIT');
    readfile($f);
    return true;
}
function page_cache_put(string $uri, string $html): void {
    $f = page_cache_key($uri);
    if ($f) @file_put_contents($f, $html, LOCK_EX);
}

/* перед 404 смотрим таблицу редиректов со старого сайта */
function site_not_found(string $uri): void {
    $p = '/' . trim($uri, '/') . '/';
    $r = row('SELECT * FROM redirects WHERE from_path IN (?, ?)', [$p, rtrim($p, '/')]);
    if ($r) {
        q('UPDATE redirects SET hits = hits + 1 WHERE from_path = ?', [$r['from_path']]);
        header('Location: ' . url($r['to_path']), true, (int)$r['code']);
        exit;
    }
    http_response_code(404);
    site_page('page404', [], ['title' => 'Страница не найдена', 'noindex' => true]);
}
