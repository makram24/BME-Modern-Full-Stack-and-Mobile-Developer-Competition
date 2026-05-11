<?php
declare(strict_types=1);

/**
 * Phase 10.1 — weighted periodic grade averages (semester and year-end excluded from this average).
 */

/** Minimum / maximum weight for a periodic grade entry. */
const GRADE_WEIGHT_MIN = 0.25;
const GRADE_WEIGHT_MAX = 10.0;

function grade_weight_default(): float
{
    return 1.0;
}

/**
 * Parse weight from form input. Empty uses default 1.0.
 *
 * @return float|null null if out of range or not numeric
 */
function parse_grade_weight(mixed $raw): ?float
{
    if ($raw === null || $raw === '') {
        return grade_weight_default();
    }
    $s = is_string($raw) ? str_replace(',', '.', trim($raw)) : (string) $raw;
    if ($s === '' || !is_numeric($s)) {
        return null;
    }
    $w = round((float) $s, 2);
    if ($w < GRADE_WEIGHT_MIN || $w > GRADE_WEIGHT_MAX) {
        return null;
    }

    return $w;
}

/**
 * Weighted mean of periodic grades (1–5) using each row's weight.
 *
 * @param list<array<string, mixed>> $periodicRows rows with grade_value, optional weight
 */
function weighted_periodic_grade_average(array $periodicRows): ?float
{
    $sumW = 0.0;
    $sumVW = 0.0;
    foreach ($periodicRows as $r) {
        $v = (int) ($r['grade_value'] ?? 0);
        if ($v < 1 || $v > 5) {
            continue;
        }
        $w = isset($r['weight']) ? (float) $r['weight'] : grade_weight_default();
        if ($w <= 0.0) {
            continue;
        }
        $sumVW += $v * $w;
        $sumW += $w;
    }
    if ($sumW <= 0.0) {
        return null;
    }

    return $sumVW / $sumW;
}

function format_grade_average(?float $avg): string
{
    if ($avg === null) {
        return '—';
    }

    return number_format($avg, 2, '.', '');
}
