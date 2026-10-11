<?php
require_once dirname(__DIR__) . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$u = require_auth();
$action = $_GET['action'] ?? '';

// GET /api/student.php?action=dashboard  — dashboard data
// GET /api/student.php?action=profile    — full profile
// POST /api/student.php?action=profile   — update profile
// GET /api/student.php?action=registration
// POST /api/student.php?action=registration
// GET /api/student.php?action=activity

// ── GET: dashboard ────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'dashboard') {
    $r    = calc_readiness($u);
    $q    = db()->prepare('SELECT * FROM activity_log WHERE student_id=? ORDER BY created_at DESC LIMIT 5');
    $q->execute([$u['student_id']]);
    $events = $q->fetchAll();
    ok([
        'user'     => $u,
        'readiness' => $r,
        'activity' => $events,
    ]);
}

// ── GET: profile ──────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'profile') {
    $r = calc_readiness($u);
    ok(['user' => $u, 'readiness' => $r]);
}

// ── POST: profile ─────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'profile') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    db()->prepare('UPDATE student_registration SET full_name=?,phone=?,address=?,city=?,state=?,postal_code=?,guardian_name=?,guardian_phone=? WHERE student_id=?')->execute([
        trim($body['full_name'] ?? ''),
        trim($body['phone'] ?? ''),
        trim($body['address'] ?? ''),
        trim($body['city'] ?? ''),
        trim($body['state'] ?? ''),
        trim($body['postal_code'] ?? ''),
        trim($body['guardian_name'] ?? ''),
        trim($body['guardian_phone'] ?? ''),
        $u['student_id'],
    ]);
    log_activity((int)$u['student_id'], 'Profile updated', 'Student contact details were updated');
    ok(null, 'Your profile has been updated.');
}

// ── GET: registration ─────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'registration') {
    ok(['user' => $u]);
}

// ── POST: registration ────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'registration') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $step = max(1, min(3, (int)($body['step'] ?? 1)));
    db()->prepare('UPDATE student_registration SET full_name=?,course=?,phone=?,admission_year=?,date_of_birth=?,gender=?,address=?,city=?,state=?,postal_code=?,guardian_name=?,guardian_phone=? WHERE student_id=?')->execute([
        trim($body['full_name'] ?? ''),
        trim($body['course'] ?? ''),
        trim($body['phone'] ?? ''),
        (int)($body['admission_year'] ?? date('Y')),
        ($body['date_of_birth'] ?? '') ?: null,
        ($body['gender'] ?? '') ?: null,
        trim($body['address'] ?? ''),
        trim($body['city'] ?? ''),
        trim($body['state'] ?? ''),
        trim($body['postal_code'] ?? ''),
        trim($body['guardian_name'] ?? ''),
        trim($body['guardian_phone'] ?? ''),
        $u['student_id'],
    ]);
    log_activity((int)$u['student_id'], 'Registration details saved', 'Step ' . $step . ' of 3');
    ok(['step' => $step], 'Registration details saved.');
}

// ── GET: activity ─────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'activity') {
    $q = db()->prepare('SELECT * FROM activity_log WHERE student_id=? ORDER BY created_at DESC LIMIT 20');
    $q->execute([$u['student_id']]);
    ok(['activity' => $q->fetchAll()]);
}

fail('Unknown action.', 404);
