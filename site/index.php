<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

/* путь запроса без базового префикса: 'panel/products', 'zhbi/kolca/ks-10-9', '' (главная) */
$uri  = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$base = base_path();
if ($base !== '' && str_starts_with($uri, $base)) $uri = substr($uri, strlen($base));
$path = trim($uri, '/');
$seg  = $path === '' ? [] : explode('/', $path);

if (setting('noindex', '1') === '1') header('X-Robots-Tag: noindex, nofollow');

/* админка */
if (($seg[0] ?? '') === 'panel') {
    require APP_DIR . '/panel/router.php';
    panel_route(array_slice($seg, 1));
    exit;
}

/* публичная часть */
require APP_DIR . '/site/router.php';
site_route($seg, $uri);
