<?php
declare(strict_types=1);

/**
 * Phase 4 — read-only student data, always scoped by enrollment (user_id + year + class).
 */

function student_portal_nav(): array
{
    return [
        ['student_dashboard.php', 'Home'],
        ['student_subjects.php', 'Subjects'],
        ['student_results.php', 'Results'],
        ['student_record.php', 'My profile'],
        ['logout.php', 'Log out'],
    ];
}

/** Append to student portal URLs to keep academic year context (validated per page). */
function student_year_q(int $yearId): string
{
    return $yearId > 0 ? ('?year_id=' . $yearId) : '';
}

/**
 * @return array<int, array{0: string, 1: string}>
 */
function student_portal_nav_links(int $yearId): array
{
    $q = student_year_q($yearId);
    $out = [];
    foreach (student_portal_nav() as $item) {
        if ($item[0] === 'logout.php') {
            $out[] = $item;
        } else {
            $out[] = [$item[0] . $q, $item[1]];
        }
    }

    return $out;
}

/**
 * Academic years this student is enrolled in (for dropdown).
 *
 * @return list<array{id:int,label:string,is_current:int}>
 */
function student_enrolled_years_detail(mysqli $db, int $userId): array
{
    $stmt = $db->prepare(
        'SELECT DISTINCT ay.id, ay.label, ay.is_current
         FROM academic_years ay
         INNER JOIN class_enrollments ce ON ce.academic_year_id = ay.id
         WHERE ce.user_id = ?
         ORDER BY ay.date_end DESC'
    );
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param('i', $userId);
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

/** @return int[] */
function student_enrolled_year_ids(mysqli $db, int $userId): array
{
    $stmt = $db->prepare(
        'SELECT DISTINCT ce.academic_year_id FROM class_enrollments ce WHERE ce.user_id = ? ORDER BY ce.academic_year_id DESC'
    );
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $ids = [];
    while ($row = $res->fetch_assoc()) {
        $ids[] = (int) $row['academic_year_id'];
    }
    $stmt->close();

    return $ids;
}

function student_has_enrollment_for_year(mysqli $db, int $userId, int $yearId): bool
{
    $stmt = $db->prepare(
        'SELECT 1 FROM class_enrollments WHERE user_id = ? AND academic_year_id = ? LIMIT 1'
    );
    if ($stmt === false) {
        return false;
    }
    $stmt->bind_param('ii', $userId, $yearId);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $ok;
}

/**
 * Pick academic year row for this student: optional $requestedYearId, else current flag, else latest by date_end.
 *
 * @return array<string, mixed>|null
 */
function student_resolve_year(mysqli $db, int $userId, int $requestedYearId): ?array
{
    $enrolled = student_enrolled_year_ids($db, $userId);
    if ($enrolled === []) {
        return null;
    }

    $yearId = 0;
    if ($requestedYearId > 0 && in_array($requestedYearId, $enrolled, true)) {
        $yearId = $requestedYearId;
    } else {
        $stmt = $db->prepare(
            'SELECT ay.id FROM academic_years ay
             INNER JOIN class_enrollments ce ON ce.academic_year_id = ay.id
             WHERE ce.user_id = ? AND ay.is_current = 1
             LIMIT 1'
        );
        if ($stmt && $stmt->bind_param('i', $userId) && $stmt->execute()) {
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
                 INNER JOIN class_enrollments ce ON ce.academic_year_id = ay.id
                 WHERE ce.user_id = ?
                 ORDER BY ay.date_end DESC
                 LIMIT 1'
            );
            if ($stmt && $stmt->bind_param('i', $userId) && $stmt->execute()) {
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
        $yearId = $enrolled[0];
    }

    $stmt = $db->prepare('SELECT * FROM academic_years WHERE id = ? LIMIT 1');
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param('i', $yearId);
    $stmt->execute();
    $year = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $year ?: null;
}

/**
 * Enrollment + class row for this user and year.
 *
 * @return array<string, mixed>|null
 */
function student_enrollment_with_class(mysqli $db, int $userId, int $yearId): ?array
{
    $stmt = $db->prepare(
        'SELECT ce.id AS enrollment_id, ce.class_id, ce.academic_year_id,
                c.start_date, c.class_code, c.display_name
         FROM class_enrollments ce
         INNER JOIN classes c ON c.id = ce.class_id
         WHERE ce.user_id = ? AND ce.academic_year_id = ?
         LIMIT 1'
    );
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param('ii', $userId, $yearId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * @return list<array<string, mixed>>
 */
function student_assignments_for_class_year(mysqli $db, int $yearId, int $classId): array
{
    $stmt = $db->prepare(
        'SELECT csa.id AS assignment_id, csa.subject_id, s.title, s.description, s.required_books, s.lessons_outline,
                u.username AS teacher_username
         FROM class_subject_assignments csa
         INNER JOIN subjects s ON s.id = csa.subject_id
         INNER JOIN users u ON u.id = csa.teacher_user_id
         WHERE csa.academic_year_id = ? AND csa.class_id = ?
         ORDER BY s.title ASC'
    );
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param('ii', $yearId, $classId);
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
 * Assignment + subject + teacher only if the student is enrolled in that class and year.
 *
 * @return array<string, mixed>|null
 */
function student_assignment_for_user(mysqli $db, int $userId, int $assignmentId): ?array
{
    $stmt = $db->prepare(
        'SELECT csa.id AS assignment_id, csa.academic_year_id, csa.class_id, csa.subject_id,
                s.title, s.description, s.required_books, s.lessons_outline,
                u.username AS teacher_username,
                ay.label AS year_label
         FROM class_subject_assignments csa
         INNER JOIN class_enrollments ce
           ON ce.class_id = csa.class_id AND ce.academic_year_id = csa.academic_year_id AND ce.user_id = ?
         INNER JOIN subjects s ON s.id = csa.subject_id
         INNER JOIN users u ON u.id = csa.teacher_user_id
         INNER JOIN academic_years ay ON ay.id = csa.academic_year_id
         WHERE csa.id = ?
         LIMIT 1'
    );
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param('ii', $userId, $assignmentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * Grades for this student only within their class+year enrollment.
 *
 * @return list<array<string, mixed>>
 */
function student_grades_for_class_year(mysqli $db, int $userId, int $yearId, int $classId): array
{
    $stmt = $db->prepare(
        'SELECT g.id, g.grade_value, g.grade_type, g.label, g.created_at,
                s.title AS subject_title, csa.id AS assignment_id
         FROM grades g
         INNER JOIN class_subject_assignments csa ON csa.id = g.class_subject_assignment_id
         INNER JOIN subjects s ON s.id = csa.subject_id
         WHERE g.student_user_id = ?
           AND csa.academic_year_id = ?
           AND csa.class_id = ?
         ORDER BY s.title ASC, g.grade_type DESC, g.created_at ASC'
    );
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param('iii', $userId, $yearId, $classId);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = $row;
    }
    $stmt->close();

    return $out;
}
