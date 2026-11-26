<?php
declare(strict_types=1);

function panel_user(): ?array {
    static $u = false;
    if ($u !== false) return $u;
    session_start_safe();
    $u = null;
    if (!empty($_SESSION['uid'])) {
        if (time() - ($_SESSION['seen'] ?? 0) > cfg('session_idle')) {
            $_SESSION = [];
            session_regenerate_id(true);
            return null;
        }
        $_SESSION['seen'] = time();
        $u = row('SELECT id, login, name, role FROM users WHERE id = ?', [$_SESSION['uid']]);
    }
    return $u;
}

function panel_require(?string $role = null): array {
    $u = panel_user();
    if (!$u) redirect('panel/login?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? ''));
    if ($role && $u['role'] !== $role) { http_response_code(403); exit('Недостаточно прав'); }
    return $u;
}

function login_locked(): bool {
    $since = date('Y-m-d H:i:s', time() - cfg('login_lock_minutes') * 60);
    q('DELETE FROM login_attempts WHERE at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
    return (int)val('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND at >= ?', [client_ip(), $since]) >= cfg('login_max_attempts');
}

function panel_login_page(): void {
    session_start_safe();
    $err = '';
    if (is_post()) {
        csrf_check();
        if (login_locked()) {
            $err = 'Слишком много попыток. Попробуйте через ' . cfg('login_lock_minutes') . ' минут.';
        } else {
            $login = trim((string)($_POST['login'] ?? ''));
            $u = row('SELECT * FROM users WHERE login = ?', [$login]);
            if ($u && password_verify((string)($_POST['password'] ?? ''), $u['pass_hash'])) {
                session_regenerate_id(true);
                $_SESSION['uid'] = (int)$u['id'];
                $_SESSION['seen'] = time();
                q('UPDATE users SET last_login = ? WHERE id = ?', [now(), $u['id']]);
                q('DELETE FROM login_attempts WHERE ip = ?', [client_ip()]);
                if (password_needs_rehash($u['pass_hash'], PASSWORD_DEFAULT)) q('UPDATE users SET pass_hash = ? WHERE id = ?', [password_hash((string)$_POST['password'], PASSWORD_DEFAULT), $u['id']]);
                $next = (string)($_GET['next'] ?? '');
                $ok = $next !== '' && str_starts_with($next, base_path() . '/panel') && !str_contains($next, '//');
                header('Location: ' . ($ok ? $next : url('panel/')));
                exit;
            }
            q('INSERT INTO login_attempts(ip, at) VALUES(?, ?)', [client_ip(), now()]);
            usleep(400000);
            $err = 'Неверный логин или пароль.';
        }
    }
    echo render('panel/login', ['err' => $err]);
}

function panel_logout(): void {
    session_start_safe();
    if (is_post()) { csrf_check(); $_SESSION = []; session_regenerate_id(true); }
    redirect('panel/login');
}

function user_create(string $login, string $name, string $password, string $role = 'editor'): int {
    return insert('users', ['login' => $login, 'name' => $name, 'pass_hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role, 'created_at' => now()]);
}
