<?php
/**
 * Shared helpers for the Data Mining pages.
 *
 * WHY THIS EXISTS
 * Alumni answer the Tracer Survey (tracer_responses table) but the old mining
 * pages only read the `employment` table, which is filled only from "My Profile".
 * Result: most alumni looked like "no data". getAlumniProfiles() merges both
 * sources: the LATEST survey wins, the employment table is used as fallback.
 */

/** Decode text that cleanInput() stored as HTML entities (so we escape only once on output). */
function cleanText($s) {
    $s = trim(html_entity_decode((string)$s, ENT_QUOTES, 'UTF-8'));
    return $s === '' ? null : preg_replace('/\s+/', ' ', $s);
}

/** "₱25,000 - ₱35,000" => 30000 (midpoint). "₱30,000" => 30000. Returns null if no number. */
function parseSalary($range) {
    if ($range === null || $range === '') return null;
    if (!preg_match_all('/\d[\d,]*(?:\.\d+)?/', $range, $m)) return null;
    $nums = [];
    foreach ($m[0] as $n) {
        $v = (float) str_replace(',', '', $n);
        if ($v > 0) $nums[] = $v;
    }
    return $nums ? array_sum($nums) / count($nums) : null;
}

/** Survey "time to first job" answer => approximate months. */
function timeLabelToMonths($label) {
    static $map = [
        'Less than 3 months' => 1.5,
        '3 to 6 months'      => 4.5,
        '6 to 12 months'     => 9,
        'Over 1 year'        => 15,
    ];
    return $map[$label] ?? null;
}

/** Wilson 95% confidence interval for a proportion k/n. Returns [low, high] as 0..1. */
function wilsonInterval($k, $n, $z = 1.96) {
    if ($n <= 0) return [0, 0];
    $p = $k / $n;
    $den = 1 + $z * $z / $n;
    $centre = $p + $z * $z / (2 * $n);
    $margin = $z * sqrt(($p * (1 - $p) + $z * $z / (4 * $n)) / $n);
    return [max(0, ($centre - $margin) / $den), min(1, ($centre + $margin) / $den)];
}

/**
 * One unified row per alumni.
 * Keys: id, name, program, year, status (Employed|Self-Employed|Unemployed|Pursuing Higher Education|null),
 *       working (bool), industry, position, company, salary (float|null), salary_range, relevance,
 *       start_date, time_to_first_job, seeking_time, impact_factor, skills[], has_survey
 */
function getAlumniProfiles(PDO $pdo): array {
    $sql = "SELECT a.id, a.first_name, a.last_name, a.program, a.graduation_year,
                   e.status AS e_status, e.industry AS e_industry, e.position AS e_position,
                   e.company AS e_company, e.salary_range, e.relevance, e.start_date,
                   t.is_employed AS t_employed, t.job_title AS t_title, t.industry AS t_industry,
                   t.company AS t_company, t.survey_data
            FROM alumni a
            LEFT JOIN employment e ON e.alumni_id = a.id
            LEFT JOIN tracer_responses t ON t.id = (
                SELECT t2.id FROM tracer_responses t2
                WHERE t2.alumni_id = a.id
                ORDER BY t2.response_date DESC, t2.id DESC LIMIT 1
            )";
    $rows = $pdo->query($sql)->fetchAll();

    $statusMap = [0 => 'Unemployed', 1 => 'Employed', 2 => 'Self-Employed'];
    $out = [];

    foreach ($rows as $r) {
        if (isset($out[$r['id']])) continue; // guard against duplicate employment rows

        $survey = $r['survey_data'] ? json_decode($r['survey_data'], true) : [];
        if (!is_array($survey)) $survey = [];

        // Status: latest survey first, then employment table
        $status = null;
        if ($r['t_employed'] !== null && isset($statusMap[(int)$r['t_employed']])) {
            $status = $statusMap[(int)$r['t_employed']];
        } elseif (!empty($r['e_status'])) {
            $status = $r['e_status'];
        }
        $working = in_array($status, ['Employed', 'Self-Employed'], true);

        // Industry: survey category > survey business industry > free-text fields
        $industry = null;
        if ($working) {
            $candidates = [
                $survey['job_category'] ?? null,
                $survey['business_industry'] ?? null,
                $r['t_industry'],
                $r['e_industry'],
            ];
            foreach ($candidates as $c) {
                $c = cleanText($c);
                if ($c && strcasecmp($c, 'Other') !== 0) { $industry = $c; break; }
            }
        }

        $position = null;
        if ($working) {
            foreach ([$survey['job_title_desc'] ?? null, $r['t_title'], $r['e_position'], $survey['business_type'] ?? null] as $c) {
                $c = cleanText($c);
                if ($c) { $position = $c; break; }
            }
        }

        $skills = [];
        foreach (($survey['skills_critical'] ?? []) as $s) {
            $s = cleanText($s);
            if ($s) $skills[] = $s;
        }

        $out[$r['id']] = [
            'id'                => (int)$r['id'],
            'name'              => trim($r['first_name'] . ' ' . $r['last_name']),
            'program'           => $r['program'],
            'year'              => (int)$r['graduation_year'],
            'status'            => $status,
            'working'           => $working,
            'industry'          => $industry,
            'position'          => $position,
            'company'           => cleanText($survey['employer_name'] ?? $r['t_company'] ?? $r['e_company']),
            'salary'            => parseSalary($r['salary_range']),
            'salary_range'      => $r['salary_range'],
            'relevance'         => $r['relevance'],
            'start_date'        => $r['start_date'],
            'time_to_first_job' => $survey['time_to_first_job'] ?? null,
            'seeking_time'      => $survey['seeking_time'] ?? null,
            'impact_factor'     => $survey['impact_factor'] ?? null,
            'skills'            => $skills,
            'has_survey'        => $r['survey_data'] !== null,
        ];
    }
    return array_values($out);
}