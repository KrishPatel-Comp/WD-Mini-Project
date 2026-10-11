<?php
require_once dirname(__DIR__) . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET /api/auth.php?action=me  — return current session user + CSRF token
// POST /api/auth.php?action=login
// POST /api/auth.php?action=signup
// POST /api/auth.php?action=logout
// POST /api/auth.php?action=forgot
// POST /api/auth.php?action=reset

$action = $_GET['action'] ?? '';

// ── GET: me ──────────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'me') {
    $u = current_user();
    ok([
        'user'  => $u,
        'csrf'  => csrf_token(),
        'loggedIn' => (bool)$u,
    ]);
}

// ── POST: login ───────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'login') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $key   = trim($body['identity'] ?? '');
    $pass  = $body['password'] ?? '';
    if (!$key || !$pass) fail('Please enter your admission number / email and password.');
    $q = db()->prepare('SELECT student_id,password_hash FROM student_login WHERE admission_no=? OR email=?');
    $q->execute([$key, $key]);
    $row = $q->fetch();
    if (!$row || !password_verify($pass, $row['password_hash'])) fail("We couldn't match those details. Check them and try again.");
    session_regenerate_id(true);
    $_SESSION['student_id'] = (int)$row['student_id'];
    db()->prepare('UPDATE student_login SET last_login=NOW() WHERE student_id=?')->execute([$row['student_id']]);
    ok(['redirect' => 'dashboard.html'], 'Welcome back! Your student journey is right where you left it.');
}

// ── POST: signup ──────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'signup') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $name   = trim($body['full_name'] ?? '');
    $email  = strtolower(trim($body['email'] ?? ''));
    $course = trim($body['course'] ?? '');
    $phone  = trim($body['phone'] ?? '');
    $pass   = $body['password'] ?? '';
    $cpass  = $body['confirm_password'] ?? '';
    if (!$name)                                          fail('Full name is required.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))      fail('Enter a valid email address.');
    if (!$course)                                        fail('Please select your course.');
    if (!preg_match('/^[0-9]{10}$/', $phone))            fail('Mobile number must be exactly 10 digits.');
    if (strlen($pass) < 8)                               fail('Password must be at least 8 characters.');
    if ($pass !== $cpass)                                fail('Passwords do not match.');
    try {
        $pdo = db();
        $pdo->beginTransaction();
        do {
            $ad     = 'UB' . date('Y') . '-' . random_int(1000, 9999);
            $exists = $pdo->prepare('SELECT 1 FROM student_login WHERE admission_no=?');
            $exists->execute([$ad]);
        } while ($exists->fetch());
        $pdo->prepare('INSERT INTO student_login(admission_no,email,password_hash) VALUES(?,?,?)')->execute([$ad, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO student_registration(student_id,full_name,course,phone,admission_year) VALUES(?,?,?,?,YEAR(CURDATE()))')->execute([$id, $name, $course, $phone]);
        $pdo->commit();
        $_SESSION['student_id'] = $id;
        ok(['admission_no' => $ad, 'redirect' => 'registration.html'], 'Your account is ready. Admission ID: ' . $ad);
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        fail('That email is already registered.');
    }
}

// ── POST: logout ──────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'logout') {
    $_SESSION = [];
    session_destroy();
    ok(null, 'Signed out successfully.');
}

// ── POST: forgot ──────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'forgot') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = strtolower(trim($body['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Enter a valid email address.');
    $q = db()->prepare('SELECT student_id FROM student_login WHERE email=?');
    $q->execute([$email]);
    $msg = 'If an account matches that email, a reset link has been generated.';
    if ($q->fetch()) {
        $token = bin2hex(random_bytes(24));
        db()->prepare('INSERT INTO password_resets(email,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 30 MINUTE)) ON DUPLICATE KEY UPDATE token_hash=VALUES(token_hash),expires_at=VALUES(expires_at)')->execute([$email, hash('sha256', $token)]);
        // In production, email this link. For local dev we return it.
        $link = 'reset.html?token=' . urlencode($token);
        ok(['reset_link' => $link], 'Reset link generated (local dev — see data.reset_link).');
    }
    ok(null, $msg);
}

// ── POST: reset ────────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'reset') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $token = $body['token'] ?? '';
    $pass  = $body['password'] ?? '';
    $cpass = $body['confirm_password'] ?? '';
    if (!$token) fail('Missing reset token.');
    $q = db()->prepare('SELECT email FROM password_resets WHERE token_hash=? AND expires_at>NOW()');
    $q->execute([hash('sha256', $token)]);
    $reset = $q->fetch();
    if (!$reset) fail('This reset link has expired or is invalid. Request a new one.');
    if (strlen($pass) < 8)     fail('Password must be at least 8 characters.');
    if ($pass !== $cpass)      fail('Passwords do not match.');
    db()->prepare('UPDATE student_login SET password_hash=? WHERE email=?')->execute([password_hash($pass, PASSWORD_DEFAULT), $reset['email']]);
    db()->prepare('DELETE FROM password_resets WHERE email=?')->execute([$reset['email']]);
    ok(['redirect' => 'login.html'], 'Password updated successfully. You can now sign in.');
}

fail('Unknown action.', 404);
