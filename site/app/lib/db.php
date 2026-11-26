<?php
declare(strict_types=1);

/* SQLite: одна база в data/site.sqlite. Схема создаётся и обновляется сама (PRAGMA user_version). */

const DB_SCHEMA_VERSION = 1;

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $file = cfg('db_file');
    $pdo = new PDO('sqlite:' . $file, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $ver = (int)$pdo->query('PRAGMA user_version')->fetchColumn();
    if ($ver < DB_SCHEMA_VERSION) db_migrate($pdo, $ver);
    return $pdo;
}

function db_migrate(PDO $pdo, int $from): void {
    if ($from < 1) {
        $pdo->exec(file_get_contents(APP_DIR . '/schema.sql'));
    }
    $pdo->exec('PRAGMA user_version = ' . DB_SCHEMA_VERSION);
}

function q(string $sql, array $p = []): PDOStatement {
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st;
}
function row(string $sql, array $p = []): ?array { $r = q($sql, $p)->fetch(); return $r ?: null; }
function rows(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function val(string $sql, array $p = []) { return q($sql, $p)->fetchColumn(); }

function insert(string $table, array $data): int {
    $cols = array_keys($data);
    q('INSERT INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')', array_values($data));
    return (int)db()->lastInsertId();
}
function update(string $table, array $data, string $where, array $wp = []): void {
    $set = implode(',', array_map(fn($c) => "$c = ?", array_keys($data)));
    q("UPDATE $table SET $set WHERE $where", array_merge(array_values($data), $wp));
}

function set_setting(string $k, string $v): void {
    q('INSERT INTO settings(key, value) VALUES(?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value', [$k, $v]);
}

/* журнал изменений в админке */
function audit(string $action, string $entity, $entityId = null, string $details = ''): void {
    q('INSERT INTO audit(at, user_id, action, entity, entity_id, details) VALUES(?,?,?,?,?,?)',
      [now(), $_SESSION['uid'] ?? null, $action, $entity, $entityId === null ? null : (string)$entityId, $details]);
}

/* после любых правок в админке сбрасываем кэш страниц */
function cache_clear(): void {
    foreach (glob(DATA_DIR . '/cache/*.html') ?: [] as $f) @unlink($f);
}
