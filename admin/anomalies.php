<?php
require_once '../includes/admin_header.php';
require_once '../includes/mining_helpers.php';

$profiles = getAlumniProfiles($pdo);

$counts  = ['high' => 0, 'medium' => 0, 'low' => 0];
$flagged = [];

// ---- 1. Salary outliers (z-score on salary midpoint) ----
$salaried = array_values(array_filter($profiles, fn($p) => $p['salary'] !== null));
$n = count($salaried);
$avg = $std = 0;

if ($n >= 3) {
    $vals = array_column($salaried, 'salary');
    $avg  = array_sum($vals) / $n;
    $std  = sqrt(array_sum(array_map(fn($v) => ($v - $avg) ** 2, $vals)) / $n);

    if ($std > 0) { // fixes "division by zero" when every salary is equal
        foreach ($salaried as $p) {
            $z  = ($p['salary'] - $avg) / $std;
            $az = abs($z);
            $level = $az > 3 ? 'high' : ($az > 2 ? 'medium' : ($az > 1 ? 'low' : null));
            if ($level) {
                $counts[$level]++;
                $flagged[] = [
                    'id' => $p['id'], 'name' => $p['name'], 'program' => $p['program'],
                    'type'  => $z > 0 ? 'Salary above average' : 'Salary below average',
                    'detail'=> '₱' . number_format($p['salary']) . ' (z = ' . round($z, 2) . ')',
                    'level' => $level, 'sort' => $az,
                ];
            }
        }
    }
}

// ---- 2. Long-term unemployment (from tracer survey) ----
foreach ($profiles as $p) {
    if ($p['status'] === 'Unemployed' && $p['seeking_time'] === 'Over 1 year') {
        $counts['medium']++;
        $flagged[] = [
            'id' => $p['id'], 'name' => $p['name'], 'program' => $p['program'],
            'type' => 'Long-term unemployment', 'detail' => 'Seeking work for over 1 year',
            'level' => 'medium', 'sort' => 2.5,
        ];
    }
}

usort($flagged, fn($a, $b) => $b['sort'] <=> $a['sort']);
$badge = ['high' => 'danger', 'medium' => 'warning', 'low' => 'success'];

// Optional priority filter (?level=high|medium|low)
$level_filter = $_GET['level'] ?? 'all';
if (!isset($badge[$level_filter])) $level_filter = 'all';
$shown = $level_filter === 'all'
    ? $flagged
    : array_values(array_filter($flagged, fn($f) => $f['level'] === $level_filter));
?>
<div class="page-header">
    <h1>Anomaly Detection</h1>
    <p class="text-muted mb-0">Salary outliers (z-score) and long-term unemployment</p>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card text-white bg-danger mb-3"><div class="card-body">
            <h5 class="card-title">High Priority</h5>
            <p class="display-4"><?= $counts['high'] ?></p>
            <p class="mb-0">Salary z-score above 3</p>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning mb-3"><div class="card-body">
            <h5 class="card-title">Medium Priority</h5>
            <p class="display-4"><?= $counts['medium'] ?></p>
            <p class="mb-0">z-score above 2, or unemployed over 1 year</p>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3"><div class="card-body">
            <h5 class="card-title">Low Priority</h5>
            <p class="display-4"><?= $counts['low'] ?></p>
            <p class="mb-0">Mild outliers (z-score above 1)</p>
        </div></div>
    </div>
</div>

<?php if ($n < 3): ?>
    <div class="alert alert-info">Salary outlier detection needs at least 3 alumni with salary data (found <?= $n ?>).</div>
<?php elseif ($std == 0): ?>
    <div class="alert alert-info">All salaries are identical, so there are no salary outliers.</div>
<?php else: ?>
    <p class="text-muted small">Based on <?= $n ?> salary records. Average ₱<?= number_format($avg) ?>, standard deviation ₱<?= number_format($std) ?>. With few records, very high z-scores are mathematically impossible, so more data gives better results.</p>
<?php endif; ?>

<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="?level=all" class="btn btn-sm <?= $level_filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">All (<?= count($flagged) ?>)</a>
    <?php foreach (['high', 'medium', 'low'] as $lv): ?>
        <a href="?level=<?= $lv ?>" class="btn btn-sm <?= $level_filter === $lv ? 'btn-' . $badge[$lv] : 'btn-outline-' . $badge[$lv] ?>"><?= ucfirst($lv) ?> (<?= $counts[$lv] ?>)</a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header">Flagged Alumni (<?= count($shown) ?>)</div>
    <div class="card-body">
        <?php if (empty($shown)): ?>
            <p class="text-muted mb-0">No anomalies detected.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Alumni</th><th>Program</th><th>Anomaly</th><th>Detail</th><th>Priority</th></tr></thead>
                <tbody>
                <?php foreach ($shown as $f): ?>
                    <tr>
                        <td><a href="view_alumni.php?id=<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></a></td>
                        <td><?= htmlspecialchars($f['program']) ?></td>
                        <td><?= htmlspecialchars($f['type']) ?></td>
                        <td><?= htmlspecialchars($f['detail']) ?></td>
                        <td><span class="badge bg-<?= $badge[$f['level']] ?>"><?= ucfirst($f['level']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>