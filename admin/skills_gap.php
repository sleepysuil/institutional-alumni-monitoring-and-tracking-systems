<?php
require_once '../includes/admin_header.php';
require_once '../includes/mining_helpers.php';

/*
 * Skills gap per program, built from what alumni actually reported:
 *  - skills employed alumni say were critical (Tracer Survey)
 *  - barriers unemployed alumni report (Tracer Survey "impact factor")
 *  - job relevance and employment rate
 * (The old page used a hard-coded skills list that never matched real industry names.)
 */
$profiles = getAlumniProfiles($pdo);

$stats = [];
foreach ($profiles as $p) {
    $g = &$stats[$p['program']];
    $g ??= ['total' => 0, 'resp' => 0, 'working' => 0, 'rated' => 0, 'relevant' => 0,
            'industries' => [], 'skills' => [], 'barriers' => []];
    $g['total']++;
    if ($p['status'] !== null) $g['resp']++;
    if ($p['working']) {
        $g['working']++;
        if ($p['relevance']) {
            $g['rated']++;
            if ($p['relevance'] === 'Highly Relevant') $g['relevant']++;
        }
        if ($p['industry']) $g['industries'][$p['industry']] = ($g['industries'][$p['industry']] ?? 0) + 1;
        foreach ($p['skills'] as $s) {
            $k = strtolower($s);
            $g['skills'][$k] = ['label' => $g['skills'][$k]['label'] ?? $s, 'n' => ($g['skills'][$k]['n'] ?? 0) + 1];
        }
    }
    if ($p['status'] === 'Unemployed' && $p['impact_factor']) {
        $g['barriers'][$p['impact_factor']] = ($g['barriers'][$p['impact_factor']] ?? 0) + 1;
    }
    unset($g);
}
ksort($stats);

function gapRecommendations(array $g): array {
    $rec = [];
    $empRate = $g['resp'] ? $g['working'] / $g['resp'] * 100 : null;
    $relRate = $g['rated'] >= 3 ? $g['relevant'] / $g['rated'] * 100 : null;

    if ($relRate !== null && $relRate < 60)
        $rec[] = 'Only ' . round($relRate) . '% of jobs are highly relevant: review curriculum alignment with the industries alumni actually enter.';
    if ($empRate !== null && $g['resp'] >= 3 && $empRate < 70)
        $rec[] = 'Employment rate is ' . round($empRate) . '%: strengthen career services and job placement support.';

    $map = [
        'Lack of relevant work experience'         => 'Expand internships / OJT partnerships so graduates gain work experience earlier.',
        'Insufficient qualifications or education' => 'Add certification prep and skills workshops to close qualification gaps.',
        'Economic downturn'                        => 'Add entrepreneurship and freelancing tracks as alternatives to scarce jobs.',
        'Inadequate networking opportunities'      => 'Hold more job fairs, alumni mentoring and industry linkage events.',
    ];
    $barriers = $g['barriers'];
    arsort($barriers);
    foreach (array_slice(array_keys($barriers), 0, 2) as $b) {
        if (isset($map[$b])) $rec[] = $map[$b];
    }
    return $rec ?: ['No critical gaps detected from the current data.'];
}
?>
<div class="page-header">
    <h1>Skills Gap Analysis</h1>
    <p class="text-muted mb-0">Built from tracer survey answers and employment data</p>
</div>

<?php if (empty($stats)): ?>
    <div class="alert alert-warning">No alumni data available yet.</div>
<?php endif; ?>

<?php foreach ($stats as $program => $g):
    $empRate = $g['resp'] ? round($g['working'] / $g['resp'] * 100, 1) : 0;
    $relRate = $g['rated'] ? round($g['relevant'] / $g['rated'] * 100, 1) : null;
    arsort($g['industries']);
    uasort($g['skills'], fn($a, $b) => $b['n'] <=> $a['n']);
    arsort($g['barriers']);
?>
<div class="card mb-4">
    <div class="card-header"><?= htmlspecialchars($program) ?> <small class="text-muted">· <?= $g['total'] ?> alumni, <?= $g['resp'] ?> responded</small></div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <p class="mb-1"><strong>Employment rate:</strong> <?= $empRate ?>%</p>
                <div class="progress mb-3" style="height: 22px;">
                    <div class="progress-bar bg-success" style="width: <?= $empRate ?>%;"><?= $empRate ?>%</div>
                    <div class="progress-bar bg-danger" style="width: <?= $g['resp'] ? 100 - $empRate : 0 ?>%;"></div>
                </div>

                <p class="mb-1"><strong>Highly relevant jobs:</strong>
                    <?= $relRate === null ? '<span class="text-muted">no relevance data</span>' : $relRate . '%' ?></p>
                <?php if ($relRate !== null): ?>
                <div class="progress mb-3" style="height: 22px;">
                    <div class="progress-bar" style="width: <?= $relRate ?>%; background:#388087;"><?= $relRate ?>%</div>
                </div>
                <?php endif; ?>

                <h6 class="mt-3">Top industries</h6>
                <?php if ($g['industries']): ?>
                    <ul class="mb-0">
                    <?php foreach (array_slice($g['industries'], 0, 3, true) as $ind => $n): ?>
                        <li><?= htmlspecialchars($ind) ?> <span class="badge bg-secondary"><?= $n ?></span></li>
                    <?php endforeach; ?>
                    </ul>
                <?php else: ?><p class="text-muted small mb-0">No industry data.</p><?php endif; ?>
            </div>

            <div class="col-md-6">
                <h6>Skills employed alumni found critical</h6>
                <?php if ($g['skills']): ?>
                    <p>
                    <?php foreach (array_slice($g['skills'], 0, 6) as $s): ?>
                        <span class="badge bg-info me-1 mb-1"><?= htmlspecialchars($s['label']) ?> (<?= $s['n'] ?>)</span>
                    <?php endforeach; ?>
                    </p>
                <?php else: ?><p class="text-muted small">No survey skills yet.</p><?php endif; ?>

                <h6>Barriers reported by unemployed alumni</h6>
                <?php if ($g['barriers']): ?>
                    <ul>
                    <?php foreach (array_slice($g['barriers'], 0, 3, true) as $b => $n): ?>
                        <li><?= htmlspecialchars($b) ?> <span class="badge bg-secondary"><?= $n ?></span></li>
                    <?php endforeach; ?>
                    </ul>
                <?php else: ?><p class="text-muted small">None reported.</p><?php endif; ?>

                <h6>Recommendations</h6>
                <ul class="mb-0">
                    <?php foreach (gapRecommendations($g) as $r): ?><li><?= htmlspecialchars($r) ?></li><?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include '../includes/footer.php'; ?>