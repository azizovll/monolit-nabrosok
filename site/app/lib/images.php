<?php
declare(strict_types=1);

/*
 * Картинки: из исходника делаем ширины 400/800/1200 в WebP и JPEG.
 * В базе храним только «основу» пути, например uploads/products/ks-10-9,
 * файлы: ks-10-9-400.webp, ks-10-9-400.jpg, ... (больше исходника не растягиваем).
 */

function img_store(string $src, string $subdir, string $name): string {
    $info = @getimagesize($src);
    if (!$info) throw new RuntimeException('Файл не похож на картинку');
    [$w, $h, $type] = $info;
    $im = match ($type) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($src),
        IMAGETYPE_PNG  => imagecreatefrompng($src),
        IMAGETYPE_WEBP => imagecreatefromwebp($src),
        IMAGETYPE_GIF  => imagecreatefromgif($src),
        default => throw new RuntimeException('Поддерживаются JPG, PNG, WebP'),
    };
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) $im = img_fix_orientation($im, $src);
    $w = imagesx($im); $h = imagesy($im);

    $dir = UPLOAD_DIR . '/' . trim($subdir, '/');
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = slugify($name) ?: bin2hex(random_bytes(4));
    $base = $dir . '/' . $name;
    $i = 1;
    while (is_file($base . '-400.webp')) { $base = $dir . '/' . $name . '-' . (++$i); }

    $made = [];
    foreach (cfg('image_widths') as $tw) {
        if ($made && $tw > $w) break;                      // не растягиваем больше исходника
        $nw = min($tw, $w); $nh = (int)round($h * $nw / $w);
        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // прозрачный фон PNG делаем белым
        imagealphablending($dst, true);
        imagecopyresampled($dst, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagewebp($dst, "$base-$tw.webp", 80);
        imagejpeg($dst, "$base-$tw.jpg", 82);
        imagedestroy($dst);
        $made[] = $tw;
    }
    imagedestroy($im);
    file_put_contents($base . '.json', jenc(['w' => $w, 'h' => $h, 'widths' => $made]));
    return 'uploads/' . trim($subdir, '/') . '/' . basename($base);
}

function img_fix_orientation($im, string $src) {
    $exif = @exif_read_data($src);
    $o = $exif['Orientation'] ?? 1;
    return match ((int)$o) { 3 => imagerotate($im, 180, 0), 6 => imagerotate($im, -90, 0), 8 => imagerotate($im, 90, 0), default => $im };
}

function img_meta(string $base): array {
    static $cache = [];
    if (!isset($cache[$base])) $cache[$base] = jdec(@file_get_contents(ROOT_DIR . '/' . $base . '.json'), ['w' => 800, 'h' => 600, 'widths' => [400, 800]]);
    return $cache[$base];
}

/* <picture> с WebP и JPEG, размерами и ленивой загрузкой */
function picture(?string $base, string $alt, string $sizes = '(max-width: 700px) 100vw, 400px', array $attr = []): string {
    if (!$base) return '<div class="noimg" role="img" aria-label="' . e($alt) . '"></div>';
    $m = img_meta($base);
    $ws = $m['widths'] ?: [400];
    $set = fn($ext) => implode(', ', array_map(fn($w) => url("$base-$w.$ext") . ' ' . min($w, $m['w']) . 'w', $ws));
    $mid = in_array(800, $ws) ? 800 : end($ws);
    $h = (int)round($m['h'] * 800 / max(1, $m['w']));
    $lazy = ($attr['eager'] ?? false) ? 'eager' : 'lazy';
    unset($attr['eager']);
    $extra = '';
    foreach ($attr as $k => $v) $extra .= ' ' . $k . '="' . e($v) . '"';
    return '<picture><source type="image/webp" srcset="' . $set('webp') . '" sizes="' . e($sizes) . '">'
         . '<img src="' . url("$base-$mid.jpg") . '" srcset="' . $set('jpg') . '" sizes="' . e($sizes) . '" width="800" height="' . $h . '" alt="' . e($alt) . '" loading="' . $lazy . '" decoding="async"' . $extra . '></picture>';
}

function img_delete(string $base): void {
    if (!str_starts_with($base, 'uploads/')) return;
    foreach (glob(ROOT_DIR . '/' . $base . '-*.{webp,jpg}', GLOB_BRACE) ?: [] as $f) @unlink($f);
    @unlink(ROOT_DIR . '/' . $base . '.json');
}
