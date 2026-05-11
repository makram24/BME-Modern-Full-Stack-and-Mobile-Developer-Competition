<?php
declare(strict_types=1);

require_once __DIR__ . '/timetable_format.php';

/**
 * @return list<array{id: int, timetable_day: int, timetable_slot: int}>
 */
function timetable_slots_list(mysqli $db, int $assignmentId): array
{
    $stmt = $db->prepare(
        'SELECT id, timetable_day, timetable_slot FROM assignment_timetable_slots
         WHERE class_subject_assignment_id = ?
         ORDER BY timetable_day ASC, timetable_slot ASC, id ASC'
    );
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param('i', $assignmentId);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = [
            'id' => (int) $row['id'],
            'timetable_day' => (int) $row['timetable_day'],
            'timetable_slot' => (int) $row['timetable_slot'],
        ];
    }
    $stmt->close();

    return $out;
}

/**
 * @param list<int> $assignmentIds
 * @return array<int, list<array{id: int, timetable_day: int, timetable_slot: int}>>
 */
function timetable_slots_batch(mysqli $db, array $assignmentIds): array
{
    $assignmentIds = array_values(array_filter(array_map('intval', $assignmentIds), static fn (int $x): bool => $x > 0));
    if ($assignmentIds === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($assignmentIds), '?'));
    $types = str_repeat('i', count($assignmentIds));
    $stmt = $db->prepare(
        "SELECT id, class_subject_assignment_id, timetable_day, timetable_slot
         FROM assignment_timetable_slots
         WHERE class_subject_assignment_id IN ($placeholders)
         ORDER BY class_subject_assignment_id ASC, timetable_day ASC, timetable_slot ASC"
    );
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param($types, ...$assignmentIds);
    $stmt->execute();
    $res = $stmt->get_result();
    $by = [];
    while ($row = $res->fetch_assoc()) {
        $aid = (int) $row['class_subject_assignment_id'];
        if (!isset($by[$aid])) {
            $by[$aid] = [];
        }
        $by[$aid][] = [
            'id' => (int) $row['id'],
            'timetable_day' => (int) $row['timetable_day'],
            'timetable_slot' => (int) $row['timetable_slot'],
        ];
    }
    $stmt->close();

    return $by;
}

/**
 * @param list<array{timetable_day: int, timetable_slot: int}> $slots
 */
function timetable_slots_summary_string(array $slots): string
{
    if ($slots === []) {
        return '—';
    }
    $parts = [];
    foreach ($slots as $s) {
        $parts[] = timetable_summary((int) $s['timetable_day'], (int) $s['timetable_slot']);
    }

    return implode('; ', $parts);
}

/** @return string|null error message or null on success */
function timetable_slots_add(mysqli $db, int $assignmentId, int $day, int $slot): ?string
{
    if ($day < TIMETABLE_DAY_MIN || $day > TIMETABLE_DAY_MAX || $slot < TIMETABLE_SLOT_MIN || $slot > TIMETABLE_SLOT_MAX) {
        return 'Invalid day or period.';
    }
    $stmt = $db->prepare(
        'INSERT INTO assignment_timetable_slots (class_subject_assignment_id, timetable_day, timetable_slot) VALUES (?,?,?)'
    );
    if ($stmt === false) {
        return 'Database error (run sql/011_assignment_timetable_slots.sql?).';
    }
    $stmt->bind_param('iii', $assignmentId, $day, $slot);
    if (!$stmt->execute()) {
        $dup = ($stmt->errno === 1062) || ($db->errno === 1062);
        $stmt->close();
        if ($dup) {
            return 'That day and period is already set for this assignment.';
        }

        return 'Could not add slot.';
    }
    $stmt->close();

    return null;
}

function timetable_slots_delete(mysqli $db, int $slotRowId, int $assignmentId): bool
{
    $stmt = $db->prepare(
        'DELETE FROM assignment_timetable_slots WHERE id = ? AND class_subject_assignment_id = ? LIMIT 1'
    );
    if ($stmt === false) {
        return false;
    }
    $stmt->bind_param('ii', $slotRowId, $assignmentId);
    $ok = $stmt->execute() && $stmt->affected_rows > 0;
    $stmt->close();

    return $ok;
}
