<?php
require_once '../includes/admin_header.php';
require_once '../includes/mining_helpers.php';

/*
 * Rule: "graduates of PROGRAM work in INDUSTRY"
 * Only alumni who are working AND have an industry are counted, so support,
 * confidence and lift all use the same base (the old page mixed all alumni
 * with employed-only counts, which made lift wrong).
 */
$working = array_values(array_filter(getAlumniProfiles($pdo), fn($p) => $p['working'] && $p['industry']));
$N = count($working);

$programCounts = $industryCounts = $pairs = [];
foreach ($working as $p) {
    $programCounts[$p['program']]   = ($programCounts[$p['program']] ?? 0) + 1;
    $industryCounts[$p['industry']] = ($industryCounts[$p['industry']] ?? 0) + 1;
    $key = $p['program'] . '|' . $p['industry'];
    $pairs[$key] = ($pairs[$key] ?? 0) + 1;
}

$rules = [];
foreach ($pairs as $key => $count) {
    [$program, $industry] = explode('|', $key, 2);
    $support    = $count / $N;
    $confidence = $count / $programCounts[$program];
    $expected   = $industryCounts[$industry] / $N;
    $lift       = $expected ? $confidence / $expected : 0;
    $rules[] = [
        'program'    => $program,
        'industry'   => $industry,
        'count'      => $count,
        'support'    => round($support * 100, 1),
        'confidence' => round($confidence * 100, 1),
        'lift'       => round($lift, 2),
        'strength'   => $lift > 1.2 ? 'Strong' : ($lift >= 0.8 ? 'Moderate' : 'Weak'),
    ];
}
usort($rules, fn($a, $b) => [$b['lift'], $b['count']] <=> [$a['lift'], $a['count']]);
?>
<div class="page-header">
    <h1>Association Rules</h1>
    <p class="text-muted mb-0">Program → Industry (<?= $N ?> working alumni with a known industry)</p>
</div>

<?php if (empty($rules)): ?>
    <div class="alert alert-warning">No association data yet. Alumni need to be Employed/Self-Employed with an industry (from the Tracer Survey job category or their profile).</div>
<?php else: ?>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>Program</th><th>Industry</th><th>Alumni</th><th>Support (%)</th><th>Confidence (%)</th><th>Lift</th><th>Strength</th></tr>
                </thead>
                <tbody>
                <?php foreach ($rules as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['program']) ?></td>
                        <td><?= htmlspecialchars($r['industry']) ?></td>
                        <td><?= $r['count'] ?></td>
                        <td><?= $r['support'] ?></td>
                        <td><?= $r['confidence'] ?></td>
                        <td><?= $r['lift'] ?></td>
                        <td><span class="badge bg-<?= $r['strength'] == 'Strong' ? 'success' : ($r['strength'] == 'Moderate' ? 'warning' : 'secondary') ?>"><?= $r['strength'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="alert alert-info mt-3 mb-0">
            <strong>Understanding the metrics:</strong>
            <b>Support</b> – share of all working alumni with this program + industry.
            <b>Confidence</b> – chance a graduate of the program works in that industry.
            <b>Lift</b> – how much more likely than random (above 1 = positive association).
            <?php if ($N < 20): ?><br><em>Only <?= $N ?> records so far, so treat these as indicative until more alumni respond.</em><?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>