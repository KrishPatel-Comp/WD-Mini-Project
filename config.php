<?php
declare(strict_types=1);
session_start();
defined('DB_HOST') || define('DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1');
defined('DB_PORT') || define('DB_PORT', $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306');
defined('DB_NAME') || define('DB_NAME', $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'unibridge');
defined('DB_USER') || define('DB_USER', $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root');
defined('DB_PASS') || define('DB_PASS', $_ENV['DB_PASS'] ?? (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''));
const APP_NAME = 'UniBridge';
function db(): PDO {
    static $pdo;
    if (!$pdo) $pdo = new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    return $pdo;
}
function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function go(string $url): never { header('Location: '.$url); exit; }
function user(): ?array {
    if (empty($_SESSION['student_id'])) return null;
    $q=db()->prepare('SELECT l.student_id,l.admission_no,l.email,r.full_name,r.course,r.phone,r.admission_year,r.date_of_birth,r.gender,r.address,r.city,r.state,r.postal_code,r.guardian_name,r.guardian_phone,r.profile_photo FROM student_login l LEFT JOIN student_registration r ON r.student_id=l.student_id WHERE l.student_id=?');
    $q->execute([$_SESSION['student_id']]); return $q->fetch() ?: null;
}
function require_login(): array { $u=user(); if(!$u) go('login.php'); return $u; }
function csrf(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(24)); return $_SESSION['csrf']; }
function check_csrf(): void { if(!hash_equals(csrf(), $_POST['csrf']??'')) { http_response_code(400); exit('Invalid request token. Please go back and try again.'); } }
function flash(string $m, string $type='success'): void { $_SESSION['flash']=['message'=>$m,'type'=>$type]; }
function take_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function documents(int $id): array { $q=db()->prepare('SELECT * FROM document_track WHERE student_id=? ORDER BY FIELD(document_type,"photo","identity","marksheet","transfer_certificate"),updated_at DESC'); $q->execute([$id]); $out=[]; foreach($q as $d)$out[$d['document_type']]=$d; return $out; }
function readiness(array $u): array {
    $fields=['full_name','course','phone','admission_year','date_of_birth','gender','address','city','state','postal_code','guardian_name','guardian_phone']; $filled=0; foreach($fields as $f)if(!empty($u[$f]))$filled++;
    $ds=documents((int)$u['student_id']); $approved=0; foreach(['photo','identity','marksheet','transfer_certificate'] as $k)if(($ds[$k]['status']??'')==='Approved')$approved++;
    $profile=(int)round($filled/count($fields)*100); $score=(int)round($profile*.55+($approved/4)*45); return ['profile'=>$profile,'docs'=>$approved,'score'=>$score,'documents'=>$ds];
}
function log_activity(int $id,string $action,string $detail=''): void { $q=db()->prepare('INSERT INTO activity_log(student_id,action,detail) VALUES(?,?,?)');$q->execute([$id,$action,$detail]); }
function layout_start(string $title, ?array $u=null): void { $f=take_flash(); ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?=h($title)?> · UniBridge</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/style.css"><script defer src="assets/app.js"></script></head><body><?php if($u): ?><aside class="sidebar"><a class="brand" href="dashboard.php"><span class="brand-mark">u</span><span>UniBridge<small>STUDENT PORTAL</small></span></a><div class="side-label">WORKSPACE</div><nav><a class="nav-link <?=basename($_SERVER['PHP_SELF'])==='dashboard.php'?'active':''?>" href="dashboard.php"><span>⌂</span> Overview</a><a class="nav-link" href="registration.php"><span>▤</span> Registration</a><a class="nav-link" href="documents.php"><span>↥</span> Documents</a><a class="nav-link" href="profile.php"><span>◉</span> My profile</a></nav><div class="sidebar-bottom"><div class="help-card"><span class="help-icon">✦</span><b>Need a hand?</b><p>Your onboarding guide is here to help.</p><a href="mailto:admissions@unibridge.edu">Contact support ↗</a></div><a class="nav-link logout" href="logout.php"><span>↪</span> Sign out</a></div></aside><main class="main-shell"><header class="topbar"><div class="crumb">Student portal <span>/</span> <?=h($title)?></div><div class="top-user"><span class="online-dot"></span><span><?=h($u['full_name']?:$u['admission_no'])?></span><span class="avatar"><?=strtoupper(substr($u['full_name']?:'S',0,1))?></span></div></header><?php else: ?><main class="public-shell"><a class="brand public-brand" href="login.php"><span class="brand-mark">u</span><span>UniBridge<small>STUDENT PORTAL</small></span></a><?php endif; ?><?php if($f): ?><div class="toast <?=h($f['type'])?>"><?=h($f['message'])?></div><?php endif; ?><?php }
function layout_end(?array $u=null): void { ?></main></body></html><?php }
