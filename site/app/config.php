<?php
/* Настройки по умолчанию. Секреты (пароль SMTP и т.п.) только в config.local.php, он не попадает в git. */
return [
    'debug' => false,
    'db_file' => DATA_DIR . '/site.sqlite',
    // базовый путь сайта: '' в корне, '/new' в подпапке. null = определить автоматически
    'base_path' => null,
    'smtp' => [
        'host' => 'smtp.yandex.ru',
        'port' => 465,
        'user' => 'monolit-west@yandex.ru',
        'pass' => '',              // пароль приложения Яндекса, задаётся в config.local.php
        'from_name' => 'Сайт Монолит-Вест',
    ],
    'image_widths' => [400, 800, 1200],
    'session_name' => 'mw_panel',
    'session_idle' => 7200,        // выход из админки по бездействию, сек
    'login_max_attempts' => 5,
    'login_lock_minutes' => 15,
    'leads_per_hour_per_ip' => 5,
];
