<?php
require_once '../includes/admin_header.php';
$trends = [
    ['year' => 2020, 'rate' => 100, 'direction' => '↑ stable', 'prediction' => 100],
    ['year' => 2021, 'rate' => 100, 'direction' => '↑ stable', 'prediction' => 100],
    ['year' => 2022, 'rate' => 100, 'direction' => '↑ stable', 'prediction' => 100],
    ['year' => 2023, 'rate' => 50,  'direction' => '↓ decreasing', 'prediction' => 0],
    ['year' => 2024, 'rate' => 0,   'direction' => '↓ decreasing', 'prediction' => -50],
];
?>
<div class="page-header">
    <h1>Employment Trend Analysis</h1>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Historical Trends & Predictions</div>
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
                    <thead><tr><th>Year</th><th>Rate</th><th>Direction</th><th>Prediction</th></tr></thead>
                    <tbody>
                        <?php foreach ($trends as $t): ?>
                        <tr>
                            <td><?= $t['year'] ?></td>
                            <td><?= $t['rate'] ?>%</td>
                            <td><?= $t['direction'] ?></td>
                            <td><?= $t['prediction'] ?>%</td>
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
                labels: [2020,2021,2022,2023,2024],
                datasets: [
                    {
                        label: 'Employment Rate',
                        data: [100,100,100,50,0],
                        borderColor: '#EF4444',
                        backgroundColor: 'rgba(239,68,68,0.1)',
                        tension: 0.1
                    },
                    {
                        label: 'Prediction',
                        data: [null,null,null,null,-50],
                        borderColor: '#8B5CF6',
                        borderDash: [5,5],
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, max: 150 } }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>