<?php
declare(strict_types=1);
/*
 * Пользователи админки из консоли.
 *   tools/php.sh tools/user.php add <логин> "<Имя>" [admin|editor]   создать, пароль печатается один раз
 *   tools/php.sh tools/user.php reset <логин>                          новый пароль
 *   tools/php.sh tools/user.php list
 */
require __DIR__ . '/../site/app/bootstrap.php';
require APP_DIR . '/panel/auth.php';

[$_, $cmd, $login, $name, $role] = array_pad($argv, 5, null);
switch ($cmd) {
    case 'add':
        if (!$login || !preg_match('~^[a-z0-9_.-]{3,32}$~', $login)) exit("Логин: латиница, цифры, от 3 символов\n");
        if (val('SELECT 1 FROM users WHERE login = ?', [$login])) exit("Такой логин уже есть\n");
        $pass = bin2hex(random_bytes(6));
        user_create($login, $name ?: $login, $pass, $role === 'admin' ? 'admin' : 'editor');
        echo "Создан $login, пароль: $pass\n";
        break;
    case 'reset':
        $pass = bin2hex(random_bytes(6));
        q('UPDATE users SET pass_hash = ? WHERE login = ?', [password_hash($pass, PASSWORD_DEFAULT), $login]);
        echo "Новый пароль для $login: $pass\n";
        break;
    case 'list':
        foreach (rows('SELECT login, name, role, last_login FROM users') as $u) echo "{$u['login']}\t{$u['name']}\t{$u['role']}\t{$u['last_login']}\n";
        break;
    default:
        echo "add <логин> \"<Имя>\" [admin|editor] | reset <логин> | list\n";
}
