<?php
require_once __DIR__ . '/config.php';
$u = require_login();
$r = readiness($u);
$q = db()->prepare('SELECT * FROM activity_log WHERE student_id=? ORDER BY created_at DESC LIMIT 5');
$q->execute([$u['student_id']]);
$events = $q->fetchAll();
$docs = $r['documents'];
layout_start('Overview', $u);
?>
<div class="content">
  <div class="hero">
    <div>
      <div class="eyebrow">YOUR CAMPUS JOURNEY</div>
      <h1>Good to see you, <?= h(explode(' ', $u['full_name'] ?: 'student')[0]) ?>.</h1>
      <p>Small steps today bring your first day closer.</p>
    </div>
    <div class="hero-art">✳</div>
  </div>

  <div class="grid stats">
    <div class="card stat-card"><div class="stat-icon">▤</div><div><small>Registration profile</small><strong><?= $r['profile'] ?>%</strong></div></div>
    <div class="card stat-card"><div class="stat-icon">↥</div><div><small>Documents approved</small><strong><?= $r['docs'] ?> <span style="font-size:13px;color:#89958c">/ 4</span></strong></div></div>
    <div class="card stat-card"><div class="stat-icon">✦</div><div><small>Readiness score</small><strong><?= $r['score'] ?>%</strong></div></div>
  </div>

  <div class="grid two-col">
    <section class="card">
      <div class="card-head"><h2>Your readiness</h2><a class="text-link" href="registration.php">Continue setup →</a></div>
      <div class="readiness">
        <div class="ring" style="--p:<?= $r['score'] ?>"><strong><?= $r['score'] ?>%</strong></div>
        <div>
          <b><?= $r['score'] >= 90 ? 'Almost there!' : ($r['score'] >= 50 ? "You're making progress" : "Let's get started") ?></b>
          <p>Complete your student details and get all four documents approved to reach 100% readiness.</p>
        </div>
      </div>
      <div class="checklist">
        <div class="check-item"><span class="check">✓</span> Student account created <span class="muted" style="margin-left:auto">Complete</span></div>
        <div class="check-item"><span class="check <?= $r['profile'] === 100 ? '' : 'pending' ?>"><?= $r['profile'] === 100 ? '✓' : '•' ?></span> Profile details <span class="muted" style="margin-left:auto"><?= $r['profile'] ?>%</span></div>
        <div class="check-item"><span class="check <?= $r['docs'] === 4 ? '' : 'pending' ?>"><?= $r['docs'] === 4 ? '✓' : '•' ?></span> Documents approved <span class="muted" style="margin-left:auto"><?= $r['docs'] ?> / 4</span></div>
      </div>
      <div class="button-row">
        <a class="btn" href="registration.php">Continue registration →</a>
        <a class="btn btn-light" href="documents.php">Manage documents</a>
      </div>
    </section>

    <section class="card">
      <div class="card-head"><h2>Document tracker</h2><a class="text-link" href="documents.php">View all</a></div>
      <?php foreach (['photo' => 'Profile photo', 'identity' => 'Identity proof', 'marksheet' => 'Latest marksheet', 'transfer_certificate' => 'Transfer certificate'] as $key => $label):
        $d = $docs[$key] ?? null; ?>
      <div class="progress-row">
        <div class="progress-label"><span><?= $label ?></span><span><?= $d ? h($d['status']) : 'Not uploaded' ?></span></div>
        <div class="track"><i style="width:<?= $d ? ($d['status'] === 'Approved' ? 100 : 55) : 0 ?>%;background:<?= $d && $d['status'] === 'Approved' ? '#82ad65' : '#d4b46a' ?>"></i></div>
      </div>
      <?php endforeach ?>
      <a href="documents.php" class="btn btn-light" style="margin-top:10px">Upload a document ↑</a>
    </section>
  </div>

  <div class="grid two-col" style="margin-top:17px">
    <section class="card">
      <div class="card-head"><h2>Recent activity</h2><span class="muted" style="font-size:11px">Your latest updates</span></div>
      <?php if (!$events): ?>
      <div class="empty">Your activity will appear here as you complete onboarding.</div>
      <?php else: ?>
      <div class="activity">
        <?php foreach ($events as $ev): ?>
        <div class="activity-row">
          <span class="activity-mark">✦</span>
          <div><b><?= h($ev['action']) ?></b><small><?= h($ev['detail']) ?> · <?= date('M j, g:i a', strtotime($ev['created_at'])) ?></small></div>
        </div>
        <?php endforeach ?>
      </div>
      <?php endif ?>
    </section>

    <section class="card">
      <div class="card-head"><h2>Student snapshot</h2><a class="text-link" href="profile.php">Edit profile →</a></div>
      <div class="activity-row"><div><small class="muted">Admission number</small><b style="display:block;margin-top:5px"><?= h($u['admission_no']) ?></b></div></div>
      <div class="activity-row"><div><small class="muted">Course</small><b style="display:block;margin-top:5px"><?= h($u['course'] ?: 'Add your course') ?></b></div></div>
      <div class="activity-row"><div><small class="muted">Email</small><b style="display:block;margin-top:5px"><?= h($u['email']) ?></b></div></div>
    </section>
  </div>

  <div class="footnote">UniBridge · Your onboarding journey, in one place.</div>
</div>
<?php layout_end($u);
