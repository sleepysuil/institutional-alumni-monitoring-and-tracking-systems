<?php
require_once '../includes/admin_header.php';

// Dummy cluster data
$clusters = [
    [
        'name' => 'Recent Graduates',
        'desc' => 'High Employment Rate, Early Career',
        'color' => '#EF4444',
        'points' => [[2022,150000],[2023,160000],[2024,140000]]
    ],
    [
        'name' => 'Experienced Alumni',
        'desc' => 'Stable Employment, Higher Earning',
        'color' => '#10B981',
        'points' => [[2020,380000],[2021,420000]]
    ],
    [
        'name' => 'Career Transition',
        'desc' => 'Seeking Opportunities',
        'color' => '#8B5CF6',
        'points' => [[2024,400000]]
    ],
];

$summary = [
    ['name' => 'Recent Graduates (2022-2024)', 'year' => 2022, 'rate' => 100, 'salary' => 150000],
    ['name' => 'Experienced Alumni (2020-2021)', 'year' => 2021, 'rate' => 100, 'salary' => 400000],
    ['name' => 'Career Transition Phase', 'year' => 2024, 'rate' => 0, 'salary' => 400000],
];

// Prepare datasets for Chart.js
$datasets = [];
foreach ($clusters as $c) {
    $points = array_map(function($p) {
        return ['x' => $p[0], 'y' => $p[1]];
    }, $c['points']);
    $datasets[] = [
        'label' => $c['name'],
        'data' => $points,
        'backgroundColor' => $c['color'],
        'pointRadius' => 6
    ];
}
?>
<div class="page-header">
    <h1>Clustering Analysis</h1>
    <p class="text-muted">Alumni Segmentation (K-Means Clustering)</p>
</div>

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
                    <li><strong>Employment Rate:</strong> <?= $c['rate'] ?>%</li>
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
                datasets: <?= json_encode($datasets) ?>
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: { display: true, text: 'Graduation Year' },
                        min: 2018,
                        max: 2026
                    },
                    y: {
                        title: { display: true, text: 'Salary (₱)' },
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>