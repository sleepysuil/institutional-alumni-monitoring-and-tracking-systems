<?php
require_once '../includes/admin_header.php';

$yearly = $pdo->query("
    SELECT a.graduation_year,
           COUNT(CASE WHEN e.status = 'Employed' THEN 1 END) as employed,
           COUNT(CASE WHEN e.status = 'Self-Employed' THEN 1 END) as self_employed,
           COUNT(CASE WHEN e.status = 'Unemployed' THEN 1 END) as unemployed,
           COUNT(*) as total
    FROM alumni a
    LEFT JOIN employment e ON a.id = e.alumni_id
    GROUP BY a.graduation_year
    ORDER BY a.graduation_year
")->fetchAll();

$trends = [];
$prev_rate = null;
foreach ($yearly as $y) {
    $employed_rate = $y['total'] ? round(($y['employed'] / $y['total']) * 100, 1) : 0;
    $self_employed_rate = $y['total'] ? round(($y['self_employed'] / $y['total']) * 100, 1) : 0;
    $unemployed_rate = $y['total'] ? round(($y['unemployed'] / $y['total']) * 100, 1) : 0;
    $total_employed_rate = $employed_rate + $self_employed_rate;
    
    if ($prev_rate !== null) {
        $direction = $total_employed_rate > $prev_rate ? '↑ increasing' : ($total_employed_rate < $prev_rate ? '↓ decreasing' : '→ stable');
    } else {
        $direction = '→ stable';
    }
    
    $trends[] = [
        'year' => $y['graduation_year'],
        'employed_rate' => $employed_rate,
        'self_employed_rate' => $self_employed_rate,
        'unemployed_rate' => $unemployed_rate,
        'total_employed_rate' => $total_employed_rate,
        'direction' => $direction,
        'total' => $y['total'],
        'employed_count' => $y['employed'],
        'self_employed_count' => $y['self_employed'],
        'unemployed_count' => $y['unemployed']
    ];
    $prev_rate = $total_employed_rate;
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
                <div class="chart-container" style="position: relative; height:400px;">
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
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Employed</th>
                            <th>Self-Emp</th>
                            <th>Unemp</th>
                            <th>Direction</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trends as $t): ?>
                        <tr>
                            <td><?= $t['year'] ?></td>
                            <td><?= $t['employed_rate'] ?>%</td>
                            <td><?= $t['self_employed_rate'] ?>%</td>
                            <td><?= $t['unemployed_rate'] ?>%</td>
                            <td><?= $t['direction'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Additional Statistics Cards -->
<div class="row g-4 mt-2">
    <?php 
    $latest = end($trends);
    if ($latest):
    ?>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6>Employed (<?= $latest['year'] ?>)</h6>
                <h3><?= $latest['employed_rate'] ?>%</h3>
                <small><?= $latest['employed_count'] ?> alumni</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6>Self-Employed (<?= $latest['year'] ?>)</h6>
                <h3><?= $latest['self_employed_rate'] ?>%</h3>
                <small><?= $latest['self_employed_count'] ?> alumni</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h6>Unemployed (<?= $latest['year'] ?>)</h6>
                <h3><?= $latest['unemployed_rate'] ?>%</h3>
                <small><?= $latest['unemployed_count'] ?> alumni</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6>Total Alumni (<?= $latest['year'] ?>)</h6>
                <h3><?= $latest['total'] ?></h3>
                <small>Graduates</small>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('trendChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($trends, 'year')) ?>,
                datasets: [
                    {
                        label: 'Employed',
                        data: <?= json_encode(array_column($trends, 'employed_rate')) ?>,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    },
                    {
                        label: 'Self-Employed',
                        data: <?= json_encode(array_column($trends, 'self_employed_rate')) ?>,
                        borderColor: '#17a2b8',
                        backgroundColor: 'rgba(23, 162, 184, 0.1)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    },
                    {
                        label: 'Unemployed',
                        data: <?= json_encode(array_column($trends, 'unemployed_rate')) ?>,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    },
                    {
                        label: 'Total Employed (Emp + Self-Emp)',
                        data: <?= json_encode(array_column($trends, 'total_employed_rate')) ?>,
                        borderColor: '#388087',
                        backgroundColor: 'rgba(56, 128, 135, 0.1)',
                        borderWidth: 3,
                        borderDash: [5, 5],
                        tension: 0.1,
                        fill: false,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Employment Rate Trends by Graduation Year',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y + '%';
                                }
                                return label;
                            }
                        }
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        title: {
                            display: true,
                            text: 'Percentage'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Graduation Year'
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>