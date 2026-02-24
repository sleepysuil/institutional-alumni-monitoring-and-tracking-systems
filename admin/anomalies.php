<?php
require_once '../includes/admin_header.php';

// Detect anomalies: alumni with unusually high salary or long unemployment
// We'll use simple standard deviation for salary

$salaries = $pdo->query("
    SELECT a.id, e.salary_range 
    FROM alumni a
    JOIN employment e ON a.id = e.alumni_id
    WHERE e.salary_range IS NOT NULL AND e.salary_range != ''
")->fetchAll();

$numeric_salaries = [];
foreach ($salaries as $s) {
    if (preg_match('/₱?([\d,]+)/', $s['salary_range'], $matches)) {
        $numeric_salaries[$s['id']] = (float) str_replace(',', '', $matches[1]);
    }
}

$anomalies = ['high' => 0, 'medium' => 0, 'low' => 0];
if (!empty($numeric_salaries)) {
    $avg = array_sum($numeric_salaries) / count($numeric_salaries);
    $variance = array_reduce($numeric_salaries, fn($carry, $val) => $carry + ($val - $avg)**2, 0) / count($numeric_salaries);
    $std = sqrt($variance);
    
    foreach ($numeric_salaries as $id => $sal) {
        $z = abs($sal - $avg) / $std;
        if ($z > 3) $anomalies['high']++;
        elseif ($z > 2) $anomalies['medium']++;
        elseif ($z > 1) $anomalies['low']++;
    }
}
?>
<div class="page-header">
    <h1>Anomaly Detection</h1>
    <p class="text-muted">Outliers based on salary (z-score)</p>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card text-white bg-danger mb-3">
            <div class="card-body">
                <h5 class="card-title">High Priority</h5>
                <p class="display-4"><?= $anomalies['high'] ?></p>
                <p>Require immediate attention (z > 3)</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <h5 class="card-title">Medium Priority</h5>
                <p class="display-4"><?= $anomalies['medium'] ?></p>
                <p>Monitor closely (z > 2)</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Low Priority</h5>
                <p class="display-4"><?= $anomalies['low'] ?></p>
                <p>Positive outliers (z > 1)</p>
            </div>
        </div>
    </div>
</div>

<?php if (empty($numeric_salaries)): ?>
    <div class="alert alert-info">No salary data available for anomaly detection.</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>