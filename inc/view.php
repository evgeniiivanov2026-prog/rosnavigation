<?php
declare(strict_types=1);

function logo_html(bool $withCaption = true): string
{
    $caption = $withCaption
        ? '<span class="logo__meta"><span class="logo__word">РосНавигация</span><span class="logo__sub">На 15-м</span></span>'
        : '<span class="logo__word">РосНавигация</span>';
    return '<span class="logo__mark" aria-hidden="true">'
        . '<svg viewBox="0 0 32 32"><rect x="1.25" y="1.25" width="29.5" height="29.5" rx="8" fill="#121A24"/>'
        . '<path d="M8 23.5 16 7.5 24 23.5" fill="none" stroke="#FF7A1A" stroke-width="1.7" stroke-linejoin="round"/>'
        . '<path d="M16 13.2v7.2" stroke="#F2F5F8" stroke-width="1.4" stroke-linecap="round" stroke-dasharray="1.4 2.2"/>'
        . '<circle cx="16" cy="24.6" r="1.2" fill="#FFC933"/></svg></span>' . $caption;
}

function holiday(): ?string
{
    if (date('n') === 5 && date('j') === 9) {
        return 'victory';
    }
    return null;
}

function shield(?string $route): string
{
    $route = trim((string) $route);
    if ($route === '') {
        return '';
    }
    $kind = preg_match('/^[РPрp]/u', $route) ? 'r' : 'm';
    return '<span class="shield shield--' . $kind . '">' . e($route) . '</span>';
}

function status_pill(string $status): string
{
    $mod = match ($status) {
        'on_air' => 'ok',
        'on_route' => 'brand',
        'need_help' => 'danger',
        default => 'muted',
    };
    return '<span class="status status--' . $mod . '">' . e(status_label($status)) . '</span>';
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function honeypot_field(): string
{
    return '<div class="hp"><label>Сайт<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $letters = '';
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $letters .= mb_substr($part, 0, 1);
        if (mb_strlen($letters) >= 2) {
            break;
        }
    }
    return mb_strtoupper($letters !== '' ? $letters : 'Р');
}

function avatar_html(?array $user, string $class = 'avatar'): string
{
    if (!$user) {
        return '<span class="' . e($class) . '" aria-hidden="true">?</span>';
    }
    if (!empty($user['avatar_path'])) {
        return '<img class="' . e($class) . '" src="' . e((string) $user['avatar_path']) . '" alt="" width="64" height="64">';
    }
    return '<span class="' . e($class) . '" aria-hidden="true">' . e(initials((string) $user['name'])) . '</span>';
}

function boot_script(array $extra = []): string
{
    $user = current_user();
    $payload = array_merge([
        'csrf' => csrf_token(),
        'page' => $GLOBALS['page'] ?? '',
        'demoMode' => repo_mode() !== 'mysql',
        'user' => $user,
        'pollMs' => 30000,
    ], $extra);
    $json = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    return '<script type="application/json" id="boot">' . $json . '</script>';
}

function render_header(array $meta): void
{
    $title = $meta['title'] ?? APP_NAME;
    $description = $meta['description'] ?? 'Всероссийская сеть взаимовыручки дальнобойщиков. На 15-м — свои.';
    $canonical = $meta['canonical'] ?? APP_URL . '/';
    $page = $GLOBALS['page'] ?? '';
    $user = current_user();
    $holiday = holiday();
    $flash = flash_take();
    require dirname(__DIR__) . '/templates/header.php';
}

function render_footer(array $meta = []): void
{
    $page = $GLOBALS['page'] ?? '';
    $compact = ($meta['chrome'] ?? '') === 'map';
    $boot = boot_script($GLOBALS['boot_extra'] ?? []);
    require dirname(__DIR__) . '/templates/footer.php';
}
