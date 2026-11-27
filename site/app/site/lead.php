<?php
declare(strict_types=1);

const LEAD_TYPES_PUBLIC = ['callback' => 'Обратный звонок', 'paving' => 'Расчёт брусчатки', 'product' => 'Заявка на товар', 'project3d' => 'Проект из 3D', 'contact' => 'Обратная связь', 'calc' => 'Калькулятор'];
const LEAD_KEYS = ['product', 'qty', 'form', 'color', 'thickness', 'area', 'kerb', 'address', 'delivery', 'comment', 'topic', 'total', 'project', 'link', 'image'];
const LEAD_LABELS = ['product' => 'Товар', 'qty' => 'Количество', 'form' => 'Форма', 'color' => 'Цвет', 'thickness' => 'Толщина', 'area' => 'Площадь, м²',
    'kerb' => 'Бордюр', 'address' => 'Адрес', 'delivery' => 'Доставка', 'comment' => 'Комментарий', 'topic' => 'Тема', 'total' => 'Ориентировочно, ₽', 'project' => 'Проект', 'link' => 'Ссылка на проект', 'image' => 'Картинка проекта'];

function lead_handle(): void {
    $ajax = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    $fail = function (string $msg, int $code = 422) use ($ajax) {
        if ($ajax) json_out(['ok' => false, 'error' => $msg], $code);
        http_response_code($code);
        site_page('lead_error', ['msg' => $msg], ['title' => 'Заявка не отправлена', 'noindex' => true]);
        exit;
    };
    if (!is_post()) $fail('Неверный запрос', 405);

    $in = fn(string $k, int $max = 300) => mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max);
    $type = $in('type', 20);
    if (!isset(LEAD_TYPES_PUBLIC[$type])) $type = 'callback';

    // антиспам: ловушка, время заполнения, номер, согласие, частота
    // порог низкий: живой человек с автозаполнением телефона может отправить быстро, а заявку терять нельзя
    $isBot = $in('website') !== '' || (int)($_POST['elapsed'] ?? 0) < 1200;
    $digits = preg_replace('~\D~', '', $in('phone', 40));
    if (strlen($digits) === 10) $digits = '7' . $digits;
    if (strlen($digits) === 11 && $digits[0] === '8') $digits = '7' . substr($digits, 1);
    if (strlen($digits) !== 11 || $digits[0] !== '7') $fail('Проверьте номер телефона: нужно 11 цифр, например 8 921 215-26-43.');
    if (empty($_POST['consent'])) $fail('Отметьте согласие на обработку данных, без него мы не можем принять заявку.');
    $recent = (int)val('SELECT COUNT(*) FROM leads WHERE ip = ? AND created_at >= ?', [client_ip(), date('Y-m-d H:i:s', time() - 3600)]);
    if ($recent >= cfg('leads_per_hour_per_ip')) $fail('Слишком много заявок подряд. Позвоните нам: ' . setting('phone') . '.', 429);

    $phone = sprintf('+7 (%s) %s-%s-%s', substr($digits, 1, 3), substr($digits, 4, 3), substr($digits, 7, 2), substr($digits, 9, 2));
    $email = $in('email', 120);
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';
    $payload = ['name' => $in('name', 80), 'phone' => $phone, 'email' => $email];
    foreach (LEAD_KEYS as $k) {
        $v = $in($k, $k === 'comment' || $k === 'project' ? 3000 : 300);
        if ($v !== '') $payload[$k] = $v;
    }
    if (isset($payload['link']) && !str_starts_with($payload['link'], abs_url('/'))) unset($payload['link']);
    $text = implode(' ', array_diff_key($payload, ['link' => 1]));
    $hasLinks = (bool)preg_match('~(https?://|www\.|\[url|<a\s)~i', $text);

    $source = [];
    foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'ref'] as $k) if (!empty($_POST[$k])) $source[$k] = mb_substr((string)$_POST[$k], 0, 200);
    $status = ($isBot || $hasLinks) ? 'spam' : 'new';

    $id = insert('leads', [
        'created_at' => now(), 'type' => $type, 'name' => $payload['name'], 'phone' => $phone, 'email' => $email,
        'payload' => jenc($payload), 'page' => $in('page', 300), 'source' => jenc($source), 'ip' => client_ip(), 'status' => $status,
    ]);

    $shot = (string)($_POST['shot'] ?? '');
    if ($status === 'new' && $type === 'project3d' && str_starts_with($shot, 'data:image/jpeg;base64,') && strlen($shot) < 3000000) {
        $bin = base64_decode(substr($shot, 23), true);
        if ($bin !== false && @getimagesizefromstring($bin)) {
            if (!is_dir(DATA_DIR . '/leads')) @mkdir(DATA_DIR . '/leads', 0775, true);
            @file_put_contents(DATA_DIR . '/leads/' . $id . '.jpg', $bin);
            $payload['image'] = 'есть, смотрите в админке';
            update('leads', ['payload' => jenc($payload)], 'id = ?', [$id]);
        }
    }
    if ($status === 'new') {
        [$ok, $err] = lead_send_mail($id, $type, $payload, $in('page', 300), $source);
        update('leads', ['mail_ok' => $ok ? 1 : 0, 'mail_error' => $err], 'id = ?', [$id]);
        if (!$ok) log_line('mail.log', "lead #$id: $err");
    }
    // боту отвечаем как обычно, чтобы он не подбирал обход
    if ($ajax) json_out(['ok' => true, 'id' => $id]);
    redirect('/spasibo/');
}

function lead_send_mail(int $id, string $type, array $p, string $page, array $source): array {
    $subject = 'Заявка с сайта №' . $id . ': ' . LEAD_TYPES_PUBLIC[$type] . ' · ' . ($p['name'] ?: 'без имени') . ' · ' . $p['phone'];
    $lines = [LEAD_TYPES_PUBLIC[$type], '', 'Имя: ' . ($p['name'] ?: '—'), 'Телефон: ' . $p['phone']];
    if ($p['email']) $lines[] = 'E-mail: ' . $p['email'];
    foreach (LEAD_KEYS as $k) if (isset($p[$k])) $lines[] = LEAD_LABELS[$k] . ': ' . $p[$k];
    $lines[] = '';
    $lines[] = 'Страница: ' . ($page ? abs_url($page) : '—');
    if ($source) $lines[] = 'Источник: ' . implode(', ', array_map(fn($k, $v) => "$k=$v", array_keys($source), $source));
    $lines[] = 'Время: ' . date('d.m.Y H:i') . ' (Москва)';
    $lines[] = '';
    $lines[] = 'Все заявки: ' . abs_url('panel/leads/' . $id);
    $body = implode("\n", $lines);
    $to = setting('lead_email') ?: setting('email');
    $reply = $p['email'] ?: '';

    if (cfg('smtp.pass')) {
        try { smtp_send($to, $subject, $body, $reply); return [true, '']; }
        catch (Throwable $t) { $smtpErr = 'SMTP: ' . $t->getMessage(); }
    } else {
        $smtpErr = 'SMTP не настроен (нет пароля приложения)';
    }
    // резерв: почта хостинга
    $headers = 'From: ' . mime_header(cfg('smtp.from_name')) . ' <' . cfg('smtp.user') . ">\r\n" . ($reply ? "Reply-To: $reply\r\n" : '')
             . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64";
    $ok = @mail($to, mime_header($subject), chunk_split(base64_encode($body)), $headers);
    return $ok ? [true, $smtpErr . '; отправлено резервом через mail()'] : [false, $smtpErr . '; mail() тоже не сработал'];
}

function mime_header(string $s): string { return '=?UTF-8?B?' . base64_encode($s) . '?='; }

/* минимальный SMTP-клиент: SSL 465, AUTH LOGIN (Яндекс с паролем приложения) */
function smtp_send(string $to, string $subject, string $body, string $replyTo = ''): void {
    $c = cfg('smtp');
    $fp = @stream_socket_client('ssl://' . $c['host'] . ':' . $c['port'], $errno, $errstr, 15, STREAM_CLIENT_CONNECT,
        stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]));
    if (!$fp) throw new RuntimeException("нет соединения ($errno $errstr)");
    stream_set_timeout($fp, 15);
    $read = function () use ($fp) { $r = ''; while (($l = fgets($fp, 515)) !== false) { $r .= $l; if (isset($l[3]) && $l[3] === ' ') break; } return $r; };
    $cmd = function (string $line, array $okCodes) use ($fp, $read) {
        if ($line !== '') fwrite($fp, $line . "\r\n");
        $r = $read();
        if (!in_array((int)substr($r, 0, 3), $okCodes, true)) throw new RuntimeException(trim(preg_replace('~\s+~', ' ', $r)) ?: 'нет ответа');
        return $r;
    };
    $cmd('', [220]);
    $cmd('EHLO ' . (gethostname() ?: 'localhost'), [250]);
    $cmd('AUTH LOGIN', [334]);
    $cmd(base64_encode($c['user']), [334]);
    $cmd(base64_encode($c['pass']), [235]);
    $cmd('MAIL FROM:<' . $c['user'] . '>', [250]);
    $cmd('RCPT TO:<' . $to . '>', [250, 251]);
    $cmd('DATA', [354]);
    $headers = [
        'Date: ' . date('r'), 'From: ' . mime_header($c['from_name']) . ' <' . $c['user'] . '>', 'To: <' . $to . '>',
        'Subject: ' . mime_header($subject), 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . explode('@', $c['user'])[1] . '>',
        'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: base64',
    ];
    if ($replyTo) $headers[] = 'Reply-To: <' . $replyTo . '>';
    $cmd(implode("\r\n", $headers) . "\r\n\r\n" . rtrim(chunk_split(base64_encode($body), 76, "\r\n")) . "\r\n.", [250]);
    fwrite($fp, "QUIT\r\n");
    fclose($fp);
}
