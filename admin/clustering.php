<?php
require_once '../includes/admin_header.php';

// Get alumni with graduation year and salary (numeric from salary_range if possible)
// For simplicity, we'll extract approximate numeric salary from salary_range (e.g., "₱25,000 - ₱35,000" -> average 30000)
$alumni_data = $pdo->query("
    SELECT a.graduation_year, e.salary_range
    FROM alumni a
    JOIN employment e ON a.id = e.alumni_id
    WHERE e.salary_range IS NOT NULL AND e.salary_range != ''
")->fetchAll();

// Convert salary_range to numeric average
$points = [];
foreach ($alumni_data as $row) {
    $salary = 0;
    if (preg_match('/₱?([\d,]+)/', $row['salary_range'], $matches)) {
        $salary = (float) str_replace(',', '', $matches[1]);
    }
    if ($salary > 0) {
        $points[] = ['x' => (int)$row['graduation_year'], 'y' => $salary];
    }
}

// If no data, use empty array
if (empty($points)) {
    $points = [];
}

// Simple clustering by year ranges (we'll create 3 clusters manually based on data)
$clusters = [];
$years = array_column($points, 'x');
if (!empty($years)) {
    $min_year = min($years);
    $max_year = max($years);
    $range = $max_year - $min_year;
    $step = $range / 3;

    $cluster1 = ['name' => 'Recent Graduates', 'color' => '#EF4444', 'points' => []];
    $cluster2 = ['name' => 'Mid-Career', 'color' => '#10B981', 'points' => []];
    $cluster3 = ['name' => 'Experienced', 'color' => '#8B5CF6', 'points' => []];

    foreach ($points as $p) {
        if ($p['x'] <= $min_year + $step) {
            $cluster1['points'][] = $p;
        } elseif ($p['x'] <= $min_year + 2*$step) {
            $cluster2['points'][] = $p;
        } else {
            $cluster3['points'][] = $p;
        }
    }
    $clusters = [$cluster1, $cluster2, $cluster3];
}

// Prepare summary stats per cluster
$summary = [];
foreach ($clusters as $c) {
    if (empty($c['points'])) continue;
    $years = array_column($c['points'], 'x');
    $salaries = array_column($c['points'], 'y');
    $summary[] = [
        'name' => $c['name'],
        'year' => round(array_sum($years) / count($years)),
        'rate' => 100, // We don't have employment rate per cluster easily
        'salary' => round(array_sum($salaries) / count($salaries))
    ];
}

// If no data, show message
$has_data = !empty($points);
?>
<div class="page-header">
    <h1>Clustering Analysis</h1>
    <p class="text-muted">Alumni Segmentation (K-Means Clustering based on graduation year and salary)</p>
</div>

<?php if (!$has_data): ?>
    <div class="alert alert-warning">No salary data available for clustering. Please update employment records.</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Cluster Visualization</div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="clusterChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <?php foreach ($summary as $c): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5><?= $c['name'] ?></h5>
                <ul class="list-unstyled small">
                    <li><strong>Avg Grad Year:</strong> <?= $c['year'] ?></li>
                    <li><strong>Avg Salary:</strong> ₱<?= number_format($c['salary']) ?></li>
                </ul>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('clusterChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: <?= json_encode(array_map(function($c) {
                    return [
                        'label' => $c['name'],
                        'data' => $c['points'],
                        'backgroundColor' => $c['color'],
                        'pointRadius' => 6
                    ];
                }, $clusters)) ?>
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { title: { display: true, text: 'Graduation Year' } },
                    y: { 
                        title: { display: true, text: 'Salary (₱)' },
                        ticks: { callback: value => '₱' + value.toLocaleString() }
                    }
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>