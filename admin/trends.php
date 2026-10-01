<?php
require_once '../includes/admin_header.php';
require_once '../includes/mining_helpers.php';

/*
 * Rates are calculated over RESPONDENTS (alumni with a known status from the
 * tracer survey or profile), the usual approach in tracer studies. Alumni with
 * no data no longer drag the rates down.
 */
$all_profiles = getAlumniProfiles($pdo);
$program_list = array_values(array_unique(array_column($all_profiles, 'program')));
sort($program_list);
$program = $_GET['program'] ?? '';
if (!in_array($program, $program_list, true)) $program = '';

$byYear = [];
foreach ($all_profiles as $p) {
    if (!$p['year']) continue;
    if ($program !== '' && $p['program'] !== $program) continue;
    $y = $p['year'];
    $byYear[$y] ??= ['total' => 0, 'resp' => 0, 'employed' => 0, 'self' => 0, 'unemployed' => 0];
    $byYear[$y]['total']++;
    if ($p['status'] === null) continue;
    $byYear[$y]['resp']++;
    if ($p['status'] === 'Employed')      $byYear[$y]['employed']++;
    if ($p['status'] === 'Self-Employed') $byYear[$y]['self']++;
    if ($p['status'] === 'Unemployed')    $byYear[$y]['unemployed']++;
}
ksort($byYear);

$pct = fn($n, $d) => $d ? round($n / $d * 100, 1) : 0;
$trends = [];
$prev = null;
foreach ($byYear as $year => $y) {
    $emp   = $pct($y['employed'], $y['resp']);
    $self  = $pct($y['self'], $y['resp']);
    $unemp = $pct($y['unemployed'], $y['resp']);
    $tot   = round($emp + $self, 1);

    if ($prev === null || !$y['resp'])  $dir = '→ stable';
    elseif ($tot > $prev + 0.05)         $dir = '↑ increasing';
    elseif ($tot < $prev - 0.05)         $dir = '↓ decreasing';
    else                                 $dir = '→ stable';

    $trends[] = [
        'year' => $year, 'employed_rate' => $emp, 'self_employed_rate' => $self,
        'unemployed_rate' => $unemp, 'total_employed_rate' => $tot, 'direction' => $dir,
        'total' => $y['total'], 'responses' => $y['resp'],
        'employed_count' => $y['employed'], 'self_employed_count' => $y['self'], 'unemployed_count' => $y['unemployed'],
    ];
    if ($y['resp']) $prev = $tot;
}
$latest = end($trends);
?>
<div class="page-header">
    <h1>Employment Trend Analysis</h1>
    <p class="text-muted mb-0">By graduation year, based on survey and profile responses<?= $program !== '' ? ' · ' . htmlspecialchars($program) : '' ?></p>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-4">
        <select name="program" class="form-select" onchange="this.form.submit()">
            <option value="">All programs</option>
            <?php foreach ($program_list as $pl): ?>
                <option value="<?= htmlspecialchars($pl) ?>" <?= $program === $pl ? 'selected' : '' ?>><?= htmlspecialchars($pl) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if (empty($trends)): ?>
    <div class="alert alert-info">No alumni records yet.</div>
<?php else: ?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Historical Trends</div>
            <div class="card-body">
                <div class="chart-container" style="height: 400px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Summary</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Year</th><th>Resp.</th><th>Emp</th><th>Self</th><th>Unemp</th><th>Trend</th></tr></thead>
                        <tbody>
                        <?php foreach ($trends as $t): ?>
                            <tr>
                                <td><?= $t['year'] ?></td>
                                <td><?= $t['responses'] ?>/<?= $t['total'] ?></td>
                                <td><?= $t['employed_rate'] ?>%</td>
                                <td><?= $t['self_employed_rate'] ?>%</td>
                                <td><?= $t['unemployed_rate'] ?>%</td>
                                <td><?= $t['direction'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <small class="text-muted">Resp. = alumni with a known status / all alumni in that class.</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-6 col-md-3"><div class="card bg-success text-white"><div class="card-body">
        <h6>Employed (<?= $latest['year'] ?>)</h6><h3><?= $latest['employed_rate'] ?>%</h3><small><?= $latest['employed_count'] ?> alumni</small>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card bg-info text-white"><div class="card-body">
        <h6>Self-Employed (<?= $latest['year'] ?>)</h6><h3><?= $latest['self_employed_rate'] ?>%</h3><small><?= $latest['self_employed_count'] ?> alumni</small>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card bg-warning text-white"><div class="card-body">
        <h6>Unemployed (<?= $latest['year'] ?>)</h6><h3><?= $latest['unemployed_rate'] ?>%</h3><small><?= $latest['unemployed_count'] ?> alumni</small>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card bg-primary text-white"><div class="card-body">
        <h6>Total Alumni (<?= $latest['year'] ?>)</h6><h3><?= $latest['total'] ?></h3><small><?= $latest['responses'] ?> responded</small>
    </div></div></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ds = (label, data, color, extra = {}) => Object.assign({
        label, data, borderColor: color, backgroundColor: color + '22',
        borderWidth: 2, tension: 0.1, fill: true, pointRadius: 5, pointHoverRadius: 7
    }, extra);

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($trends, 'year'), JSON_HEX_TAG | JSON_HEX_AMP) ?>,
            datasets: [
                ds('Employed',      <?= json_encode(array_column($trends, 'employed_rate')) ?>,      '#28a745'),
                ds('Self-Employed', <?= json_encode(array_column($trends, 'self_employed_rate')) ?>, '#17a2b8'),
                ds('Unemployed',    <?= json_encode(array_column($trends, 'unemployed_rate')) ?>,    '#ffc107'),
                ds('Total Employed (Emp + Self-Emp)', <?= json_encode(array_column($trends, 'total_employed_rate')) ?>, '#388087',
                   { borderWidth: 3, borderDash: [5, 5], fill: false, pointRadius: 4 })
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                datalabels: { display: false },
                title: { display: true, text: 'Employment Rate Trends by Graduation Year' },
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } },
                tooltip: { callbacks: { label: c => `${c.dataset.label}: ${c.parsed.y}%` } }
            },
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' }, title: { display: true, text: 'Percentage of respondents' } },
                x: { title: { display: true, text: 'Graduation Year' } }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>