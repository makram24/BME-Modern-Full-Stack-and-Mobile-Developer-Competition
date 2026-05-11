<?php
declare(strict_types=1);

/**
 * Phase 5 — teacher assignments and grades (scoped to class_subject_assignments.teacher_user_id).
 */

function teacher_portal_nav(): array
{
    return [
        ['teacher_dashboard.php', 'Home'],
        ['teacher_assignments.php', 'My subjects'],
        ['logout.php', 'Log out'],
    ];
}

function teacher_year_q(int $yearId): string
{
    return $yearId > 0 ? ('?year_id=' . $yearId) : '';
}

/**
 * @return array<int, array{0: string, 1: string}>
 */
function teacher_portal_nav_links(int $yearId): array
{
    $q = teacher_year_q($yearId);
    $out = [];
    foreach (teacher_portal_nav() as $item) {
        if ($item[0] === 'logout.php') {
            $out[] = $item;
        } else {
            $out[] = [$item[0] . $q, $item[1]];
        }
    }

    return $out;
}

/** @return int[] */
function teacher_assigned_year_ids(mysqli $db, int $teacherId): array
{
    $stmt = $db->prepare(
        'SELECT DISTINCT csa.academic_year_id
         FROM class_subject_assignments csa
         WHERE csa.teacher_user_id = ?
         ORDER BY csa.academic_year_id DESC'
    );
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param('i', $teacherId);
    $stmt->execute();
    $res = $stmt->get_result();
    $ids = [];
    while ($row = $res->fetch_assoc()) {
        $ids[] = (int) $row['academic_year_id'];
    }
    $stmt->close();

    return $ids;
}

/**
 * @return list<array{id:int,label:string,is_current:int}>
 */
function teacher_assigned_years_detail(mysqli $db, int $teacherId): array
{
    $stmt = $db->prepare(
        'SELECT DISTINCT ay.id, ay.label, ay.is_current, ay.date_end
         FROM academic_years ay
         INNER JOIN class_subject_assignments csa ON csa.academic_year_id = ay.id
         WHERE csa.teacher_user_id = ?
         ORDER BY ay.date_end DESC'
    );
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param('i', $teacherId);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = [
            'id' => (int) $row['id'],
            'label' => (string) $row['label'],
            'is_current' => (int) $row['is_current'],
        ];
    }
    $stmt->close();

    return $out;
}

/**
 * @return array<string, mixed>|null
 */
function teacher_resolve_year(mysqli $db, int $teacherId, int $requestedYearId): ?array
{
    $ids = teacher_assigned_year_ids($db, $teacherId);
    if ($ids === []) {
        return null;
    }

    $yearId = 0;
    if ($requestedYearId > 0 && in_array($requestedYearId, $ids, true)) {
        $yearId = $requestedYearId;
    } else {
        $stmt = $db->prepare(
            'SELECT ay.id FROM academic_years ay
             INNER JOIN class_subject_assignments csa ON csa.academic_year_id = ay.id
             WHERE csa.teacher_user_id = ? AND ay.is_current = 1
             LIMIT 1'
        );
        if ($stmt && $stmt->bind_param('i', $teacherId) && $stmt->execute()) {
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                $yearId = (int) $row['id'];
            }
        } elseif ($stmt) {
            $stmt->close();
        }
        if ($yearId === 0) {
            $stmt = $db->prepare(
                'SELECT ay.id FROM academic_years ay
                 INNER JOIN class_subject_assignments csa ON csa.academic_year_id = ay.id
                 WHERE csa.teacher_user_id = ?
                 ORDER BY ay.date_end DESC
                 LIMIT 1'
            );
            if ($stmt && $stmt->bind_param('i', $teacherId) && $stmt->execute()) {
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row) {
                    $yearId = (int) $row['id'];
                }
            } elseif ($stmt) {
                $stmt->close();
            }
        }
    }

    if ($yearId === 0) {
        $yearId = $ids[0];
    }

    $stmt = $db->prepare('SELECT * FROM academic_years WHERE id = ? LIMIT 1');
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param('i', $yearId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * @return list<array<string, mixed>>
 */
function teacher_assignments_for_year(mysqli $db, int $teacherId, int $yearId): array
{
    $stmt = $db->prepare(
        'SELECT csa.id AS assignment_id, csa.class_id, csa.academic_year_id, csa.subject_id,
                s.title AS subject_title,
                c.class_code, c.start_date, c.display_name
         FROM class_subject_assignments csa
         INNER JOIN subjects s ON s.id = csa.subject_id
         INNER JOIN classes c ON c.id = csa.class_id
         WHERE csa.teacher_user_id = ? AND csa.academic_year_id = ?
         ORDER BY s.title ASC, c.class_code ASC'
    );
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param('ii', $teacherId, $yearId);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = $row;
    }
    $stmt->close();

    return $out;
}

/**
 * @return array<string, mixed>|null
 */
function teacher_assignment_for_teacher(mysqli $db, int $teacherId, int $assignmentId): ?array
{
    $stmt = $db->prepare(
        'SELECT csa.id AS assignment_id, csa.class_id, csa.academic_year_id, csa.subject_id,
                s.title AS subject_title, s.description,
                c.class_code, c.start_date, c.display_name,
                ay.label AS year_label
         FROM class_subject_assignments csa
         INNER JOIN subjects s ON s.id = csa.subject_id
         INNER JOIN classes c ON c.id = csa.class_id
         INNER JOIN academic_years ay ON ay.id = csa.academic_year_id
         WHERE csa.id = ? AND csa.teacher_user_id = ?
         LIMIT 1'
    );
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param('ii', $assignmentId, $teacherId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * @return list<array<string, mixed>>
 */
function teacher_roster(mysqli $db, int $classId, int $yearId): array
{
    $stmt = $db->prepare(
        'SELECT u.id AS student_id, u.username
         FROM class_enrollments ce
         INNER JOIN users u ON u.id = ce.user_id
         WHERE ce.class_id = ? AND ce.academic_year_id = ?
         ORDER BY u.username ASC'
    );
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param('ii', $classId, $yearId);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = $row;
    }
    $stmt->close();

    return $out;
}

/**
 * @return list<array<string, mixed>>
 */
function teacher_grades_for_assignment(mysqli $db, int $assignmentId): array
{
    $stmt = $db->prepare(
        'SELECT g.id, g.student_user_id, g.grade_value, g.grade_type, g.label, g.created_at, u.username
         FROM grades g
         INNER JOIN users u ON u.id = g.student_user_id
         WHERE g.class_subject_assignment_id = ?
         ORDER BY u.username ASC, g.grade_type ASC, g.created_at ASC'
    );
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param('i', $assignmentId);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = $row;
    }
    $stmt->close();

    return $out;
}

function teacher_student_in_class(mysqli $db, int $classId, int $yearId, int $studentId): bool
{
    $stmt = $db->prepare(
        'SELECT 1 FROM class_enrollments WHERE class_id = ? AND academic_year_id = ? AND user_id = ? LIMIT 1'
    );
    if ($stmt === false) {
        return false;
    }
    $stmt->bind_param('iii', $classId, $yearId, $studentId);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $ok;
}

/** Portal uses integer scale 1–5 for all graded entries. */
function teacher_grade_value_valid(string $value): bool
{
    return (bool) preg_match('/^[1-5]$/', trim($value));
}

function teacher_assignment_url(int $assignmentId, int $yearId, string $extraQuery = ''): string
{
    $u = 'teacher_assignment.php?assignment_id=' . $assignmentId;
    if ($yearId > 0) {
        $u .= '&year_id=' . $yearId;
    }
    if ($extraQuery !== '') {
        $u .= '&' . ltrim($extraQuery, '&');
    }

    return $u;
}

function teacher_redirect_assignment(int $assignmentId, int $yearId, string $extraQuery = ''): void
{
    header('Location: ' . teacher_assignment_url($assignmentId, $yearId, $extraQuery));
    exit;
}
