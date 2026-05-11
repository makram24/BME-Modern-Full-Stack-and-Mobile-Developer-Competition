<?php
declare(strict_types=1);

/**
 * Phase 10.3 — campus events (listing helpers; admin pages own mutations).
 */

/**
 * @return list<array<string, mixed>>
 */
function events_academic_years_for_select(mysqli $db): array
{
    $res = $db->query('SELECT id, label FROM academic_years ORDER BY date_end DESC, id DESC');
    if ($res === false) {
        return [];
    }
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $res->close();

    return $rows;
}

/**
 * Events for the public calendar (newest first). Optional year filter: NULL or 0 = all.
 *
 * @return list<array<string, mixed>>
 */
function events_list_for_portal(mysqli $db, int $filterYearId = 0): array
{
    if ($filterYearId > 0) {
        $stmt = $db->prepare(
            'SELECT e.id, e.title, e.description, e.starts_at, e.ends_at, e.location, e.academic_year_id, ay.label AS year_label
             FROM campus_events e
             LEFT JOIN academic_years ay ON ay.id = e.academic_year_id
             WHERE e.academic_year_id IS NULL OR e.academic_year_id = ?
             ORDER BY e.starts_at DESC, e.id DESC'
        );
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('i', $filterYearId);
    } else {
        $stmt = $db->prepare(
            'SELECT e.id, e.title, e.description, e.starts_at, e.ends_at, e.location, e.academic_year_id, ay.label AS year_label
             FROM campus_events e
             LEFT JOIN academic_years ay ON ay.id = e.academic_year_id
             ORDER BY e.starts_at DESC, e.id DESC'
        );
        if ($stmt === false) {
            return [];
        }
    }
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
function admin_event_by_id(mysqli $db, int $id): ?array
{
    $stmt = $db->prepare(
        'SELECT id, title, description, starts_at, ends_at, location, academic_year_id
         FROM campus_events WHERE id = ? LIMIT 1'
    );
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}
