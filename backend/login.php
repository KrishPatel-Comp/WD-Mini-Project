<?php
require_once __DIR__ . '/config.php';
if (user()) go('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $q = db()->prepare('SELECT student_id,password_hash FROM student_login WHERE admission_no=? OR email=?');
    $key = trim($_POST['identity'] ?? '');
    $q->execute([$key, $key]);
    $row = $q->fetch();
    if ($row && password_verify($_POST['password'] ?? '', $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['student_id'] = (int)$row['student_id'];
        $q = db()->prepare('UPDATE student_login SET last_login=NOW() WHERE student_id=?');
        $q->execute([$row['student_id']]);
        flash('Welcome back. Your student journey is right where you left it.');
        go('dashboard.php');
    }
    $error = "We couldn't match those details. Check them and try again.";
}
layout_start('Student login');
?>
<div class="auth-layout">
  <section class="auth-copy">
    <div class="eyebrow">A CALMER WAY TO GET READY</div>
    <h1>Welcome to<br>your campus<br>journey.</h1>
    <p>Your registration, documents and important details—together in one place. Pick up right where you left off.</p>
    <div class="auth-points">
      <span>✦ Your onboarding, at a glance</span>
      <span>✦ Clear next steps to keep you moving</span>
      <span>✦ Secure student access</span>
    </div>
  </section>
  <section class="auth-panel">
    <h2>Sign in</h2>
    <p>Use your admission number or registered email.</p>
    <?php if ($error): ?><div class="toast error"><?= h($error) ?></div><?php endif ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
      <div class="field">
        <label>Admission number or email</label>
        <input name="identity" required autocomplete="username" value="<?= h($_POST['identity'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Password</label>
        <input name="password" required type="password" autocomplete="current-password">
      </div>
      <div class="field" style="text-align:right;margin-top:-6px">
        <a class="text-link" href="forgot.php">Forgot password?</a>
      </div>
      <button class="btn" type="submit">Sign in to UniBridge →</button>
    </form>
    <div class="auth-foot">New student? <a href="signup.php">Create an account</a></div>
  </section>
</div>
<?php layout_end();
