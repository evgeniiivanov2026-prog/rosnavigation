<?php
declare(strict_types=1);

function icon(string $name, string $class = 'icon'): string
{
    static $paths = [
        'radio' => '<path d="M5 10a7 7 0 0 1 14 0"/><path d="M8 10a4 4 0 0 1 8 0"/><path d="M12 10v7"/><path d="M9 18.5h6"/>',
        'truck' => '<path d="M3 6.5h11v9H3z"/><path d="M14 9h3.5L21 12.5V15.5h-7"/><path d="M7 15.5v2M17 15.5v2"/><circle cx="7" cy="17.5" r="1.4"/><circle cx="17" cy="17.5" r="1.4"/>',
        'pin' => '<path d="M12 21s6-5.2 6-9a6 6 0 1 0-12 0c0 3.8 6 9 6 9z"/><circle cx="12" cy="12" r="1.6"/>',
        'wrench' => '<path d="M14.5 6.5a3.5 3.5 0 0 0-4.8 4.3L4 16.5 6.5 19l5.7-5.7a3.5 3.5 0 0 0 4.3-4.8L14 11l-1.5-1.5z"/>',
        'fuel' => '<path d="M7 4h7v16H7z"/><path d="M14 8h2.2a2 2 0 0 1 2 2v5.2a1.6 1.6 0 1 0 3.2 0V9L18 6"/><path d="M9 8h3"/>',
        'bed' => '<path d="M4 18V8"/><path d="M4 14h16v4"/><path d="M20 18v-5a3 3 0 0 0-3-3H9v6"/><path d="M4 18h16"/>',
        'alert' => '<path d="M12 4 3.5 19h17z"/><path d="M12 10v4"/><path d="M12 16.5h.01"/>',
        'pulse' => '<path d="M3 12h4l2-5 3 10 2-5h7"/>',
        'help' => '<circle cx="12" cy="12" r="8"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.2 2.4c-.8.4-1.2.9-1.2 1.8"/><path d="M12 17h.01"/>',
        'map' => '<path d="M9 5 4 7v12l5-2 6 2 5-2V5l-5 2z"/><path d="M9 5v12M15 7v12"/>',
        'user' => '<circle cx="12" cy="8" r="3"/><path d="M5.5 19a6.5 6.5 0 0 1 13 0"/>',
        'send' => '<path d="M4 12 20 4l-6 16-2.5-6.5z"/>',
        'phone' => '<path d="M8 3.5h8v17H8z"/><path d="M11 18h2"/>',
        'star' => '<path d="m12 3.5 2.2 4.6 5 .7-3.6 3.5.9 5.1L12 15.8 7.5 17.4l.9-5.1L4.8 8.8l5-.7z"/>',
        'shield' => '<path d="M12 3.5 5 6v6.2c0 3.8 2.7 6.4 7 8.3 4.3-1.9 7-4.5 7-8.3V6z"/>',
        'sun' => '<circle cx="12" cy="12" r="3.2"/><path d="M12 3.5v1.8M12 18.7v1.8M3.5 12h1.8M18.7 12h1.8M6 6l1.3 1.3M16.7 16.7 18 18M18 6l-1.3 1.3M7.3 16.7 6 18"/>',
        'moon' => '<path d="M16 3.8A7.2 7.2 0 1 0 20.2 14 5.6 5.6 0 0 1 16 3.8z"/>',
        'check' => '<path d="m5 12 5 5L19 7"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'search' => '<circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/>',
        'clock' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4.5L15 15"/>',
    ];
    $body = $paths[$name] ?? '<circle cx="12" cy="12" r="7"/>';
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $body . '</svg>';
}

function logo_mark(): string
{
    return '<svg class="logo__mark" viewBox="0 0 32 32" aria-hidden="true" focusable="false">'
        . '<rect width="32" height="32" rx="8" fill="#121A24"/>'
        . '<rect x="1.4" y="1.4" width="29.2" height="29.2" rx="7" fill="none" stroke="#FF7A1A" stroke-width="1.5"/>'
        . '<path d="M10 24.5 16 8l6 16.5" fill="none" stroke="#FF7A1A" stroke-width="1.7" stroke-linejoin="round"/>'
        . '<path d="M16 13.2v7" stroke="#F4F1EA" stroke-width="1.5" stroke-dasharray="1.6 2.2" stroke-linecap="round"/>'
        . '</svg>';
}
