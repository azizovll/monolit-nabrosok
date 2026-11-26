<?php
declare(strict_types=1);

function price_in($v): ?int {
    $v = preg_replace('~[^\d]~', '', (string)$v);
    return $v === '' ? null : (int)$v;
}

function price_changed_hook(): void {
    set_setting('price_date', date('Y-m-d'));
    cache_clear();
}

function products_list(): void {
    $cat = (int)($_GET['cat'] ?? 0);
    $s = trim((string)($_GET['s'] ?? ''));
    $where = []; $p = [];
    if ($cat) { $where[] = 'p.category_id = ?'; $p[] = $cat; }
    if ($s !== '') { $where[] = '(p.name LIKE ? OR p.slug LIKE ?)'; $p[] = "%$s%"; $p[] = '%' . slugify($s) . '%'; }
    $items = rows('SELECT p.*, c.name cname FROM products p JOIN categories c ON c.id = p.category_id'
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY c.sort, p.subgroup, p.sort, p.name', $p);
    $cats = rows('SELECT id, name FROM categories ORDER BY sort');
    panel_page('Товары', render('panel/products_list', compact('items', 'cats', 'cat', 's')), 'products');
}

/* правка цен прямо в списке */
function products_save_prices(): void {
    if (!is_post()) redirect('panel/products');
    csrf_check();
    $changed = 0;
    foreach ((array)($_POST['price'] ?? []) as $id => $v) {
        $id = (int)$id;
        $old = row('SELECT name, price FROM products WHERE id = ?', [$id]);
        if (!$old) continue;
        $new = price_in($v);
        if ($new === ($old['price'] === null ? null : (int)$old['price'])) continue;
        update('products', ['price' => $new, 'updated_at' => now()], 'id = ?', [$id]);
        audit('price', 'product', $id, $old['name'] . ': ' . ($old['price'] ?? 'по запросу') . ' → ' . ($new ?? 'по запросу'));
        $changed++;
    }
    if ($changed) price_changed_hook();
    flash($changed ? "Сохранено цен: $changed." : 'Цены не менялись.');
    redirect('panel/products' . (!empty($_POST['back']) ? '?' . $_POST['back'] : ''));
}

function product_edit(?int $id): void {
    $p = $id ? row('SELECT * FROM products WHERE id = ?', [$id]) : null;
    if ($id && !$p) { panel_404(); return; }
    $err = '';
    if (is_post()) {
        csrf_check();
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = slugify((string)($_POST['slug'] ?? '')) ?: slugify($name);
        $cat = (int)($_POST['category_id'] ?? 0);
        $specs = [];
        foreach ((array)($_POST['spec_k'] ?? []) as $i => $k) {
            $k = trim((string)$k); $v = trim((string)($_POST['spec_v'][$i] ?? ''));
            if ($k !== '' && $v !== '') $specs[] = [$k, $v];
        }
        $data = [
            'name' => $name, 'slug' => $slug, 'category_id' => $cat,
            'subgroup' => trim((string)($_POST['subgroup'] ?? '')),
            'price' => price_in($_POST['price'] ?? ''),
            'price_unit' => trim((string)($_POST['price_unit'] ?? 'шт')) ?: 'шт',
            'in_stock' => empty($_POST['in_stock']) ? 0 : 1,
            'visible' => empty($_POST['visible']) ? 0 : 1,
            'short' => trim((string)($_POST['short'] ?? '')),
            'description' => trim((string)($_POST['description'] ?? '')),
            'specs' => jenc($specs),
            'seo_title' => trim((string)($_POST['seo_title'] ?? '')),
            'seo_description' => trim((string)($_POST['seo_description'] ?? '')),
            'note' => trim((string)($_POST['note'] ?? '')),
            'sort' => (int)($_POST['sort'] ?? 0),
            'updated_at' => now(),
        ];
        if ($name === '') $err = 'Укажите название.';
        elseif (!val('SELECT 1 FROM categories WHERE id = ?', [$cat])) $err = 'Выберите раздел.';
        elseif (val('SELECT 1 FROM products WHERE slug = ? AND id <> ?', [$slug, $id ?? 0])) $err = "Адрес «{$slug}» уже занят другим товаром.";
        if (!$err) {
            if ($id) {
                $changes = [];
                foreach (['name', 'price', 'in_stock', 'visible', 'slug'] as $k) {
                    $o = $p[$k] === null ? null : (string)$p[$k]; $n = $data[$k] === null ? null : (string)$data[$k];
                    if ($o !== $n) $changes[] = "$k: " . ($o ?? '—') . ' → ' . ($n ?? '—');
                }
                update('products', $data, 'id = ?', [$id]);
                audit('update', 'product', $id, $name . ($changes ? ' (' . implode(', ', $changes) . ')' : ''));
                if ((string)$p['price'] !== (string)$data['price']) price_changed_hook();
            } else {
                $data['images'] = '[]';
                $id = insert('products', $data);
                audit('create', 'product', $id, $name);
            }
            cache_clear();
            flash('Сохранено.');
            redirect('panel/products/' . $id);
        }
        $p = array_merge($p ?? [], $data, ['id' => $id]);
    }
    $p ??= ['id' => null, 'name' => '', 'slug' => '', 'category_id' => (int)($_GET['cat'] ?? 0), 'subgroup' => '', 'price' => null, 'price_unit' => 'шт',
            'in_stock' => 1, 'visible' => 1, 'short' => '', 'description' => '', 'specs' => '[]', 'images' => '[]', 'seo_title' => '', 'seo_description' => '', 'note' => '', 'sort' => 0];
    $cats = rows('SELECT id, name, slug, kind FROM categories ORDER BY sort');
    $subgroups = array_column(rows("SELECT DISTINCT subgroup FROM products WHERE subgroup <> '' ORDER BY subgroup"), 'subgroup');
    panel_page($p['id'] ? $p['name'] : 'Новый товар', render('panel/product_edit', compact('p', 'cats', 'err', 'subgroups')), 'products');
}

function product_delete(int $id): void {
    if (!is_post()) redirect('panel/products/' . $id);
    csrf_check();
    $p = row('SELECT * FROM products WHERE id = ?', [$id]);
    if ($p) {
        foreach (jdec($p['images']) as $b) {
            if (!val('SELECT 1 FROM products WHERE id <> ? AND images LIKE ?', [$id, '%' . $b . '%'])) img_delete($b);
        }
        q('DELETE FROM products WHERE id = ?', [$id]);
        audit('delete', 'product', $id, $p['name']);
        cache_clear();
        flash('Товар удалён.');
    }
    redirect('panel/products');
}

/* фото товара: загрузка, удаление, порядок */
function product_image(int $id): void {
    if (!is_post()) redirect('panel/products/' . $id);
    csrf_check();
    $p = row('SELECT * FROM products WHERE id = ?', [$id]);
    if (!$p) { panel_404(); return; }
    $imgs = jdec($p['images']);
    $act = $_POST['act'] ?? '';
    if ($act === 'upload') {
        $n = 0; $errs = [];
        foreach (upload_files('files') as $f) {
            try {
                $imgs[] = upload_image($f, 'products', $p['slug']);
                $n++;
            } catch (Throwable $t) { $errs[] = $f['name'] . ': ' . $t->getMessage(); }
        }
        if ($n) flash("Загружено фото: $n.");
        foreach ($errs as $er) flash($er, 'err');
    } elseif ($act === 'delete') {
        $b = (string)($_POST['base'] ?? '');
        $imgs = array_values(array_filter($imgs, fn($x) => $x !== $b));
        if (!val('SELECT 1 FROM products WHERE id <> ? AND images LIKE ?', [$id, '%' . $b . '%'])) img_delete($b);
        flash('Фото удалено.');
    } elseif ($act === 'first') {
        $b = (string)($_POST['base'] ?? '');
        if (in_array($b, $imgs, true)) $imgs = array_values(array_merge([$b], array_filter($imgs, fn($x) => $x !== $b)));
        flash('Фото стало главным.');
    }
    update('products', ['images' => jenc($imgs), 'updated_at' => now()], 'id = ?', [$id]);
    audit('images', 'product', $id, $p['name'] . ": $act");
    cache_clear();
    redirect('panel/products/' . $id . '#photos');
}

/* $_FILES[name][] → список файлов */
function upload_files(string $name): array {
    $f = $_FILES[$name] ?? null;
    if (!$f) return [];
    $out = [];
    foreach ((array)$f['name'] as $i => $n) {
        $out[] = ['name' => (string)$n, 'tmp' => (array)$f['tmp_name'] ? ((array)$f['tmp_name'])[$i] : '', 'error' => ((array)$f['error'])[$i], 'size' => ((array)$f['size'])[$i]];
    }
    return array_filter($out, fn($x) => $x['error'] !== UPLOAD_ERR_NO_FILE);
}

function upload_image(array $f, string $subdir, string $name): string {
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('ошибка загрузки (' . $f['error'] . ')');
    if ($f['size'] > 25 * 1048576) throw new RuntimeException('файл больше 25 МБ');
    if (!is_uploaded_file($f['tmp'])) throw new RuntimeException('файл не принят');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) throw new RuntimeException('нужен JPG, PNG или WebP');
    return img_store($f['tmp'], $subdir, $name);
}
