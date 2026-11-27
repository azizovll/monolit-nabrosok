<?php
declare(strict_types=1);

const LEAD_STATUSES = ['new' => 'Новая', 'work' => 'В работе', 'done' => 'Выполнена', 'spam' => 'Спам'];
const LEAD_TYPES = [
    'callback' => 'Обратный звонок', 'paving' => 'Расчёт брусчатки', 'product' => 'Заявка на товар',
    'project3d' => 'Проект из 3D', 'contact' => 'Обратная связь', 'calc' => 'Калькулятор',
];

function leads_list(): void {
    $st = (string)($_GET['status'] ?? '');
    $where = isset(LEAD_STATUSES[$st]) ? 'WHERE status = ?' : "WHERE status <> 'spam'";
    $p = isset(LEAD_STATUSES[$st]) ? [$st] : [];
    $items = rows("SELECT * FROM leads $where ORDER BY id DESC LIMIT 500", $p);
    $counts = [];
    foreach (rows('SELECT status, COUNT(*) n FROM leads GROUP BY status') as $r) $counts[$r['status']] = (int)$r['n'];
    panel_page('Заявки', render('panel/leads_list', compact('items', 'st', 'counts')), 'leads');
}

function lead_view(int $id): void {
    $l = row('SELECT * FROM leads WHERE id = ?', [$id]);
    if (!$l) { panel_404(); return; }
    if (is_post()) {
        csrf_check();
        $status = (string)($_POST['status'] ?? $l['status']);
        if (!isset(LEAD_STATUSES[$status])) $status = $l['status'];
        update('leads', ['status' => $status, 'note' => trim((string)($_POST['note'] ?? ''))], 'id = ?', [$id]);
        if ($status !== $l['status']) audit('status', 'lead', $id, LEAD_STATUSES[$l['status']] . ' → ' . LEAD_STATUSES[$status]);
        flash('Заявка обновлена.');
        redirect('panel/leads/' . $id);
    }
    if ($l['status'] === 'new') { q("UPDATE leads SET status = 'work' WHERE id = ?", [$id]); $l['status'] = 'work'; }
    panel_page('Заявка №' . $id, render('panel/lead_view', ['l' => $l]), 'leads');
}

function lead_shot(int $id): void {
    $f = DATA_DIR . '/leads/' . $id . '.jpg';
    if (!is_file($f)) { panel_404(); return; }
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . filesize($f));
    readfile($f);
    exit;
}

/* CSV для Excel: точка с запятой и BOM, чтобы кириллица открылась без настроек */
function leads_export(): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="zayavki-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['№', 'Дата', 'Тип', 'Имя', 'Телефон', 'E-mail', 'Что заказали', 'Страница', 'Источник', 'Статус', 'Письмо', 'Заметка'], ';');
    foreach (rows('SELECT * FROM leads ORDER BY id DESC') as $l) {
        fputcsv($out, [$l['id'], $l['created_at'], LEAD_TYPES[$l['type']] ?? $l['type'], $l['name'], $l['phone'], $l['email'],
            lead_summary($l), $l['page'], lead_source($l), LEAD_STATUSES[$l['status']] ?? $l['status'], $l['mail_ok'] ? 'ушло' : 'не ушло', $l['note']], ';');
    }
    fclose($out);
    exit;
}

function lead_summary(array $l): string {
    $p = jdec($l['payload']);
    $skip = ['name', 'phone', 'email', 'consent'];
    $parts = [];
    foreach ($p as $k => $v) {
        if (in_array($k, $skip, true) || $v === '' || $v === null) continue;
        $parts[] = (LEAD_FIELDS[$k] ?? $k) . ': ' . (is_array($v) ? jenc($v) : $v);
    }
    return implode('; ', $parts);
}

function lead_source(array $l): string {
    $s = jdec($l['source']);
    return implode(', ', array_map(fn($k, $v) => "$k=$v", array_keys($s), $s));
}

const LEAD_FIELDS = [
    'product' => 'Товар', 'qty' => 'Количество', 'form' => 'Форма', 'color' => 'Цвет', 'thickness' => 'Толщина', 'area' => 'Площадь, м²',
    'kerb' => 'Бордюр', 'address' => 'Адрес', 'delivery' => 'Доставка', 'comment' => 'Комментарий', 'topic' => 'Тема', 'total' => 'Сумма, ₽',
    'project' => 'Проект', 'link' => 'Ссылка на проект', 'image' => 'Картинка проекта',
];
