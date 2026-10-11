<?php
require_once __DIR__ . '/config.php';
if (user()) go('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name   = trim($_POST['full_name'] ?? '');
    $email  = strtolower(trim($_POST['email'] ?? ''));
    $course = trim($_POST['course'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $pass   = $_POST['password'] ?? '';
    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$course || !preg_match('/^[0-9]{10}$/', $phone) || strlen($pass) < 8 || $pass !== ($_POST['confirm_password'] ?? ''))
        $error = 'Check each field. Use a 10-digit mobile number and matching passwords of at least 8 characters.';
    else try {
        $pdo = db();
        $pdo->beginTransaction();
        do {
            $ad = 'UB' . date('Y') . '-' . random_int(1000, 9999);
            $exists = $pdo->prepare('SELECT 1 FROM student_login WHERE admission_no=?');
            $exists->execute([$ad]);
        } while ($exists->fetch());
        $pdo->prepare('INSERT INTO student_login(admission_no,email,password_hash) VALUES(?,?,?)')->execute([$ad, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO student_registration(student_id,full_name,course,phone,admission_year) VALUES(?,?,?,?,YEAR(CURDATE()))')->execute([$id, $name, $course, $phone]);
        $pdo->commit();
        $_SESSION['student_id'] = $id;
        flash('Your account is ready. Your admission ID is ' . $ad . '.');
        go('registration.php');
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        $error = 'That email is already registered.';
    }
}
layout_start('Create account');
?>
<div class="auth-layout">
  <section class="auth-copy">
    <div class="eyebrow">YOUR NEXT CHAPTER STARTS HERE</div>
    <h1>One bridge.<br>All the way to<br>your first day.</h1>
    <p>Get your student journey in order, one small step at a time. Create your account to begin onboarding.</p>
    <div class="auth-points">
      <span>✓ Track registration progress</span>
      <span>✓ Upload and follow documents</span>
      <span>✓ Keep your student details in one place</span>
    </div>
  </section>
  <section class="auth-panel">
    <h2>Create your account</h2>
    <p>Start your student onboarding journey.</p>
    <?php if ($error): ?><div class="toast error"><?= h($error) ?></div><?php endif ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
      <div class="field"><label>Full name</label><input name="full_name" required value="<?= h($_POST['full_name'] ?? '') ?>" autocomplete="name"></div>
      <div class="field">
        <label>Admission ID</label>
        <input disabled value="Generated automatically after account creation">
        <small>Your unique UniBridge ID will appear after registration.</small>
      </div>
      <div class="field"><label>Mobile number</label><input name="phone" required type="tel" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" placeholder="10-digit mobile number" value="<?= h($_POST['phone'] ?? '') ?>"></div>
      <div class="field"><label>Email address</label><input name="email" required type="email" autocomplete="email" value="<?= h($_POST['email'] ?? '') ?>"></div>
      <div class="field">
        <label>Course</label>
        <select name="course" required>
          <option value="">Choose your course</option>
          <?php foreach (['B.Sc. Computer Science','BCA','B.Com','B.A. Economics','BBA','B.Tech. Information Technology','Other'] as $c): ?>
          <option><?= h($c) ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="form-grid">
        <div class="field"><label>Password</label><input class="password-field" name="password" required minlength="8" type="password"></div>
        <div class="field"><label>Confirm password</label><input class="password-field" name="confirm_password" required minlength="8" type="password"></div>
      </div>
      <label class="show-password"><input type="checkbox" data-show-password> Show passwords</label>
      <button class="btn" type="submit">Create student account →</button>
    </form>
    <div class="auth-foot">Already registered? <a href="login.php">Sign in</a></div>
  </section>
</div>
<?php layout_end(); ?>
