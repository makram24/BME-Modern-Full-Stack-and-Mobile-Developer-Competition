<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';

/**
 * Teacher-created assignments (subject_assignments) + student file uploads (subject_assignment_submissions).
 *
 * Returns errors as strings for UI redirections.
 */

const SUBJECT_UPLOAD_MAX_BYTES = 10_485_760; // 10 MiB

/**
 * @return array<int, string> allowed extensions (lowercase, without dot)
 */
function subject_upload_allowed_extensions(): array
{
    return ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
}

/**
 * @param array{title?:mixed,instructions?:mixed,due_at?:mixed} $raw
 */
function subject_assignment_validate_create(array $raw): array
{
    $title = isset($raw['title']) ? trim((string) $raw['title']) : '';
    $instructions = isset($raw['instructions']) ? trim((string) $raw['instructions']) : '';
    $dueAtRaw = isset($raw['due_at']) ? (string) $raw['due_at'] : '';

    if ($title === '') {
        return ['err' => 'Title is required.'];
    }
    if (mb_strlen($title) > 255) {
        return ['err' => 'Title is too long.'];
    }
    if (mb_strlen($instructions) > 5000) {
        return ['err' => 'Instructions are too long.'];
    }

    $dueAtDb = null;
    if ($dueAtRaw !== '') {
        // Expect datetime-local: "YYYY-MM-DDTHH:MM"
        $ts = strtotime($dueAtRaw);
        if ($ts === false) {
            return ['err' => 'Invalid due date/time.'];
        }
        $dueAtDb = date('Y-m-d H:i:s', $ts);
    }

    return ['err' => null, 'title' => $title, 'instructions' => ($instructions === '' ? null : $instructions), 'due_at' => $dueAtDb];
}

/**
 * @return list<array{id:int,title:string,instructions:?string,due_at:?string,created_at:string}>
 */
function subject_assignments_list(mysqli $db, int $classSubjectAssignmentId): array
{
    $stmt = $db->prepare(
        'SELECT id, title, instructions, due_at, created_at
         FROM subject_assignments
         WHERE class_subject_assignment_id = ?
         ORDER BY due_at IS NULL, due_at ASC, created_at DESC'
    );
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param('i', $classSubjectAssignmentId);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'instructions' => $row['instructions'] !== null ? (string) $row['instructions'] : null,
            'due_at' => $row['due_at'] !== null ? (string) $row['due_at'] : null,
            'created_at' => (string) $row['created_at'],
        ];
    }
    $stmt->close();
    return $out;
}

/** @return list<array{id:int,subject_assignment_id:int,student_user_id:int,file_original_name:string,file_stored_path:string,submitted_at:string}> */
function subject_submissions_list_for_class_subject(mysqli $db, int $classSubjectAssignmentId, array $studentIds): array
{
    $studentIds = array_values(array_filter(array_map('intval', $studentIds), static fn (int $x): bool => $x > 0));
    if ($studentIds === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $types = str_repeat('i', count($studentIds));

    $stmt = $db->prepare(
        "SELECT sas.id AS submission_id,
                sas.subject_assignment_id,
                sas.student_user_id,
                sas.file_original_name,
                sas.file_stored_path,
                sas.submitted_at
         FROM subject_assignment_submissions sas
         INNER JOIN subject_assignments sa ON sa.id = sas.subject_assignment_id
         WHERE sa.class_subject_assignment_id = ?
           AND sas.student_user_id IN ($placeholders)
         ORDER BY sas.subject_assignment_id ASC, sas.student_user_id ASC"
    );
    if ($stmt === false) {
        return [];
    }
    $types2 = 'i' . $types;
    $params = array_merge([$classSubjectAssignmentId], $studentIds);
    $stmt->bind_param($types2, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = [
            'id' => (int) $row['submission_id'],
            'subject_assignment_id' => (int) $row['subject_assignment_id'],
            'student_user_id' => (int) $row['student_user_id'],
            'file_original_name' => (string) $row['file_original_name'],
            'file_stored_path' => (string) $row['file_stored_path'],
            'submitted_at' => (string) $row['submitted_at'],
        ];
    }
    $stmt->close();
    return $out;
}

function subject_assignment_create(mysqli $db, int $classSubjectAssignmentId, int $teacherUserId, string $title, ?string $instructions, ?string $dueAtDb): ?string
{
    // Ownership check: only teacher assigned to this class-subject can create
    $chk = $db->prepare(
        'SELECT 1
         FROM class_subject_assignments csa
         WHERE csa.id = ? AND csa.teacher_user_id = ?
         LIMIT 1'
    );
    if ($chk === false) {
        return 'Database error.';
    }
    $chk->bind_param('ii', $classSubjectAssignmentId, $teacherUserId);
    $chk->execute();
    $ok = $chk->get_result()->num_rows > 0;
    $chk->close();

    if (!$ok) {
        return 'You cannot create assignments for this subject offering.';
    }

    $stmt = $db->prepare(
        'INSERT INTO subject_assignments (class_subject_assignment_id, title, instructions, due_at, created_by_user_id)
         VALUES (?,?,?,?,?)'
    );
    if ($stmt === false) {
        return 'Database error.';
    }
    $stmt->bind_param(
        'isssi',
        $classSubjectAssignmentId,
        $title,
        $instructions,
        $dueAtDb,
        $teacherUserId
    );
    $stmt->execute();
    $stmt->close();

    return null;
}

function subject_assignment_delete(mysqli $db, int $subjectAssignmentId, int $teacherUserId): bool
{
    $stmt = $db->prepare(
        'DELETE sasn
         FROM subject_assignments sasn
         INNER JOIN class_subject_assignments csa ON csa.id = sasn.class_subject_assignment_id
         WHERE sasn.id = ? AND csa.teacher_user_id = ?
         LIMIT 1'
    );
    if ($stmt === false) {
        return false;
    }
    $stmt->bind_param('ii', $subjectAssignmentId, $teacherUserId);
    $ok = $stmt->execute() && $stmt->affected_rows > 0;
    $stmt->close();
    return $ok;
}

/**
 * @return array{ id:int, file_original_name:string, file_stored_path:string, submitted_at:string }|null
 */
function subject_submission_get(mysqli $db, int $subjectAssignmentId, int $studentUserId): ?array
{
    $stmt = $db->prepare(
        'SELECT id, file_original_name, file_stored_path, submitted_at
         FROM subject_assignment_submissions
         WHERE subject_assignment_id = ? AND student_user_id = ?
         LIMIT 1'
    );
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param('ii', $subjectAssignmentId, $studentUserId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row === null) {
        return null;
    }

    return [
        'id' => (int) $row['id'],
        'file_original_name' => (string) $row['file_original_name'],
        'file_stored_path' => (string) $row['file_stored_path'],
        'submitted_at' => (string) $row['submitted_at'],
    ];
}

function subject_submission_upsert(
    mysqli $db,
    int $subjectAssignmentId,
    int $studentUserId,
    string $fileOriginalName,
    string $fileStoredPathRel
): bool {
    $stmt = $db->prepare(
        'INSERT INTO subject_assignment_submissions (subject_assignment_id, student_user_id, file_original_name, file_stored_path)
         VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE
           file_original_name = VALUES(file_original_name),
           file_stored_path = VALUES(file_stored_path),
           submitted_at = CURRENT_TIMESTAMP'
    );
    if ($stmt === false) {
        return false;
    }
    $stmt->bind_param('iiss', $subjectAssignmentId, $studentUserId, $fileOriginalName, $fileStoredPathRel);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

