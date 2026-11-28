<?php
declare(strict_types=1);

function cfg(string $key, $default = null) {
    $v = $GLOBALS['CONFIG'];
    foreach (explode('.', $key) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) return $default;
        $v = $v[$k];
    }
    return $v;
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/* базовый путь: '' в корне домена, '/new' в подпапке */
function base_path(): string {
    static $b = null;
    if ($b !== null) return $b;
    $b = cfg('base_path');
    if ($b === null) {
        $b = IS_CLI ? '' : rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        if ($b === '.') $b = '';
    }
    return $b;
}

function url(string $path = '/'): string {
    if (preg_match('~^(https?:)?//|^(mailto|tel):|^#~', $path)) return $path;
    return base_path() . '/' . ltrim($path, '/');
}

function abs_url(string $path = '/'): string {
    $host = setting('site_url') ?: ((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path());
    return rtrim($host, '/') . '/' . ltrim($path, '/');
}

/* ссылка на статику с версией по времени изменения файла: браузер не держит старый кэш после обновления */
function asset(string $path): string {
    $f = ROOT_DIR . '/' . ltrim($path, '/');
    $v = is_file($f) ? filemtime($f) : 0;
    return url($path) . ($v ? '?v=' . $v : '');
}

function money($n): string { return number_format((float)$n, 0, ',', ' ') . ' ₽'; }

function plural(int $n, string $one, string $few, string $many): string {
    $n = abs($n) % 100; $n1 = $n % 10;
    if ($n > 10 && $n < 20) return $many;
    if ($n1 > 1 && $n1 < 5) return $few;
    if ($n1 == 1) return $one;
    return $many;
}

function translit(string $s): string {
    static $map = ['а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'j','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya'];
    return strtr(mb_strtolower($s), $map);
}

function slugify(string $s): string {
    $s = translit($s);
    $s = preg_replace('~[^a-z0-9]+~', '-', $s);
    return trim($s, '-');
}

function now(): string { return date('Y-m-d H:i:s'); }

function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }

function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }

function redirect(string $to, int $code = 302): never {
    header('Location: ' . (preg_match('~^https?://~', $to) ? $to : url($to)), true, $code);
    exit;
}

function json_out($data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jdec(?string $s, $default = []) {
    if ($s === null || $s === '') return $default;
    $v = json_decode($s, true);
    return $v === null ? $default : $v;
}

function jenc($v): string { return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }

/* ---- настройки из базы ---- */
function settings(bool $reload = false): array {
    static $s = null;
    if ($s === null || $reload) {
        $s = [];
        try {
            foreach (db()->query('SELECT key, value FROM settings') as $r) $s[$r['key']] = $r['value'];
        } catch (Throwable $t) { $s = []; }
    }
    return $s;
}
function setting(string $k, string $default = ''): string { return settings()[$k] ?? $default; }

/* ---- шаблоны ---- */
function render(string $tpl, array $vars = []): string {
    extract($vars, EXTR_SKIP);
    ob_start();
    include APP_DIR . '/views/' . $tpl . '.php';
    return ob_get_clean();
}

/* ---- сессия, CSRF, флеш-сообщения ---- */
function session_start_safe(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name(cfg('session_name'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() . '/',
        'secure' => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrf_token(): string {
    session_start_safe();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    session_start_safe();
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '');
    if (!is_string($t) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
        http_response_code(400);
        exit('Сессия устарела. Обновите страницу и попробуйте ещё раз.');
    }
}

function flash(?string $msg = null, string $type = 'ok') {
    session_start_safe();
    if ($msg !== null) { $_SESSION['flash'][] = [$type, $msg]; return null; }
    $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']);
    return $f;
}

function log_line(string $file, string $line): void {
    @file_put_contents(DATA_DIR . '/logs/' . $file, '[' . now() . '] ' . $line . "\n", FILE_APPEND | LOCK_EX);
}

/* Простая разметка для текстов из админки: ## заголовок, ### подзаголовок, - список, **жирный**, [текст](ссылка), пустая строка = новый абзац */
function md(string $s): string {
    $s = str_replace("\r\n", "\n", trim($s));
    if ($s === '') return '';
    if (preg_match('~^\s*<(p|h2|h3|ul|ol|div)[\s>]~i', $s)) return $s;   // уже HTML (например, политика со старого сайта)
    $inline = function (string $t): string {
        $t = e($t);
        $t = preg_replace('~\*\*(.+?)\*\*~u', '<strong>$1</strong>', $t);
        $t = preg_replace_callback('~\[([^\]]+)\]\(((?:https?://|/|tel:|mailto:)[^)\s]+)\)~u', fn($m) => '<a href="' . (str_starts_with($m[2], '/') ? e(url($m[2])) : $m[2]) . '">' . $m[1] . '</a>', $t);
        return $t;
    };
    $out = '';
    foreach (preg_split("~\n{2,}~", $s) as $block) {
        $lines = explode("\n", trim($block));
        $buf = [];
        $flush = function () use (&$buf, &$out, $inline) { if ($buf) { $out .= '<p>' . implode('<br>', array_map($inline, $buf)) . "</p>\n"; $buf = []; } };
        $list = [];
        $flushList = function () use (&$list, &$out, $inline) { if ($list) { $out .= '<ul>' . implode('', array_map(fn($x) => '<li>' . $inline($x) . '</li>', $list)) . "</ul>\n"; $list = []; } };
        foreach ($lines as $ln) {
            if (preg_match('~^(#{2,3})\s+(.+)$~u', $ln, $m)) { $flush(); $flushList(); $h = strlen($m[1]); $out .= "<h$h>" . $inline($m[2]) . "</h$h>\n"; }
            elseif (preg_match('~^[-•*]\s+(.+)$~u', $ln, $m)) { $flush(); $list[] = $m[1]; }
            else { $flushList(); $buf[] = $ln; }
        }
        $flush(); $flushList();
    }
    return $out;
}
