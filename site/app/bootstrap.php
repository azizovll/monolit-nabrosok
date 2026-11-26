<?php
declare(strict_types=1);

/* Монолит-Вест: общий запуск для сайта, админки и консольных скриптов. */

define('APP_DIR', __DIR__);
define('ROOT_DIR', dirname(__DIR__));          // site/
define('DATA_DIR', ROOT_DIR . '/data');
define('UPLOAD_DIR', ROOT_DIR . '/uploads');
define('IS_CLI', PHP_SAPI === 'cli');

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Moscow');

$config = require APP_DIR . '/config.php';
if (is_file(APP_DIR . '/config.local.php')) {
    $config = array_replace_recursive($config, require APP_DIR . '/config.local.php');
}
$GLOBALS['CONFIG'] = $config;

error_reporting(E_ALL);
ini_set('display_errors', $config['debug'] ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', DATA_DIR . '/logs/php-error.log');

foreach ([DATA_DIR, DATA_DIR . '/logs', DATA_DIR . '/cache', DATA_DIR . '/backups', UPLOAD_DIR] as $d) {
    if (!is_dir($d)) @mkdir($d, 0775, true);
}

require APP_DIR . '/lib/helpers.php';
require APP_DIR . '/lib/db.php';
require APP_DIR . '/lib/images.php';
