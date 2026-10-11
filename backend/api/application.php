<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$u      = require_auth();
$action = $_GET['action'] ?? '';

ensure_tables();
$sid = (int)$u['student_id'];

// ── GET: get application status and timeline ──────────────────────────────
if ($method === 'GET' && $action === 'get') {
    $q = db()->prepare('SELECT * FROM application_track WHERE student_id = ?');
    $q->execute([$sid]);
    $app = $q->fetch();

    if (!$app) {
        // Initialize default application record
        $initStatus = !empty($u['course']) ? 'Under Review' : 'Draft';
        $initNote   = $initStatus === 'Under Review' 
            ? 'Application submitted and currently undergoing evaluation by Admissions Committee.' 
            : 'Complete your personal and academic details to submit.';
        
        db()->prepare('
            INSERT INTO application_track (student_id, status, status_note, submitted_at)
            VALUES (?, ?, ?, NOW())
        ')->execute([$sid, $initStatus, $initNote]);

        $q->execute([$sid]);
        $app = $q->fetch();

        add_notification(
            $sid,
            'Application Created',
            "Your application for {$u['course']} is recorded with status: {$initStatus}.",
            'info'
        );
    }

    // Build timeline stages with dynamic labels based on active status
    $docLabel = ($app['status'] === 'Correction Required') ? 'Correction Required' : 'Document Verification';
    $docDesc  = ($app['status'] === 'Correction Required') 
        ? 'Admissions office requested document correction' 
        : 'Verification of transcripts & identification';

    $finalLabel = match($app['status']) {
        'Waitlisted' => 'Admissions Waitlisted',
        'Rejected'   => 'Application Rejected',
        default      => 'Admissions Approved',
    };
    $finalDesc = match($app['status']) {
        'Waitlisted' => 'Placed on official university waitlist pending quota vacancy',
        'Rejected'   => 'Evaluated and not selected for current admissions cycle',
        default      => 'Final acceptance granted. Eligible for fee payment & enrollment',
    };

    $allStages = [
        ['key' => 'Draft',               'label' => 'Application Draft',       'desc' => 'Student registration initiated'],
        ['key' => 'Submitted',           'label' => 'Submitted Online',        'desc' => 'Form submitted & application fee verified'],
        ['key' => 'Under Review',        'label' => 'Under Faculty Review',    'desc' => 'Admissions committee evaluating academic credentials'],
        ['key' => 'Documents Pending',   'label' => $docLabel,                 'desc' => $docDesc],
        ['key' => 'Approved',            'label' => $finalLabel,               'desc' => $finalDesc]
    ];

    // Status map ordering
    $statusOrder = [
        'Draft' => 1,
        'Submitted' => 2,
        'Under Review' => 3,
        'Documents Pending' => 4,
        'Correction Required' => 4,
        'Waitlisted' => 5,
        'Approved' => 5,
        'Rejected' => 5,
    ];

    $currentRank = $statusOrder[$app['status']] ?? 2;

    $timeline = array_map(function($stage, $idx) use ($currentRank, $app) {
        $stepNum = $idx + 1;
        $isCurrent = ($stepNum === $currentRank);
        $isDone = ($stepNum < $currentRank);
        return [
            'step'      => $stepNum,
            'key'       => $stage['key'],
            'label'     => $stage['label'],
            'desc'      => $stage['desc'],
            'state'     => $isCurrent ? 'current' : ($isDone ? 'completed' : 'upcoming'),
            'timestamp' => $isDone || $isCurrent ? $app['updated_at'] : null,
        ];
    }, $allStages, array_keys($allStages));

    ok([
        'application' => $app,
        'timeline'    => $timeline,
        'user'        => $u,
    ]);
}

// ── POST: update application status (simulation & review) ─────────────────
if ($method === 'POST' && $action === 'update_status') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $validStatuses = ['Draft', 'Submitted', 'Under Review', 'Documents Pending', 'Correction Required', 'Waitlisted', 'Approved', 'Rejected'];
    
    $newStatus = trim($body['status'] ?? '');
    $note      = trim($body['note'] ?? '');

    if (!in_array($newStatus, $validStatuses, true)) {
        fail('Invalid status provided.');
    }

    if (!$note) {
        $defaultNotes = [
            'Draft'               => 'Application returned to draft state for modifications.',
            'Submitted'           => 'Application formally submitted for admissions review.',
            'Under Review'        => 'Admissions Committee is actively reviewing academic credentials.',
            'Documents Pending'   => 'Verification pending for identity or transfer certificate.',
            'Correction Required' => 'Admissions office requested correction on uploaded marksheet.',
            'Waitlisted'          => 'Application placed on the official university waitlist.',
            'Approved'            => 'Congratulations! You have been accepted for enrollment.',
            'Rejected'            => 'Application not selected for the current academic session.',
        ];
        $note = $defaultNotes[$newStatus] ?? 'Application status updated.';
    }

    db()->prepare('
        INSERT INTO application_track (student_id, status, status_note, submitted_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE status = VALUES(status), status_note = VALUES(status_note), updated_at = NOW()
    ')->execute([$sid, $newStatus, $note]);

    // Notification type based on status
    $notifType = match($newStatus) {
        'Approved'                                  => 'success',
        'Correction Required', 'Documents Pending'  => 'warning',
        'Rejected'                                  => 'alert',
        default                                     => 'info',
    };

    add_notification(
        $sid,
        "Status Changed: {$newStatus}",
        $note,
        $notifType
    );

    log_activity($sid, "Application Status: {$newStatus}", $note);

    ok(['status' => $newStatus, 'note' => $note], "Application status successfully updated to {$newStatus}.");
}

fail('Unknown application action.', 404);
