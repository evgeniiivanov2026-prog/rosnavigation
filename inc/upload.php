<?php
declare(strict_types=1);

function handle_upload(array $file, string $subdir, int $maxBytes = 2_097_152): string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        throw new HttpError('upload', 'Файл не выбран.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new HttpError('upload', 'Не удалось принять файл.');
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        throw new HttpError('upload', 'Файл больше 2 МБ.');
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new HttpError('upload', 'Не удалось принять файл.');
    }
    $mime = mime_content_type($tmp);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!is_string($mime) || !isset($allowed[$mime])) {
        throw new HttpError('upload', 'Нужна картинка JPG, PNG или WebP.');
    }
    $dir = dirname(__DIR__) . '/uploads/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new HttpError('upload', 'Нет папки для файлов.', 500);
    }
    $name = bin2hex(random_bytes(16)) . '.webp';
    $target = $dir . '/' . $name;
    if (!save_as_webp($tmp, $target)) {
        $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $target = $dir . '/' . $name;
        if (!move_uploaded_file($tmp, $target)) {
            throw new HttpError('upload', 'Не удалось сохранить файл.', 500);
        }
    }
    return '/uploads/' . $subdir . '/' . $name;
}

function save_as_webp(string $source, string $target): bool
{
    if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
        return false;
    }
    $raw = file_get_contents($source);
    if ($raw === false) {
        return false;
    }
    $image = @imagecreatefromstring($raw);
    if ($image === false) {
        return false;
    }
    $width = imagesx($image);
    $height = imagesy($image);
    $max = 1600;
    if ($width > $max || $height > $max) {
        $scale = $max / max($width, $height);
        $nw = max(1, (int) round($width * $scale));
        $nh = max(1, (int) round($height * $scale));
        $resized = imagecreatetruecolor($nw, $nh);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $nw, $nh, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }
    $ok = imagewebp($image, $target, 78);
    imagedestroy($image);
    return $ok && is_file($target);
}
