<?php
declare(strict_types=1);

const SETTINGS_FORM = [
    'Контакты' => [
        'phone' => ['Телефон (как показывать)', []],
        'phone_tel' => ['Телефон для звонка', ['hint' => 'В формате +79212152643']],
        'email' => ['Почта (показывается на сайте)', ['type' => 'email']],
        'lead_email' => ['Почта для заявок', ['type' => 'email', 'hint' => 'Сюда приходят письма с заявками']],
        'max_url' => ['Ссылка на чат в MAX', ['hint' => 'Например https://max.ru/u/... Если пусто, кнопка MAX не показывается']],
        'address' => ['Адрес площадки', []],
        'hours' => ['Часы работы', []],
        'geo' => ['Координаты для карты', ['hint' => 'Широта,долгота, например 57.8136,28.3496']],
    ],
    'Реквизиты' => [
        'company' => ['Название организации', []],
        'inn' => ['ИНН', ['inputmode' => 'numeric']],
        'ogrn' => ['ОГРН', ['inputmode' => 'numeric']],
        'delivery_regions' => ['Куда доставляем', []],
    ],
    'Тексты' => [
        'disclaimer' => ['Пометка об ознакомительных цветах (полная)', ['type' => 'textarea', 'rows' => 3]],
        'disclaimer_short' => ['Пометка о цветах (коротко, для подвала)', []],
        'disclaimer_pages' => ['Показывать пометку о цветах на страницах товаров и в подвале', ['type' => 'checkbox', 'hint' => 'В 3D-примерочной пометка показывается всегда']],
    ],
    'Поиск и статистика' => [
        'site_url' => ['Адрес сайта', ['hint' => 'Для ссылок в письмах и карты сайта. Сейчас https://monolitwest.ru/new, после переезда https://monolitwest.ru']],
        'metrika_id' => ['Номер счётчика Яндекс.Метрики', ['inputmode' => 'numeric']],
        'metrika_on' => ['Включить Метрику', ['type' => 'checkbox', 'hint' => 'Пока сайт в /new, лучше выключить, чтобы не портить статистику']],
        'noindex' => ['Закрыть сайт от поисковиков', ['type' => 'checkbox', 'hint' => 'Включено, пока сайт в /new. Снять при переезде в корень']],
    ],
];

function settings_page(): void {
    $err = [];
    if (is_post()) {
        csrf_check();
        foreach (SETTINGS_FORM as $group) foreach ($group as $k => [$label, $o]) {
            $v = ($o['type'] ?? '') === 'checkbox' ? (empty($_POST[$k]) ? '0' : '1') : trim((string)($_POST[$k] ?? ''));
            if (in_array($k, ['email', 'lead_email'], true) && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) { $err[] = "$label: неверный адрес."; continue; }
            if ($k === 'max_url' && $v !== '' && !preg_match('~^https://(web\.)?max\.ru/~', $v)) { $err[] = 'Ссылка MAX должна начинаться с https://max.ru/'; continue; }
            if ($k === 'phone_tel' && $v !== '' && !preg_match('~^\+7\d{10}$~', $v)) { $err[] = 'Телефон для звонка: формат +7 и 10 цифр.'; continue; }
            if ($k === 'site_url') $v = rtrim($v, '/');
            if ($v !== setting($k)) { set_setting($k, $v); audit('setting', 'settings', $k, mb_substr($v, 0, 120)); }
        }
        settings(true);
        cache_clear();
        if ($err) foreach ($err as $er) flash($er, 'err'); else flash('Настройки сохранены.');
        redirect('panel/settings');
    }
    panel_page('Настройки', render('panel/settings', ['form' => SETTINGS_FORM]), 'settings');
}
