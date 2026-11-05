<?php
declare(strict_types=1);

function handle_sos_post(string $back): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }
    $action = (string) ($_POST['action'] ?? '');
    if (!in_array($action, ['create', 'cancel', 'accept', 'complete'], true)) {
        return;
    }
    if (!csrf_ok()) {
        flash_set('error', 'Сессия устарела. Обнови страницу.');
        redirect($back);
    }
    if (!rate_hit('sos:' . client_ip(), 8, 600)) {
        flash_set('error', 'Слишком часто. Подожди немного и позови ещё раз.');
        redirect($back);
    }
    $user = require_user_web();
    $id = (int) ($_POST['sos_id'] ?? 0);
    $uid = (int) $user['id'];
    $result = match ($action) {
        'create' => repo_create_sos($uid, $_POST),
        'cancel' => repo_cancel_sos($id, $uid),
        'accept' => repo_accept_sos($id, $uid),
        'complete' => repo_complete_sos($id, $uid),
        default => ['ok' => false, 'message' => 'Не понял действие.'],
    };
    if (empty($result['ok'])) {
        flash_set('error', (string) ($result['message'] ?? 'Не вышло.'));
        redirect($back);
    }
    if ($action === 'create') {
        redirect('/sos?sent=' . (int) $result['id']);
    }
    $text = match ($action) {
        'cancel' => 'Сигнал снят. Доброй дороги.',
        'accept' => 'Принял. Езжай аккуратно.',
        'complete' => 'Доброй дороги. Выручили.',
        default => 'Готово.',
    };
    flash_set('ok', $text);
    redirect($_POST['back'] ?? $back);
}
