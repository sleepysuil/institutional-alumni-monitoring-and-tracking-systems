<?php
require_once '../includes/admin_header.php';

$employment_status = $pdo->query("SELECT status, COUNT(*) as count FROM employment GROUP BY status")->fetchAll();
$industry_dist = $pdo->query("SELECT industry, COUNT(*) as count FROM employment WHERE industry IS NOT NULL GROUP BY industry")->fetchAll();
$yearly = $pdo->query("SELECT a.graduation_year, 
    COUNT(CASE WHEN e.status IN ('Employed','Self-Employed') THEN 1 END) as employed,
    COUNT(*) as total
    FROM alumni a LEFT JOIN employment e ON a.id = e.alumni_id
    GROUP BY a.graduation_year ORDER BY a.graduation_year")->fetchAll();
$programs = $pdo->query("SELECT program, COUNT(*) as count FROM alumni GROUP BY program")->fetchAll();

$emp_labels = array_column($employment_status, 'status');
$emp_data = array_column($employment_status, 'count');
$ind_labels = array_column($industry_dist, 'industry');
$ind_data = array_column($industry_dist, 'count');
$year_labels = array_column($yearly, 'graduation_year');
$year_data = array_map(function($y) { 
    return $y['total'] ? round(($y['employed']/$y['total'])*100,1) : 0; 
}, $yearly);
$prog_labels = array_column($programs, 'program');
$prog_data = array_column($programs, 'count');

$total_emp = array_sum($emp_data) ?: 1;
$total_ind = array_sum($ind_data) ?: 1;
$total_prog = array_sum($prog_data) ?: 1;
?>
<div class="page-header">
    <h1>Analytics Dashboard</h1>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Employment Status</div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="empChart"></canvas>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm table-bordered text-center">
                        <thead class="table-light"><tr><th>Category</th><th>Count</th><th>Percentage (%)</th></tr></thead>
                        <tbody>
                            <?php foreach ($employment_status as $row): ?>
                            <tr>
                                <td><?= $row['status'] ?></td>
                                <td><?= $row['count'] ?></td>
                                <td><?= round(($row['count']/$total_emp)*100,1) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Industry Distribution</div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="indChart"></canvas>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm table-bordered text-center">
                        <thead class="table-light"><tr><th>Industry</th><th>Count</th><th>Percentage (%)</th></tr></thead>
                        <tbody>
                            <?php foreach ($industry_dist as $row): ?>
                            <tr>
                                <td><?= $row['industry'] ?></td>
                                <td><?= $row['count'] ?></td>
                                <td><?= round(($row['count']/$total_ind)*100,1) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4 g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Employment Rate by Year</div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="yearChart"></canvas>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm table-bordered text-center">
                        <thead class="table-light"><tr><th>Year</th><th>Employment Rate (%)</th></tr></thead>
                        <tbody>
                            <?php foreach ($yearly as $y): ?>
                            <tr>
                                <td><?= $y['graduation_year'] ?></td>
                                <td><?= $y['total'] ? round(($y['employed']/$y['total'])*100,1) : 0 ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Alumni by Program</div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="progChart"></canvas>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm table-bordered text-center">
                        <thead class="table-light"><tr><th>Program</th><th>Count</th><th>Percentage (%)</th></tr></thead>
                        <tbody>
                            <?php foreach ($programs as $row): ?>
                            <tr>
                                <td><?= $row['program'] ?></td>
                                <td><?= $row['count'] ?></td>
                                <td><?= round(($row['count']/$total_prog)*100,1) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('empChart')) {
        new Chart(document.getElementById('empChart'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($emp_labels) ?>,
                datasets: [{
                    data: <?= json_encode($emp_data) ?>,
                    backgroundColor: ['#388087', '#6FB3B3', '#BADFE7', '#C2EDCE']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: {
                        color: '#fff',
                        backgroundColor: 'rgba(0,0,0,0.6)',
                        borderRadius: 3,
                        padding: { top: 2, bottom: 2, left: 4, right: 4 },
                        font: { weight: 'bold', size: 11 },
                        formatter: (value, context) => {
                            let total = context.dataset.data.reduce((a,b) => a + b, 0);
                            return total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
                        }
                    }
                }
            }
        });
    }
    if (document.getElementById('indChart')) {
        new Chart(document.getElementById('indChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($ind_labels) ?>,
                datasets: [{
                    label: 'Alumni',
                    data: <?= json_encode($ind_data) ?>,
                    backgroundColor: '#6FB3B3'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { datalabels: { display: false } }
            }
        });
    }
    if (document.getElementById('yearChart')) {
        new Chart(document.getElementById('yearChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($year_labels) ?>,
                datasets: [{
                    label: 'Employment Rate (%)',
                    data: <?= json_encode($year_data) ?>,
                    borderColor: '#388087',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, max: 100 } },
                plugins: { datalabels: { display: false } }
            }
        });
    }
    if (document.getElementById('progChart')) {
        new Chart(document.getElementById('progChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($prog_labels) ?>,
                datasets: [{
                    data: <?= json_encode($prog_data) ?>,
                    backgroundColor: ['#388087', '#6FB3B3', '#BADFE7', '#C2EDCE']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: {
                        color: '#fff',
                        backgroundColor: 'rgba(0,0,0,0.6)',
                        borderRadius: 3,
                        padding: { top: 2, bottom: 2, left: 4, right: 4 },
                        font: { weight: 'bold', size: 11 },
                        formatter: (value, context) => {
                            let total = context.dataset.data.reduce((a,b) => a + b, 0);
                            return total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>