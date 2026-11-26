<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/common.php';
require __DIR__ . '/products.php';
require __DIR__ . '/paving.php';
require __DIR__ . '/leads.php';
require __DIR__ . '/settings.php';

function panel_route(array $s): void {
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
    $a = $s[0] ?? '';
    if ($a === 'login')  { panel_login_page(); return; }
    if ($a === 'logout') { panel_logout(); return; }

    $u = panel_require();
    $id = isset($s[1]) && ctype_digit($s[1]) ? (int)$s[1] : null;

    match (true) {
        $a === ''                                   => panel_dashboard(),
        $a === 'products' && !isset($s[1])          => products_list(),
        $a === 'products' && ($s[1] ?? '') === 'new' => product_edit(null),
        $a === 'products' && ($s[1] ?? '') === 'prices' => products_save_prices(),
        $a === 'products' && $id && !isset($s[2])   => product_edit($id),
        $a === 'products' && $id && ($s[2] ?? '') === 'delete' => product_delete($id),
        $a === 'products' && $id && ($s[2] ?? '') === 'image'  => product_image($id),
        $a === 'paving' && !isset($s[1])            => paving_matrix(),
        $a === 'paving' && ($s[1] ?? '') === 'form' && isset($s[2]) => paving_form_edit((int)$s[2]),
        $a === 'paving' && ($s[1] ?? '') === 'colors' => paving_colors_edit(),
        $a === 'leads' && !isset($s[1])             => leads_list(),
        $a === 'leads' && ($s[1] ?? '') === 'export' => leads_export(),
        $a === 'leads' && $id                       => lead_view($id),
        $a === 'settings'                           => settings_page(),
        $a === 'account'                            => account_page($u),
        $a === 'users'                              => users_page(panel_require('admin')),
        $a === 'log'                                => audit_page(),
        default                                     => panel_404(),
    };
}

function panel_404(): void {
    http_response_code(404);
    panel_page('Не найдено', '<p>Такой страницы в админке нет.</p>');
}
