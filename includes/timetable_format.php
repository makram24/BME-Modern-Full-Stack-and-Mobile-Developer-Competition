<?php
declare(strict_types=1);

/**
 * Phase 10.4 — display and validation for assignment timetable (day + slot).
 */

const TIMETABLE_DAY_MIN = 1;
const TIMETABLE_DAY_MAX = 7;
const TIMETABLE_SLOT_MIN = 1;
const TIMETABLE_SLOT_MAX = 12;

/** @return int|null null = unset / invalid */
function timetable_day_sanitize(mixed $raw): ?int
{
    if ($raw === null || $raw === '' || $raw === '0' || $raw === 0) {
        return null;
    }
    $d = (int) $raw;
    if ($d < TIMETABLE_DAY_MIN || $d > TIMETABLE_DAY_MAX) {
        return null;
    }

    return $d;
}

/** @return int|null null = unset / invalid */
function timetable_slot_sanitize(mixed $raw): ?int
{
    if ($raw === null || $raw === '' || $raw === '0' || $raw === 0) {
        return null;
    }
    $s = (int) $raw;
    if ($s < TIMETABLE_SLOT_MIN || $s > TIMETABLE_SLOT_MAX) {
        return null;
    }

    return $s;
}

/** @return array<int, string> 1 => Monday … */
function timetable_weekday_options(): array
{
    return [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];
}

function timetable_day_label(int $day): string
{
    if ($day < TIMETABLE_DAY_MIN || $day > TIMETABLE_DAY_MAX) {
        return '—';
    }

    return timetable_weekday_options()[$day] ?? '—';
}

function timetable_slot_label(int $slot): string
{
    if ($slot < TIMETABLE_SLOT_MIN || $slot > TIMETABLE_SLOT_MAX) {
        return '—';
    }

    return 'Period ' . $slot;
}

/** Human-readable one-liner for tables (unset → em dash). */
function timetable_summary(?int $day, ?int $slot): string
{
    $d = $day ?? 0;
    $s = $slot ?? 0;
    if ($d < 1 && $s < 1) {
        return '—';
    }
    $parts = [];
    if ($d >= TIMETABLE_DAY_MIN && $d <= TIMETABLE_DAY_MAX) {
        $parts[] = timetable_day_label($d);
    }
    if ($s >= TIMETABLE_SLOT_MIN && $s <= TIMETABLE_SLOT_MAX) {
        $parts[] = timetable_slot_label($s);
    }

    return $parts === [] ? '—' : implode(' · ', $parts);
}

/**
 * Build a sparse grid [day 1–7][slot 1–N] => list of cell labels (multiple subjects may clash).
 *
 * @param list<array<string, mixed>> $rows each with timetable_day, timetable_slot, subject_title
 * @return array{maxSlot: int, cells: array<int, array<int, list<string>>>}
 */
function timetable_build_week_grid(array $rows): array
{
    $cells = [];
    $maxSlot = 0;
    foreach ($rows as $r) {
        $d = (int) ($r['timetable_day'] ?? 0);
        $s = (int) ($r['timetable_slot'] ?? 0);
        if ($d < TIMETABLE_DAY_MIN || $d > TIMETABLE_DAY_MAX || $s < TIMETABLE_SLOT_MIN || $s > TIMETABLE_SLOT_MAX) {
            continue;
        }
        $maxSlot = max($maxSlot, $s);
        $title = (string) ($r['subject_title'] ?? '');
        if (!isset($cells[$d])) {
            $cells[$d] = [];
        }
        if (!isset($cells[$d][$s])) {
            $cells[$d][$s] = [];
        }
        $cells[$d][$s][] = $title;
    }
    if ($maxSlot < 1) {
        $maxSlot = 1;
    }
    $maxSlot = min(TIMETABLE_SLOT_MAX, $maxSlot);

    return ['maxSlot' => $maxSlot, 'cells' => $cells];
}

/** Placeholder HTML for a grid cell with no scheduled subject (not an error). */
function timetable_empty_cell_html(): string
{
    return '<span class="timetable-cell-empty d-inline-block w-100" title="No class scheduled this period">&nbsp;</span>';
}
