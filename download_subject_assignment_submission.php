<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('teacher');

require_once __DIR__ . '/dbConnect.php';

$tid = (int) current_user_id();
$submissionId = isset($_GET['submission_id']) ? (int) $_GET['submission_id'] : 0;
if ($tid <= 0 || $submissionId <= 0) {
    http_response_code(404);
    exit('Missing submission reference.');
}

$stmt = $mysqli->prepare(
    'SELECT sas.file_original_name,
            sas.file_stored_path
     FROM subject_assignment_submissions sas
     INNER JOIN subject_assignments sa ON sa.id = sas.subject_assignment_id
     INNER JOIN class_subject_assignments csa ON csa.id = sa.class_subject_assignment_id
     WHERE sas.id = ? AND csa.teacher_user_id = ?
     LIMIT 1'
);
if ($stmt === false) {
    http_response_code(500);
    exit('Database error.');
}
$stmt->bind_param('ii', $submissionId, $tid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row === null) {
    http_response_code(403);
    exit('Not allowed.');
}

$fileOriginalName = (string) ($row['file_original_name'] ?? 'download');
$fileStoredPathRel = (string) ($row['file_stored_path'] ?? '');

$uploadsRoot = realpath(__DIR__ . '/uploads');
$fullPath = $fileStoredPathRel !== '' ? realpath($uploadsRoot . DIRECTORY_SEPARATOR . $fileStoredPathRel) : false;
if ($uploadsRoot === false || $fullPath === false || strpos($fullPath, $uploadsRoot) !== 0) {
    http_response_code(404);
    exit('File not found.');
}

if (!is_file($fullPath)) {
    http_response_code(404);
    exit('File not found.');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . rawurlencode($fileOriginalName) . '"');
header('Content-Length: ' . (string) filesize($fullPath));

readfile($fullPath);
exit;

