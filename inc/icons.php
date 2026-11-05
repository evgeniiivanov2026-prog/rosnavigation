<?php
declare(strict_types=1);

function icon(string $name, string $class = 'icon'): string
{
    $paths = [
        'radio' => '<path d="M4.5 11.5a7.5 7.5 0 0 1 15 0"/><path d="M7.5 13a4.5 4.5 0 0 1 9 0"/><circle cx="12" cy="17.2" r="1.2" fill="currentColor" stroke="none"/>',
        'truck' => '<path d="M3 15V7h11v8"/><path d="M14 10h4l3 3v2h-7"/><circle cx="7" cy="17" r="1.6"/><circle cx="17" cy="17" r="1.6"/>',
        'alert' => '<path d="M12 4.5 20 19H4L12 4.5Z"/><path d="M12 10v4"/><path d="M12 16.5h.01"/>',
        'wrench' => '<path d="M14.5 6.5a3.5 3.5 0 0 0-4.8 4.6L4 16.8 7.2 20l5.7-5.7a3.5 3.5 0 0 0 4.6-4.8L15 12l-3-3 2.5-2.5Z"/>',
        'fuel' => '<path d="M7 20V5h8v15"/><path d="M7 10h8"/><path d="M15 7l3 1.2V14a2 2 0 0 0 2 2h.5"/>',
        'bed' => '<path d="M3 18V8"/><path d="M3 14h18v4"/><path d="M7 14v-2a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/><path d="M21 18V10"/>',
        'med' => '<path d="M12 21s-7-4.4-7-9a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 4.6-7 9-7 9Z"/><path d="M12 10v4"/><path d="M10 12h4"/>',
        'help' => '<circle cx="12" cy="12" r="8"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.2 2.4c-.8.4-1.2.9-1.2 1.8"/><path d="M12 17h.01"/>',
        'pin' => '<path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10Z"/><circle cx="12" cy="11" r="1.6"/>',
        'star' => '<path d="m12 3.8 1.9 4.6 5 .5-3.8 3.2 1.2 4.9L12 14.8 7.7 17l1.2-4.9L5.1 8.9l5-.5L12 3.8Z"/>',
        'phone' => '<path d="M8 4.5h2.6l1 2.6-1.7 1a11 11 0 0 0 5 5l1-1.7 2.6 1V16a1.8 1.8 0 0 1-1.8 1.8A13.2 13.2 0 0 1 6.2 6.3 1.8 1.8 0 0 1 8 4.5Z"/>',
        'user' => '<circle cx="12" cy="8" r="3"/><path d="M5.5 19.2a6.5 6.5 0 0 1 13 0"/>',
        'moon' => '<path d="M16 4.5A7.5 7.5 0 1 0 19.5 16 6 6 0 0 1 16 4.5Z"/>',
        'sun' => '<circle cx="12" cy="12" r="3.2"/><path d="M12 3.5v2M12 18.5v2M3.5 12h2M18.5 12h2M6 6l1.4 1.4M16.6 16.6 18 18M18 6l-1.4 1.4M7.4 16.6 6 18"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'shield' => '<path d="M12 3.5 19 6.5v5.2c0 4.2-2.8 7.2-7 8.8-4.2-1.6-7-4.6-7-8.8V6.5L12 3.5Z"/>',
        'chevron' => '<path d="M9 6l6 6-6 6"/>',
        'route' => '<circle cx="6" cy="6" r="2"/><circle cx="18" cy="18" r="2"/><path d="M8 7c4 0 4 10 8 10"/>',
        'clock' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4.5L15 15"/>',
        'send' => '<path d="M4 12 20 4l-6 16-2.5-6.5L4 12Z"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
    ];
    $inner = $paths[$name] ?? $paths['help'];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">' . $inner . '</svg>';
}

function logo_mark(string $class = 'logo__mark'): string
{
    return '<svg class="' . e($class) . '" viewBox="0 0 32 32" aria-hidden="true" focusable="false">'
        . '<rect x="1.5" y="1.5" width="29" height="29" rx="8" fill="#141A22" stroke="currentColor" stroke-width="1.5"/>'
        . '<path d="M10 24.5 16 8l6 16.5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>'
        . '<path d="M16 14.2V21" stroke="#F2F5F8" stroke-width="1.5" stroke-dasharray="1.7 2.2" stroke-linecap="round"/>'
        . '</svg>';
}
