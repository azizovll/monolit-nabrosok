<?php
declare(strict_types=1);

/* общий макет админки */
function panel_page(string $title, string $body, string $active = ''): void {
    $u = panel_user();
    $newLeads = $u ? (int)val("SELECT COUNT(*) FROM leads WHERE status = 'new'") : 0;
    $nav = [
        ''         => 'Сводка',
        'paving'   => 'Брусчатка',
        'products' => 'Товары',
        'leads'    => 'Заявки' . ($newLeads ? ' <b class="badge">' . $newLeads . '</b>' : ''),
        'settings' => 'Настройки',
        'log'      => 'Журнал',
    ];
    if ($u && $u['role'] === 'admin') $nav['users'] = 'Пользователи';
    echo render('panel/layout', compact('title', 'body', 'active', 'u', 'nav'));
}

function field(string $name, string $label, $value = '', array $o = []): string {
    $type = $o['type'] ?? 'text';
    $id = 'f_' . preg_replace('~\W~', '_', $name);
    $attr = '';
    foreach (['placeholder', 'step', 'min', 'max', 'inputmode', 'autocomplete', 'pattern'] as $k) if (isset($o[$k])) $attr .= " $k=\"" . e($o[$k]) . '"';
    if (!empty($o['required'])) $attr .= ' required';
    $hint = isset($o['hint']) ? '<small class="hint">' . $o['hint'] . '</small>' : '';
    if ($type === 'textarea') {
        $in = '<textarea id="' . $id . '" name="' . e($name) . '" rows="' . ($o['rows'] ?? 4) . '"' . $attr . '>' . e($value) . '</textarea>';
    } elseif ($type === 'select') {
        $in = '<select id="' . $id . '" name="' . e($name) . '"' . $attr . '>';
        foreach ($o['options'] as $k => $v) $in .= '<option value="' . e($k) . '"' . ((string)$k === (string)$value ? ' selected' : '') . '>' . e($v) . '</option>';
        $in .= '</select>';
    } elseif ($type === 'checkbox') {
        return '<label class="check"><input type="checkbox" name="' . e($name) . '" value="1"' . ($value ? ' checked' : '') . '> ' . e($label) . '</label>' . $hint;
    } else {
        $in = '<input id="' . $id . '" type="' . $type . '" name="' . e($name) . '" value="' . e($value) . '"' . $attr . '>';
    }
    return '<label class="fld" for="' . $id . '"><span>' . e($label) . '</span>' . $in . $hint . '</label>';
}

function panel_dashboard(): void {
    $leads = rows("SELECT * FROM leads ORDER BY id DESC LIMIT 8");
    $notes = rows("SELECT p.id, p.name, p.note FROM products p WHERE p.note <> '' ORDER BY p.sort");
    $stats = [
        'Новых заявок' => (int)val("SELECT COUNT(*) FROM leads WHERE status = 'new'"),
        'Заявок за 30 дней' => (int)val("SELECT COUNT(*) FROM leads WHERE created_at >= ?", [date('Y-m-d', strtotime('-30 days'))]),
        'Товаров' => (int)val('SELECT COUNT(*) FROM products'),
        'Цен брусчатки' => (int)val('SELECT COUNT(*) FROM paving_prices'),
    ];
    $failed = (int)val("SELECT COUNT(*) FROM leads WHERE mail_ok = 0 AND status <> 'spam'");
    panel_page('Сводка', render('panel/dashboard', compact('leads', 'notes', 'stats', 'failed')), '');
}

function account_page(array $u): void {
    $err = '';
    if (is_post()) {
        csrf_check();
        $cur = (string)($_POST['current'] ?? ''); $new = (string)($_POST['new'] ?? ''); $rep = (string)($_POST['repeat'] ?? '');
        $hash = (string)val('SELECT pass_hash FROM users WHERE id = ?', [$u['id']]);
        if (!password_verify($cur, $hash)) $err = 'Текущий пароль неверный.';
        elseif (mb_strlen($new) < 10) $err = 'Новый пароль должен быть не короче 10 символов.';
        elseif ($new !== $rep) $err = 'Пароли не совпадают.';
        else {
            q('UPDATE users SET pass_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            audit('password', 'user', $u['id']);
            session_regenerate_id(true);
            flash('Пароль изменён.');
            redirect('panel/account');
        }
    }
    panel_page('Мой аккаунт', render('panel/account', ['u' => $u, 'err' => $err]), 'account');
}

function users_page(array $me): void {
    $err = ''; $created = null;
    if (is_post()) {
        csrf_check();
        $act = $_POST['act'] ?? '';
        if ($act === 'create') {
            $login = strtolower(trim((string)($_POST['login'] ?? '')));
            $name = trim((string)($_POST['name'] ?? ''));
            $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'editor';
            if (!preg_match('~^[a-z0-9_.-]{3,32}$~', $login)) $err = 'Логин: латиница, цифры, от 3 символов.';
            elseif (val('SELECT 1 FROM users WHERE login = ?', [$login])) $err = 'Такой логин уже есть.';
            else {
                $pass = bin2hex(random_bytes(6));
                $id = user_create($login, $name ?: $login, $pass, $role);
                audit('create', 'user', $id, $login);
                $created = ['login' => $login, 'pass' => $pass];
            }
        } elseif ($act === 'reset' && ($id = (int)($_POST['id'] ?? 0))) {
            $pass = bin2hex(random_bytes(6));
            q('UPDATE users SET pass_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $id]);
            audit('reset_password', 'user', $id);
            $created = ['login' => (string)val('SELECT login FROM users WHERE id = ?', [$id]), 'pass' => $pass];
        } elseif ($act === 'delete' && ($id = (int)($_POST['id'] ?? 0)) && $id !== (int)$me['id']) {
            q('DELETE FROM users WHERE id = ?', [$id]);
            audit('delete', 'user', $id);
            flash('Пользователь удалён.');
            redirect('panel/users');
        }
    }
    $users = rows('SELECT id, login, name, role, created_at, last_login FROM users ORDER BY id');
    panel_page('Пользователи', render('panel/users', compact('users', 'err', 'created', 'me')), 'users');
}

function audit_page(): void {
    $items = rows('SELECT a.*, u.name uname FROM audit a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 300');
    panel_page('Журнал изменений', render('panel/log', ['items' => $items]), 'log');
}
