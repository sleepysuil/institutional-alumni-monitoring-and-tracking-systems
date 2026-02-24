<?php
require_once '../includes/admin_header.php';

// Get employment rate per year
$yearly = $pdo->query("
    SELECT a.graduation_year,
           COUNT(CASE WHEN e.status IN ('Employed','Self-Employed') THEN 1 END) as employed,
           COUNT(*) as total
    FROM alumni a
    LEFT JOIN employment e ON a.id = e.alumni_id
    GROUP BY a.graduation_year
    ORDER BY a.graduation_year
")->fetchAll();

$trends = [];
$prev_rate = null;
foreach ($yearly as $y) {
    $rate = $y['total'] ? round(($y['employed'] / $y['total']) * 100, 1) : 0;
    if ($prev_rate !== null) {
        $direction = $rate > $prev_rate ? '↑ increasing' : ($rate < $prev_rate ? '↓ decreasing' : '→ stable');
    } else {
        $direction = '→ stable';
    }
    $trends[] = [
        'year' => $y['graduation_year'],
        'rate' => $rate,
        'direction' => $direction,
        'prediction' => $rate // simplistic: same as current
    ];
    $prev_rate = $rate;
}
?>
<div class="page-header">
    <h1>Employment Trend Analysis</h1>
    <p class="text-muted">Historical trends based on actual data</p>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Historical Trends</div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Summary</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Year</th><th>Rate</th><th>Direction</th></tr></thead>
                    <tbody>
                        <?php foreach ($trends as $t): ?>
                        <tr>
                            <td><?= $t['year'] ?></td>
                            <td><?= $t['rate'] ?>%</td>
                            <td><?= $t['direction'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('trendChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($trends, 'year')) ?>,
                datasets: [{
                    label: 'Employment Rate',
                    data: <?= json_encode(array_column($trends, 'rate')) ?>,
                    borderColor: '#EF4444',
                    backgroundColor: 'rgba(239,68,68,0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>