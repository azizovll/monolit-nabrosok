<?php
declare(strict_types=1);

function paving_matrix(): void {
    $ths = array_map('intval', array_column(rows('SELECT DISTINCT thickness FROM paving_prices ORDER BY thickness'), 'thickness')) ?: [60];
    $th = (int)($_GET['th'] ?? $ths[0]);
    if (isset($_GET['add_th'])) { $th = max(40, min(120, (int)$_GET['add_th'])); if (!in_array($th, $ths, true)) $ths[] = $th; sort($ths); }

    if (is_post()) {
        csrf_check();
        $th = (int)($_POST['th'] ?? 60);
        $changes = [];
        $forms = rows('SELECT id, name FROM paving_forms');
        $colors = rows('SELECT id, name FROM paving_colors');
        foreach ($forms as $f) foreach ($colors as $c) {
            $raw = $_POST['price'][$f['id']][$c['id']] ?? null;
            if ($raw === null) continue;
            $new = price_in($raw);
            $avail = empty($_POST['avail'][$f['id']][$c['id']]) ? 0 : 1;
            $old = row('SELECT price, available FROM paving_prices WHERE form_id = ? AND color_id = ? AND thickness = ?', [$f['id'], $c['id'], $th]);
            $label = "{$f['name']} / {$c['name']} / {$th} мм";
            if ($new === null) {
                if ($old) { q('DELETE FROM paving_prices WHERE form_id = ? AND color_id = ? AND thickness = ?', [$f['id'], $c['id'], $th]); $changes[] = "$label: убрано"; }
                continue;
            }
            if (!$old) {
                q('INSERT INTO paving_prices(form_id, color_id, thickness, price, available) VALUES(?,?,?,?,?)', [$f['id'], $c['id'], $th, $new, $avail]);
                $changes[] = "$label: добавлено, $new ₽";
            } elseif ((int)$old['price'] !== $new || (int)$old['available'] !== $avail) {
                q('UPDATE paving_prices SET price = ?, available = ? WHERE form_id = ? AND color_id = ? AND thickness = ?', [$new, $avail, $f['id'], $c['id'], $th]);
                $changes[] = "$label: " . ((int)$old['price'] !== $new ? "{$old['price']} → $new ₽" : '') . ((int)$old['available'] !== $avail ? ($avail ? ' в наличии' : ' нет в наличии') : '');
            }
        }
        foreach ($changes as $ch) audit('price', 'paving', null, $ch);
        if ($changes) price_changed_hook();
        flash($changes ? 'Сохранено изменений: ' . count($changes) . '.' : 'Цены не менялись.');
        redirect('panel/paving?th=' . $th);
    }

    $forms = rows('SELECT * FROM paving_forms ORDER BY sort');
    $colors = rows('SELECT * FROM paving_colors ORDER BY sort');
    $cells = [];
    foreach (rows('SELECT * FROM paving_prices WHERE thickness = ?', [$th]) as $r) $cells[$r['form_id']][$r['color_id']] = $r;
    panel_page('Брусчатка: цены', render('panel/paving_matrix', compact('forms', 'colors', 'cells', 'ths', 'th')), 'paving');
}

function paving_form_edit(int $id): void {
    $f = row('SELECT * FROM paving_forms WHERE id = ?', [$id]);
    if (!$f) { panel_404(); return; }
    if (is_post()) {
        csrf_check();
        $act = $_POST['act'] ?? 'save';
        $imgs = jdec($f['images']);
        if ($act === 'upload') {
            foreach (upload_files('files') as $file) {
                try { $imgs[] = upload_image($file, 'paving', $f['slug']); } catch (Throwable $t) { flash($file['name'] . ': ' . $t->getMessage(), 'err'); }
            }
            update('paving_forms', ['images' => jenc($imgs)], 'id = ?', [$id]);
        } elseif ($act === 'delete_img') {
            $b = (string)($_POST['base'] ?? '');
            update('paving_forms', ['images' => jenc(array_values(array_filter($imgs, fn($x) => $x !== $b)))], 'id = ?', [$id]);
            if (!val('SELECT 1 FROM paving_prices WHERE image = ?', [$b])) img_delete($b);
        } else {
            $specs = [];
            foreach ((array)($_POST['spec_k'] ?? []) as $i => $k) {
                $k = trim((string)$k); $v = trim((string)($_POST['spec_v'][$i] ?? ''));
                if ($k !== '' && $v !== '') $specs[] = [$k, $v];
            }
            $el = [];
            foreach (preg_split('~[;\n,]+~', (string)($_POST['elements'] ?? '')) as $chunk) {
                if (preg_match('~(\d+)\s*[×x*хХ]\s*(\d+)~u', $chunk, $m)) $el[] = [(int)$m[1], (int)$m[2]];
            }
            update('paving_forms', [
                'name' => trim((string)$_POST['name']) ?: $f['name'],
                'short' => trim((string)($_POST['short'] ?? '')),
                'description' => trim((string)($_POST['description'] ?? '')),
                'seo_text' => trim((string)($_POST['seo_text'] ?? '')),
                'seo_title' => trim((string)($_POST['seo_title'] ?? '')),
                'seo_description' => trim((string)($_POST['seo_description'] ?? '')),
                'elements' => jenc($el ?: jdec($f['elements'])),
                'm2_per_pallet' => (float)str_replace(',', '.', (string)($_POST['m2_per_pallet'] ?? '')) ?: null,
                'pallet_kg' => (int)($_POST['pallet_kg'] ?? 0) ?: null,
                'specs' => jenc($specs),
                'visible' => empty($_POST['visible']) ? 0 : 1,
                'sort' => (int)($_POST['sort'] ?? 0),
            ], 'id = ?', [$id]);
        }
        audit($act, 'paving_form', $id, $f['name']);
        cache_clear();
        flash('Сохранено.');
        redirect('panel/paving/form/' . $id);
    }
    panel_page('Форма: ' . $f['name'], render('panel/paving_form', ['f' => $f]), 'paving');
}

function paving_colors_edit(): void {
    if (is_post()) {
        csrf_check();
        foreach ((array)($_POST['name'] ?? []) as $id => $name) {
            $id = (int)$id; $name = trim((string)$name);
            if ($name === '') continue;
            $pal = palette_parse((string)($_POST['palette'][$id] ?? ''));
            $data = ['name' => $name, 'grp' => ($_POST['grp'][$id] ?? '') === 'mix' ? 'mix' : 'mono', 'palette' => jenc($pal), 'sort' => (int)($_POST['sort'][$id] ?? 0)];
            update('paving_colors', $data, 'id = ?', [$id]);
        }
        $new = trim((string)($_POST['new_name'] ?? ''));
        if ($new !== '') {
            $slug = slugify($new);
            if (!val('SELECT 1 FROM paving_colors WHERE slug = ?', [$slug])) {
                insert('paving_colors', ['slug' => $slug, 'name' => $new, 'grp' => ($_POST['new_grp'] ?? '') === 'mix' ? 'mix' : 'mono',
                    'palette' => jenc(palette_parse((string)($_POST['new_palette'] ?? '')) ?: [['#999999', 1]]), 'sort' => 999]);
            }
        }
        audit('update', 'paving_colors', null);
        cache_clear();
        flash('Цвета сохранены.');
        redirect('panel/paving/colors');
    }
    $colors = rows('SELECT * FROM paving_colors ORDER BY sort');
    panel_page('Брусчатка: цвета', render('panel/paving_colors', ['colors' => $colors]), 'paving');
}

/* "#c98a5e 3, #dda670 2" → [["#c98a5e",3],["#dda670",2]] */
function palette_parse(string $s): array {
    $out = [];
    preg_match_all('~#([0-9a-f]{6})(?:\s*[:x×*]?\s*(\d+))?~i', $s, $m, PREG_SET_ORDER);
    foreach ($m as $x) $out[] = ['#' . strtolower($x[1]), max(1, (int)($x[2] ?? 1))];
    return $out;
}
function palette_str(string $json): string {
    return implode(', ', array_map(fn($p) => $p[0] . ' ' . $p[1], jdec($json)));
}
