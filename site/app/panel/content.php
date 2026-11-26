<?php
declare(strict_types=1);

const FAQ_SCOPES = ['home' => 'Главная', 'paving' => 'Брусчатка', 'zhbi' => 'ЖБИ'];
const PAGE_NAMES = ['dostavka' => 'Доставка и оплата', 'o-kompanii' => 'О компании', 'kontakty' => 'Контакты', 'politika' => 'Политика конфиденциальности'];

function content_page(array $s): void {
    $tab = $s[0] ?? 'cats';
    match ($tab) {
        'cats' => content_cats((int)($s[1] ?? 0)),
        'pages' => content_pages((string)($s[1] ?? '')),
        'faq' => content_faq(),
        'redirects' => content_redirects(),
        default => panel_404(),
    };
}

function content_tabs(string $on): string {
    $t = ['cats' => 'Разделы каталога', 'pages' => 'Страницы', 'faq' => 'Вопросы и ответы', 'redirects' => 'Редиректы'];
    $h = '<div class="tabs" style="margin-bottom:16px">';
    foreach ($t as $k => $v) $h .= '<a class="' . ($k === $on ? 'on' : '') . '" href="' . e(url('panel/content/' . $k)) . '">' . e($v) . '</a>';
    return $h . '</div><p class="muted small">Разметка текстов: <code>## Заголовок</code>, <code>- пункт списка</code>, <code>**жирный**</code>, пустая строка = новый абзац.</p>';
}

function content_cats(int $id): void {
    if ($id) {
        $c = row('SELECT * FROM categories WHERE id = ?', [$id]);
        if (!$c) { panel_404(); return; }
        if (is_post()) {
            csrf_check();
            $data = [];
            foreach (['name', 'h1', 'intro', 'seo_text', 'seo_title', 'seo_description'] as $k) $data[$k] = trim((string)($_POST[$k] ?? ''));
            $data['visible'] = empty($_POST['visible']) ? 0 : 1;
            $data['sort'] = (int)($_POST['sort'] ?? 0);
            if ($data['name'] === '') $data['name'] = $c['name'];
            update('categories', $data, 'id = ?', [$id]);
            audit('update', 'category', $id, $data['name']);
            cache_clear(); flash('Сохранено.'); redirect('panel/content/cats/' . $id);
        }
        $f = '<form method="post" class="card form" data-dirty-guard>' . csrf_field()
           . '<div class="grid2">' . field('name', 'Название в меню и списках', $c['name']) . field('h1', 'Заголовок страницы (H1)', $c['h1']) . '</div>'
           . field('intro', 'Вводный текст под заголовком', $c['intro'], ['type' => 'textarea', 'rows' => 3])
           . field('seo_text', 'Текст для поиска (внизу страницы)', $c['seo_text'], ['type' => 'textarea', 'rows' => 10])
           . '<details class="seo" open><summary>SEO</summary>' . field('seo_title', 'Title', $c['seo_title'], ['hint' => 'Если пусто: «Название в Пскове от производителя, цена от …»'])
           . field('seo_description', 'Description', $c['seo_description'], ['type' => 'textarea', 'rows' => 2]) . '</details>'
           . '<div class="row">' . field('visible', 'Показывать на сайте', (int)$c['visible'], ['type' => 'checkbox']) . field('sort', 'Порядок', $c['sort'], ['type' => 'number']) . '</div>'
           . '<button class="btn">Сохранить</button> <a class="small" href="' . e(url('panel/content/cats')) . '">← к разделам</a></form>';
        panel_page('Раздел: ' . $c['name'], content_tabs('cats') . $f, 'content');
        return;
    }
    $h = content_tabs('cats') . '<div class="table-wrap"><table class="tbl"><thead><tr><th>Раздел</th><th>Тип</th><th>Товаров</th><th></th></tr></thead><tbody>';
    foreach (rows('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) n FROM categories c ORDER BY sort') as $c) {
        $h .= '<tr><td><b>' . e($c['name']) . '</b>' . ($c['visible'] ? '' : ' <span class="tag">скрыт</span>') . '</td><td class="small muted">' . ($c['kind'] === 'lawn' ? 'газонная решётка' : 'ЖБИ') . '</td><td>' . $c['n'] . '</td>'
            . '<td><a href="' . e(url('panel/content/cats/' . $c['id'])) . '">тексты</a> · <a href="' . e(url('panel/products?cat=' . $c['id'])) . '">товары</a></td></tr>';
    }
    $h .= '</tbody></table></div><p class="muted small">Тексты форм брусчатки правятся в разделе <a href="' . e(url('panel/paving')) . '">Брусчатка</a> → форма.</p>';
    panel_page('Тексты', $h, 'content');
}

function content_pages(string $slug): void {
    if ($slug && isset(PAGE_NAMES[$slug])) {
        $pg = page_row_admin($slug);
        if (is_post()) {
            csrf_check();
            $data = ['slug' => $slug, 'updated_at' => now()];
            foreach (['title', 'h1', 'body', 'seo_title', 'seo_description'] as $k) $data[$k] = trim((string)($_POST[$k] ?? ''));
            q('INSERT OR REPLACE INTO pages(slug, title, h1, body, seo_title, seo_description, updated_at) VALUES(:slug, :title, :h1, :body, :seo_title, :seo_description, :updated_at)', $data);
            audit('update', 'page', $slug, PAGE_NAMES[$slug]);
            cache_clear(); flash('Сохранено.'); redirect('panel/content/pages/' . $slug);
        }
        $f = '<form method="post" class="card form" data-dirty-guard>' . csrf_field()
           . '<p class="small"><a href="' . e(url($slug . '/')) . '" target="_blank" rel="noopener">Открыть на сайте ↗</a></p>'
           . '<div class="grid2">' . field('title', 'Название', $pg['title']) . field('h1', 'Заголовок на странице (H1)', $pg['h1']) . '</div>'
           . field('body', 'Текст', $pg['body'], ['type' => 'textarea', 'rows' => 18, 'hint' => $slug === 'kontakty' ? 'Телефон, адрес и часы берутся из Настроек. Здесь можно добавить, как проехать.' : ''])
           . '<details class="seo"><summary>SEO</summary>' . field('seo_title', 'Title', $pg['seo_title']) . field('seo_description', 'Description', $pg['seo_description'], ['type' => 'textarea', 'rows' => 2]) . '</details>'
           . '<button class="btn">Сохранить</button> <a class="small" href="' . e(url('panel/content/pages')) . '">← к страницам</a></form>';
        panel_page('Страница: ' . PAGE_NAMES[$slug], content_tabs('pages') . $f, 'content');
        return;
    }
    $h = content_tabs('pages') . '<section class="card"><ul class="list">';
    foreach (PAGE_NAMES as $k => $v) $h .= '<li><a href="' . e(url('panel/content/pages/' . $k)) . '"><b>' . e($v) . '</b><small class="muted">/' . e($k) . '/</small></a></li>';
    panel_page('Тексты', $h . '</ul></section>', 'content');
}

function page_row_admin(string $slug): array {
    return row('SELECT * FROM pages WHERE slug = ?', [$slug]) ?? ['slug' => $slug, 'title' => PAGE_NAMES[$slug], 'h1' => PAGE_NAMES[$slug], 'body' => '', 'seo_title' => '', 'seo_description' => ''];
}

function content_faq(): void {
    if (is_post()) {
        csrf_check();
        foreach ((array)($_POST['q'] ?? []) as $id => $qq) {
            $id = (int)$id; $qq = trim((string)$qq); $aa = trim((string)($_POST['a'][$id] ?? ''));
            if (!empty($_POST['del'][$id]) || ($qq === '' && $aa === '')) { q('DELETE FROM faq WHERE id = ?', [$id]); continue; }
            update('faq', ['q' => $qq, 'a' => $aa, 'scope' => isset(FAQ_SCOPES[$_POST['scope'][$id] ?? '']) ? $_POST['scope'][$id] : 'home', 'sort' => (int)($_POST['sort'][$id] ?? 0)], 'id = ?', [$id]);
        }
        $nq = trim((string)($_POST['new_q'] ?? '')); $na = trim((string)($_POST['new_a'] ?? ''));
        if ($nq !== '' && $na !== '') insert('faq', ['scope' => isset(FAQ_SCOPES[$_POST['new_scope'] ?? '']) ? $_POST['new_scope'] : 'home', 'q' => $nq, 'a' => $na, 'sort' => 999]);
        audit('update', 'faq', null);
        cache_clear(); flash('Вопросы сохранены.'); redirect('panel/content/faq');
    }
    $h = content_tabs('faq') . '<form method="post" class="form" data-dirty-guard>' . csrf_field();
    foreach (FAQ_SCOPES as $sc => $name) {
        $h .= '<section class="card"><h2>' . e($name) . '</h2>';
        foreach (rows('SELECT * FROM faq WHERE scope = ? ORDER BY sort, id', [$sc]) as $f) {
            $h .= '<div class="faq-row">' . field("q[{$f['id']}]", 'Вопрос', $f['q']) . field("a[{$f['id']}]", 'Ответ', $f['a'], ['type' => 'textarea', 'rows' => 3])
                . '<div class="row"><select name="scope[' . $f['id'] . ']" style="width:auto">' . implode('', array_map(fn($k, $v) => '<option value="' . $k . '"' . ($k === $sc ? ' selected' : '') . '>' . e($v) . '</option>', array_keys(FAQ_SCOPES), FAQ_SCOPES)) . '</select>'
                . '<input type="number" name="sort[' . $f['id'] . ']" value="' . (int)$f['sort'] . '" class="num-s" aria-label="Порядок">'
                . '<label class="check"><input type="checkbox" name="del[' . $f['id'] . ']" value="1"> удалить</label></div><hr></div>';
        }
        $h .= '</section>';
    }
    $h .= '<section class="card"><h2>Новый вопрос</h2>' . field('new_q', 'Вопрос', '') . field('new_a', 'Ответ', '', ['type' => 'textarea', 'rows' => 3])
        . field('new_scope', 'Где показывать', 'home', ['type' => 'select', 'options' => FAQ_SCOPES]) . '</section><div class="sticky-save"><button class="btn">Сохранить вопросы</button></div></form>';
    panel_page('Тексты', $h, 'content');
}

function content_redirects(): void {
    if (is_post()) {
        csrf_check();
        if (!empty($_POST['del'])) { q('DELETE FROM redirects WHERE from_path = ?', [(string)$_POST['del']]); flash('Редирект удалён.'); }
        $from = '/' . trim(rawurldecode(trim((string)($_POST['from'] ?? ''))), '/') . '/';
        $to = '/' . ltrim(trim((string)($_POST['to'] ?? '')), '/');
        if (!empty($_POST['from']) && $to !== '/' . '' && $from !== '//') {
            q('INSERT OR REPLACE INTO redirects(from_path, to_path, code, hits) VALUES(?, ?, 301, 0)', [$from, $to]);
            audit('create', 'redirect', null, "$from → $to"); flash('Редирект добавлен.');
        }
        redirect('panel/content/redirects');
    }
    $items = rows('SELECT * FROM redirects ORDER BY hits DESC, from_path LIMIT 500');
    $h = content_tabs('redirects') . '<p class="muted small">Старые адреса monolitwest.ru, которые ведут на новые страницы (301). Работают после переезда сайта в корень. «Переходов» показывает, сколько раз ими пользовались.</p>'
       . '<form method="post" class="card form row">' . csrf_field() . '<input name="from" placeholder="Старый адрес, например /contacts/" required style="flex:1;min-width:220px"><input name="to" placeholder="Новый, например /kontakty/" required style="flex:1;min-width:220px"><button class="btn">Добавить</button></form>'
       . '<div class="table-wrap"><table class="tbl"><thead><tr><th>Старый адрес</th><th>Новый</th><th>Переходов</th><th></th></tr></thead><tbody>';
    foreach ($items as $r) $h .= '<tr><td class="small">' . e($r['from_path']) . '</td><td class="small">' . e($r['to_path']) . '</td><td>' . (int)$r['hits'] . '</td><td><form method="post" data-confirm="Удалить редирект?">' . csrf_field() . '<input type="hidden" name="del" value="' . e($r['from_path']) . '"><button class="link small danger">удалить</button></form></td></tr>';
    panel_page('Тексты', $h . '</tbody></table></div>', 'content');
}
