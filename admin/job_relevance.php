<?php
require_once '../includes/admin_header.php';
require_once '../includes/mining_helpers.php';   // same data source as the Tracer Overview

/*
 * Percentages are now calculated over jobs that actually have a relevance rating.
 * (Before, the count was divided by all employed alumni, so the percentages
 * could add up to less than 100%.)
 */
$levels = ['Highly Relevant', 'Somewhat Relevant', 'Not Relevant'];
$colors = ['Highly Relevant' => '#388087', 'Somewhat Relevant' => '#6FB3B3', 'Not Relevant' => '#EF9A9A'];

$totals = array_fill_keys($levels, 0);
$byProgram = [];
$working = 0;
foreach (getAlumniProfiles($pdo) as $p) {
    if (!$p['working']) continue;
    $working++;
    if (!$p['relevance'] || !isset($totals[$p['relevance']])) continue;
    $totals[$p['relevance']]++;
    $byProgram[$p['program']] ??= array_fill_keys($levels, 0);
    $byProgram[$p['program']][$p['relevance']]++;
}
ksort($byProgram);
$rated = array_sum($totals);
$unrated = $working - $rated;

$chart_labels = $chart_data = $chart_colors = [];
foreach ($totals as $lvl => $n) {
    if ($n === 0) continue;
    $chart_labels[] = $lvl; $chart_data[] = $n; $chart_colors[] = $colors[$lvl];
}
$e = fn($v) => htmlspecialchars((string)($v ?? ''));
$j = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
?>
<div class="page-header">
    <h1>Job Relevance Analysis</h1>
</div>

<ul class="nav-tabs">
    <li><a class="nav-link" href="tracer_module.php">Overview</a></li>
    <li><a class="nav-link" href="employment_details.php">Employment Details</a></li>
    <li><a class="nav-link active" href="job_relevance.php">Job Relevance</a></li>
</ul>

<?php if ($rated === 0): ?>
    <div class="alert alert-info mt-4">No relevance ratings yet. Ratings come from the alumni profile ("Relevance to Degree") and the Tracer Survey.</div>
<?php else: ?>
<div class="row g-4 mt-1">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Relevance Distribution</div>
            <div class="card-body">
                <div class="chart-container"><canvas id="relevanceChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Summary</div>
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>Relevance</th><th>Count</th><th>Percentage</th></tr></thead>
                    <tbody>
                        <?php foreach ($totals as $lvl => $n): ?>
                        <tr>
                            <td><span class="badge" style="background: <?= $colors[$lvl] ?>;">&nbsp;</span> <?= $e($lvl) ?></td>
                            <td><?= $n ?></td>
                            <td><?= round($n / $rated * 100, 1) ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold"><td>Total rated</td><td><?= $rated ?></td><td>100%</td></tr>
                    </tbody>
                </table>
                <?php if ($unrated > 0): ?>
                    <p class="small text-muted mb-0"><?= $unrated ?> working alumni have not rated their job's relevance yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">Relevance by Program</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>Program</th><th class="text-center">Highly</th><th class="text-center">Somewhat</th><th class="text-center">Not</th><th style="width:30%">Highly relevant share</th></tr></thead>
                <tbody>
                    <?php foreach ($byProgram as $prog => $c):
                        $t = array_sum($c);
                        $share = $t ? round($c['Highly Relevant'] / $t * 100, 1) : 0; ?>
                    <tr>
                        <td><?= $e($prog) ?> <small class="text-muted">(<?= $t ?>)</small></td>
                        <td class="text-center"><?= $c['Highly Relevant'] ?></td>
                        <td class="text-center"><?= $c['Somewhat Relevant'] ?></td>
                        <td class="text-center"><?= $c['Not Relevant'] ?></td>
                        <td>
                            <div class="progress" style="height: 18px;">
                                <div class="progress-bar" style="width: <?= $share ?>%; background:#388087;"><?= $share ?>%</div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('relevanceChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: <?= $j($chart_labels) ?>,
            datasets: [{ data: <?= $j($chart_data) ?>, backgroundColor: <?= $j($chart_colors) ?> }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                datalabels: {
                    color: '#fff',
                    backgroundColor: 'rgba(0,0,0,0.6)',
                    borderRadius: 3,
                    padding: { top: 2, bottom: 2, left: 4, right: 4 },
                    font: { weight: 'bold', size: 11 },
                    formatter: (value, context) => {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        return total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>