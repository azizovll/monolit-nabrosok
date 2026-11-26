<?php
/* Локальный сервер: повторяет правила site/.htaccess. Запуск: bash tools/serve.sh */
$root = realpath(__DIR__ . '/../site');
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (preg_match('~^/(app|data)(/|$)~', $path) || preg_match('~/\.~', $path) || preg_match('~\.(sqlite|db|log|json|sql|md|sh|ini|lock|bak)$~i', $path)) {
    http_response_code(403); exit('Forbidden');
}
$file = $root . $path;
if ($path !== '/' && is_file($file)) return false;   // статику отдаёт встроенный сервер
$_SERVER['SCRIPT_NAME'] = '/index.php';
chdir($root);
require $root . '/index.php';
