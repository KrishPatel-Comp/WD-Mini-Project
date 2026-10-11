<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$u      = require_auth();
$action = $_GET['action'] ?? '';

ensure_tables();
$sid = (int)$u['student_id'];

// ── GET: list notifications ───────────────────────────────────────────────
if ($method === 'GET' && $action === 'list') {
    $q = db()->prepare('SELECT * FROM student_notifications WHERE student_id = ? ORDER BY created_at DESC LIMIT 20');
    $q->execute([$sid]);
    $items = $q->fetchAll();

    // If empty, seed a welcome notification
    if (empty($items)) {
        add_notification(
            $sid,
            'Welcome to UniBridge',
            'Your student onboarding portal is active. Complete your profile and documents to begin.',
            'info'
        );
        $q->execute([$sid]);
        $items = $q->fetchAll();
    }

    $unread = count(array_filter($items, fn($n) => (int)$n['is_read'] === 0));

    ok([
        'notifications' => $items,
        'unread_count'  => $unread,
    ]);
}

// ── POST: mark notification as read ───────────────────────────────────────
if ($method === 'POST' && $action === 'mark_read') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $nid  = (int)($body['notification_id'] ?? 0);

    if ($nid > 0) {
        db()->prepare('UPDATE student_notifications SET is_read = 1 WHERE notification_id = ? AND student_id = ?')
            ->execute([$nid, $sid]);
    } else {
        // Mark all as read
        db()->prepare('UPDATE student_notifications SET is_read = 1 WHERE student_id = ?')
            ->execute([$sid]);
    }

    ok(null, 'Notification marked as read.');
}

fail('Unknown notifications action.', 404);
