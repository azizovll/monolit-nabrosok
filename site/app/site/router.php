<?php
declare(strict_types=1);

/* Публичная часть. Страницы сайта подключаются на следующем шаге, пока главная-заглушка и 404 с редиректами. */

function site_route(array $seg, string $uri): void {
    if (!$seg) {
        echo '<!doctype html><meta charset="utf-8"><title>Монолит-Вест</title><p>Сайт в разработке. <a href="' . e(url('panel/')) . '">Админка</a></p>';
        return;
    }
    site_not_found($uri);
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
    echo '<!doctype html><meta charset="utf-8"><title>Страница не найдена</title><p>Страница не найдена. <a href="' . e(url('/')) . '">На главную</a></p>';
}
