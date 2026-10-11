<?php
require_once dirname(__DIR__) . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$u = require_auth();
$action = $_GET['action'] ?? '';

// GET  /api/documents.php?action=list    — list all documents
// POST /api/documents.php?action=upload  — multipart upload

// ── GET: list ─────────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'list') {
    $docs = get_documents((int)$u['student_id']);
    ok(['documents' => $docs]);
}

// ── POST: upload ──────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'upload') {
    $types = ['photo', 'identity', 'marksheet', 'transfer_certificate'];
    $type  = $_POST['document_type'] ?? '';
    $file  = $_FILES['document'] ?? null;

    if (!in_array($type, $types, true))                              fail('Invalid document type.');
    if (!$file || $file['error'] !== UPLOAD_ERR_OK)                  fail('No file received or upload error.');
    if ($file['size'] > 5 * 1024 * 1024)                            fail('File must be under 5 MB.');
    $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array(mime_content_type($file['tmp_name']), $allowed, true)) fail('Accepted formats: PDF, JPG, PNG.');

    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    $name = $u['student_id'] . '_' . $type . '_' . time() . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    move_uploaded_file($file['tmp_name'], $dir . '/' . $name);

    db()->prepare('INSERT INTO document_track(student_id,document_type,original_name,stored_name,status,uploaded_at) VALUES(?,?,?,?,"Pending",NOW()) ON DUPLICATE KEY UPDATE original_name=VALUES(original_name),stored_name=VALUES(stored_name),status="Pending",uploaded_at=NOW(),review_note=NULL')->execute([
        $u['student_id'], $type, basename($file['name']), $name
    ]);

    $labels = ['photo' => 'Profile photo', 'identity' => 'Identity proof', 'marksheet' => 'Latest marksheet', 'transfer_certificate' => 'Transfer certificate'];
    log_activity((int)$u['student_id'], 'Document uploaded', ($labels[$type] ?? $type) . ' is awaiting review');
    ok(['document_type' => $type, 'file' => $name], ($labels[$type] ?? $type) . ' uploaded and marked pending review.');
}

fail('Unknown action.', 404);
