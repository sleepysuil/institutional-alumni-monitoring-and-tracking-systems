<?php
require_once '../includes/admin_header.php';

// Get data
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
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Employment Status (Pie)
    const empCtx = document.getElementById('empChart');
    if (empCtx) {
        new Chart(empCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode($emp_labels) ?>,
                datasets: [{
                    data: <?= json_encode($emp_data) ?>,
                    backgroundColor: ['#2563EB', '#8B5CF6', '#EF4444', '#F59E0B', '#10B981']
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
                            let percentage = total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
                            return percentage;
                        }
                    }
                }
            }
        });
    }

    // Industry Distribution (Bar) – no percentages needed
    const indCtx = document.getElementById('indChart');
    if (indCtx) {
        new Chart(indCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($ind_labels) ?>,
                datasets: [{
                    label: 'Alumni',
                    data: <?= json_encode($ind_data) ?>,
                    backgroundColor: '#8B5CF6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { datalabels: { display: false } }
            }
        });
    }

    // Employment Rate by Year (Line)
    const yearCtx = document.getElementById('yearChart');
    if (yearCtx) {
        new Chart(yearCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($year_labels) ?>,
                datasets: [{
                    label: 'Employment Rate (%)',
                    data: <?= json_encode($year_data) ?>,
                    borderColor: '#2563EB',
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

    // Alumni by Program (Doughnut)
    const progCtx = document.getElementById('progChart');
    if (progCtx) {
        new Chart(progCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($prog_labels) ?>,
                datasets: [{
                    data: <?= json_encode($prog_data) ?>,
                    backgroundColor: ['#2563EB', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444']
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
                            let percentage = total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
                            return percentage;
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>