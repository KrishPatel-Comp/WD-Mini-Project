<?php
require_once __DIR__ . '/config.php';
$u = require_login();
$step = max(1, min(3, (int)($_GET['step'] ?? 1)));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $step = max(1, min(3, (int)($_POST['step'] ?? 1)));
    db()->prepare('UPDATE student_registration SET full_name=?,course=?,phone=?,admission_year=?,date_of_birth=?,gender=?,address=?,city=?,state=?,postal_code=?,guardian_name=?,guardian_phone=? WHERE student_id=?')->execute([
        trim($_POST['full_name'] ?? ''), trim($_POST['course'] ?? ''), trim($_POST['phone'] ?? ''),
        (int)($_POST['admission_year'] ?? date('Y')),
        ($_POST['date_of_birth'] ?? '') ?: null,
        ($_POST['gender'] ?? '') ?: null,
        trim($_POST['address'] ?? ''), trim($_POST['city'] ?? ''), trim($_POST['state'] ?? ''),
        trim($_POST['postal_code'] ?? ''), trim($_POST['guardian_name'] ?? ''), trim($_POST['guardian_phone'] ?? ''),
        $u['student_id']
    ]);
    log_activity((int)$u['student_id'], 'Registration details saved', 'Step ' . $step . ' of 3');
    flash('Registration details saved.');
    go('registration.php?step=' . (isset($_POST['back']) ? max(1, $step - 1) : min(3, $step + 1)));
}

$u = user();
$all = ['full_name','course','phone','admission_year','date_of_birth','gender','address','city','state','postal_code','guardian_name','guardian_phone'];
$visible = [1 => ['full_name','date_of_birth','gender','phone'], 2 => ['course','admission_year'], 3 => ['address','city','state','postal_code','guardian_name','guardian_phone']];

layout_start('Student registration', $u);
?>
<div class="content">
  <div class="eyebrow">STUDENT REGISTRATION</div>
  <h1 class="page-title">Your details, your pace.</h1>
  <p class="subhead">A few details help us prepare your student record. Your progress saves as you go.</p>
  <div class="stepper">
    <?php foreach (['Personal','Academic','Contact'] as $i => $label): $n = $i + 1; ?>
    <div class="step <?= $step === $n ? 'active' : ($step > $n ? 'done' : '') ?>"><b><?= $step > $n ? '✓' : $n ?></b><?= $label ?></div>
    <?php if ($n < 3): ?><i class="step-line"></i><?php endif; endforeach ?>
  </div>

  <form class="card form-card" method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
    <input type="hidden" name="step" value="<?= $step ?>">
    <?php foreach ($all as $f): if (!in_array($f, $visible[$step], true)): ?>
    <input type="hidden" name="<?= $f ?>" value="<?= h((string)($u[$f] ?? '')) ?>">
    <?php endif; endforeach; ?>
    <div class="card-head">
      <h2>Step <?= $step ?> of 3 · <?= ['Personal','Academic','Contact'][$step-1] ?> details</h2>
      <span class="muted" style="font-size:11px">Changes save when you continue.</span>
    </div>
    <div class="form-grid">
      <?php if ($step === 1): ?>
      <div class="field"><label>Full name *</label><input name="full_name" required value="<?= h($u['full_name']) ?>"></div>
      <div class="field"><label>Date of birth</label><input type="date" name="date_of_birth" value="<?= h($u['date_of_birth']) ?>"></div>
      <div class="field">
        <label>Gender</label>
        <select name="gender">
          <option value="">Select (optional)</option>
          <?php foreach (['Female','Male','Non-binary','Prefer not to say'] as $g): ?>
          <option <?= $u['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="field"><label>Mobile number *</label><input type="tel" name="phone" required value="<?= h($u['phone']) ?>"></div>
      <?php elseif ($step === 2): ?>
      <div class="field"><label>Course *</label><input name="course" required value="<?= h($u['course']) ?>"></div>
      <div class="field"><label>Admission year *</label><input type="number" name="admission_year" min="2000" max="2100" value="<?= h((string)($u['admission_year'] ?: date('Y'))) ?>"></div>
      <div class="field"><label>Admission number</label><input disabled value="<?= h($u['admission_no']) ?>"></div>
      <?php else: ?>
      <div class="field full"><label>Current address</label><textarea name="address"><?= h($u['address']) ?></textarea></div>
      <div class="field"><label>City</label><input name="city" value="<?= h($u['city']) ?>"></div>
      <div class="field"><label>State / region</label><input name="state" value="<?= h($u['state']) ?>"></div>
      <div class="field"><label>Postal code</label><input name="postal_code" value="<?= h($u['postal_code']) ?>"></div>
      <div class="field"><label>Parent / guardian name</label><input name="guardian_name" value="<?= h($u['guardian_name']) ?>"></div>
      <div class="field"><label>Guardian mobile number</label><input name="guardian_phone" value="<?= h($u['guardian_phone']) ?>"></div>
      <?php endif ?>
    </div>
    <div class="button-row">
      <?php if ($step > 1): ?><button class="btn btn-outline" name="back" type="submit">← Previous</button><?php endif ?>
      <button class="btn" type="submit"><?= $step === 3 ? 'Save and finish' : 'Save & continue →' ?></button>
      <a class="btn btn-light" href="dashboard.php">Save &amp; exit</a>
    </div>
  </form>
</div>
<?php layout_end($u); ?>
