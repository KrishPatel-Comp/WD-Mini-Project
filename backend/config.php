<?php
declare(strict_types=1);

// ─── CORS & JSON headers ───────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── Session ───────────────────────────────────────────────────────────────
session_start();

// ─── Database ─────────────────────────────────────────────────────────────
defined('DB_HOST') || define('DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1');
defined('DB_PORT') || define('DB_PORT', $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306');
defined('DB_NAME') || define('DB_NAME', $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'unibridge');
defined('DB_USER') || define('DB_USER', $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root');
defined('DB_PASS') || define('DB_PASS', $_ENV['DB_PASS'] ?? (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''));


function db(): PDO
{
    static $pdo;

    if (!$pdo) {
        $dsn = 'mysql:host=' . DB_HOST
            . ';port=' . DB_PORT
            . ';dbname=' . DB_NAME
            . ';charset=utf8mb4';

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}


// ─── Response & Input helpers ─────────────────────────────────────────────
function get_json_body(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw)
        return [];
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string) $raw);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function ok(mixed $data = null, string $message = 'ok'): never
{
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

function fail(string $message, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message, 'data' => null]);
    exit;
}

// ─── Auth helpers ─────────────────────────────────────────────────────────
function current_user(): ?array
{
    if (empty($_SESSION['student_id']))
        return null;
    $q = db()->prepare('
        SELECT l.student_id, l.admission_no, l.email,
               r.full_name, r.course, r.phone, r.admission_year,
               r.date_of_birth, r.gender, r.address, r.city, r.state,
               r.postal_code, r.guardian_name, r.guardian_phone, r.profile_photo
        FROM student_login l
        LEFT JOIN student_registration r ON r.student_id = l.student_id
        WHERE l.student_id = ?
    ');
    $q->execute([$_SESSION['student_id']]);
    return $q->fetch() ?: null;
}

function require_auth(): array
{
    $u = current_user();
    if (!$u)
        fail('Not authenticated. Please log in.', 401);
    return $u;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf']))
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrf_token(), $token))
        fail('Invalid CSRF token.', 403);
}

// ─── Business helpers ─────────────────────────────────────────────────────
function get_documents(int $id): array
{
    $q = db()->prepare('SELECT * FROM document_track WHERE student_id=? ORDER BY FIELD(document_type,"photo","identity","marksheet","transfer_certificate"), updated_at DESC');
    $q->execute([$id]);
    $out = [];
    foreach ($q as $d)
        $out[$d['document_type']] = $d;
    return $out;
}

function calc_readiness(array $u): array
{
    $fields = ['full_name', 'course', 'phone', 'admission_year', 'date_of_birth', 'gender', 'address', 'city', 'state', 'postal_code', 'guardian_name', 'guardian_phone'];
    $filled = 0;
    foreach ($fields as $f)
        if (!empty($u[$f]))
            $filled++;
    $docs = get_documents((int) $u['student_id']);
    $approved = 0;
    foreach (['photo', 'identity', 'marksheet', 'transfer_certificate'] as $k)
        if (($docs[$k]['status'] ?? '') === 'Approved')
            $approved++;
    $profile = (int) round($filled / count($fields) * 100);
    $score = (int) round($profile * .55 + ($approved / 4) * 45);
    return ['profile' => $profile, 'docs' => $approved, 'score' => $score, 'documents' => $docs];
}

function log_activity(int $id, string $action, string $detail = ''): void
{
    db()->prepare('INSERT INTO activity_log(student_id,action,detail) VALUES(?,?,?)')->execute([$id, $action, $detail]);
}

function ensure_tables(): void
{
    static $done = false;
    if ($done)
        return;
    $done = true;
    try {
        db()->exec("
            CREATE TABLE IF NOT EXISTS application_track (
              application_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              student_id INT UNSIGNED NOT NULL UNIQUE,
              status ENUM('Draft','Submitted','Under Review','Documents Pending','Correction Required','Waitlisted','Approved','Rejected') NOT NULL DEFAULT 'Submitted',
              status_note VARCHAR(255) NULL,
              submitted_at DATETIME NULL,
              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              CONSTRAINT fk_application_student FOREIGN KEY (student_id) REFERENCES student_login(student_id) ON DELETE CASCADE
            ) ENGINE=InnoDB;

            CREATE TABLE IF NOT EXISTS fee_payments (
              payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              student_id INT UNSIGNED NOT NULL,
              reference VARCHAR(64) NOT NULL UNIQUE,
              fee_type VARCHAR(60) NOT NULL,
              amount DECIMAL(10,2) NOT NULL,
              status ENUM('Paid','Pending','Failed') NOT NULL DEFAULT 'Pending',
              payment_method VARCHAR(50) DEFAULT 'StroWallet Checkout',
              paid_at DATETIME NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT fk_payment_student FOREIGN KEY (student_id) REFERENCES student_login(student_id) ON DELETE CASCADE
            ) ENGINE=InnoDB;

            CREATE TABLE IF NOT EXISTS student_notifications (
              notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              student_id INT UNSIGNED NOT NULL,
              title VARCHAR(120) NOT NULL,
              message TEXT NOT NULL,
              type ENUM('info','success','warning','alert') NOT NULL DEFAULT 'info',
              is_read TINYINT(1) NOT NULL DEFAULT 0,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT fk_notification_student FOREIGN KEY (student_id) REFERENCES student_login(student_id) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
    } catch (\Throwable $e) {
    }
}

function add_notification(int $student_id, string $title, string $message, string $type = 'info'): void
{
    ensure_tables();
    try {
        db()->prepare('INSERT INTO student_notifications(student_id,title,message,type) VALUES(?,?,?,?)')
            ->execute([$student_id, $title, $message, $type]);
    } catch (\Throwable $e) {
    }
}
