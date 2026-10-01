<?php
require_once '../includes/admin_header.php';

$employment_status = $pdo->query("SELECT status, COUNT(*) as count FROM employment GROUP BY status ORDER BY count DESC")->fetchAll();
$industry_dist = $pdo->query("SELECT industry, COUNT(*) as count FROM employment WHERE industry IS NOT NULL AND industry <> '' GROUP BY industry ORDER BY count DESC")->fetchAll();
$yearly = $pdo->query("SELECT a.graduation_year,
    COUNT(CASE WHEN e.status IN ('Employed','Self-Employed') THEN 1 END) as employed,
    COUNT(*) as total
    FROM alumni a LEFT JOIN employment e ON a.id = e.alumni_id
    GROUP BY a.graduation_year ORDER BY a.graduation_year")->fetchAll();
$programs = $pdo->query("SELECT program, COUNT(*) as count FROM alumni GROUP BY program ORDER BY count DESC")->fetchAll();

// KPI cards
$total_alumni = (int)$pdo->query("SELECT COUNT(*) FROM alumni")->fetchColumn();
$responded = (int)$pdo->query("SELECT COUNT(DISTINCT alumni_id) FROM tracer_responses")->fetchColumn();
$emp_records = array_sum(array_column($employment_status, 'count'));
$working = 0;
foreach ($employment_status as $r) {
    if (in_array($r['status'], ['Employed', 'Self-Employed'], true)) $working += (int)$r['count'];
}
$employment_rate = $emp_records ? round($working / $emp_records * 100, 1) : 0;
$response_rate = $total_alumni ? round($responded / $total_alumni * 100, 1) : 0;

$emp_labels = array_column($employment_status, 'status');
$emp_data = array_map('intval', array_column($employment_status, 'count'));
$ind_labels = array_column($industry_dist, 'industry');
$ind_data = array_map('intval', array_column($industry_dist, 'count'));
$year_labels = array_column($yearly, 'graduation_year');
$year_data = array_map(fn($y) => $y['total'] ? round(($y['employed'] / $y['total']) * 100, 1) : 0, $yearly);
$prog_labels = array_column($programs, 'program');
$prog_data = array_map('intval', array_column($programs, 'count'));

$total_emp = array_sum($emp_data) ?: 1;
$total_ind = array_sum($ind_data) ?: 1;
$total_prog = array_sum($prog_data) ?: 1;

$e = fn($v) => htmlspecialchars((string)($v ?? ''));
$j = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
?>
<div class="page-header">
    <h1>Analytics Dashboard</h1>
    <a href="export.php?format=excel" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-excel me-1"></i>Export Alumni</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card text-center"><div class="card-body py-3">
        <div class="fs-3 fw-bold text-primary"><?= $total_alumni ?></div><div class="small text-muted">Total Alumni</div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center"><div class="card-body py-3">
        <div class="fs-3 fw-bold text-success"><?= $employment_rate ?>%</div><div class="small text-muted">Employment Rate</div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center"><div class="card-body py-3">
        <div class="fs-3 fw-bold text-info"><?= $response_rate ?>%</div><div class="small text-muted">Tracer Response Rate</div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center"><div class="card-body py-3">
        <div class="fs-3 fw-bold text-secondary"><?= count($industry_dist) ?></div><div class="small text-muted">Industries Represented</div>
    </div></div></div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Employment Status</div>
            <div class="card-body">
                <?php if ($employment_status): ?>
                <div class="chart-container"><canvas id="empChart"></canvas></div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm table-bordered text-center">
                        <thead class="table-light"><tr><th>Category</th><th>Count</th><th>Percentage (%)</th></tr></thead>
                        <tbody>
                            <?php foreach ($employment_status as $row): ?>
                            <tr>
                                <td><?= $e($row['status']) ?></td>
                                <td><?= (int)$row['count'] ?></td>
                                <td><?= round(($row['count'] / $total_emp) * 100, 1) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?><p class="text-muted mb-0">No employment data yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Industry Distribution</div>
            <div class="card-body">
                <?php if ($industry_dist): ?>
                <div class="chart-container"><canvas id="indChart"></canvas></div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm table-bordered text-center">
                        <thead class="table-light"><tr><th>Industry</th><th>Count</th><th>Percentage (%)</th></tr></thead>
                        <tbody>
                            <?php foreach ($industry_dist as $row): ?>
                            <tr>
                                <td><?= $e($row['industry']) ?></td>
                                <td><?= (int)$row['count'] ?></td>
                                <td><?= round(($row['count'] / $total_ind) * 100, 1) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?><p class="text-muted mb-0">No industry data yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4 g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Employment Rate by Year</div>
            <div class="card-body">
                <?php if ($yearly): ?>
                <div class="chart-container"><canvas id="yearChart"></canvas></div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm table-bordered text-center">
                        <thead class="table-light"><tr><th>Year</th><th>Alumni</th><th>Employment Rate (%)</th></tr></thead>
                        <tbody>
                            <?php foreach ($yearly as $k => $y): ?>
                            <tr>
                                <td><?= (int)$y['graduation_year'] ?></td>
                                <td><?= (int)$y['total'] ?></td>
                                <td><?= $year_data[$k] ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?><p class="text-muted mb-0">No alumni yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Alumni by Program</div>
            <div class="card-body">
                <?php if ($programs): ?>
                <div class="chart-container"><canvas id="progChart"></canvas></div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm table-bordered text-center">
                        <thead class="table-light"><tr><th>Program</th><th>Count</th><th>Percentage (%)</th></tr></thead>
                        <tbody>
                            <?php foreach ($programs as $row): ?>
                            <tr>
                                <td><?= $e($row['program']) ?></td>
                                <td><?= (int)$row['count'] ?></td>
                                <td><?= round(($row['count'] / $total_prog) * 100, 1) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?><p class="text-muted mb-0">No alumni yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Enough colours for any number of slices (the old 4-colour list ran out)
    const palette = ['#388087', '#6FB3B3', '#F59E0B', '#8B5CF6', '#EF4444', '#10B981', '#3B82F6', '#EC4899', '#84CC16', '#F97316', '#64748B', '#14B8A6'];
    const colors = n => Array.from({ length: n }, (_, i) => palette[i % palette.length]);
    const pctLabel = {
        color: '#fff', backgroundColor: 'rgba(0,0,0,0.6)', borderRadius: 3,
        padding: { top: 2, bottom: 2, left: 4, right: 4 },
        font: { weight: 'bold', size: 11 },
        formatter: (value, context) => {
            const total = context.dataset.data.reduce((a, b) => a + b, 0);
            return total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
        }
    };

    const empLabels = <?= $j($emp_labels) ?>, empData = <?= $j($emp_data) ?>;
    if (document.getElementById('empChart')) {
        new Chart(document.getElementById('empChart'), {
            type: 'pie',
            data: { labels: empLabels, datasets: [{ data: empData, backgroundColor: colors(empData.length) }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { datalabels: pctLabel } }
        });
    }

    const indLabels = <?= $j($ind_labels) ?>, indData = <?= $j($ind_data) ?>;
    if (document.getElementById('indChart')) {
        new Chart(document.getElementById('indChart'), {
            type: 'bar',
            data: { labels: indLabels, datasets: [{ label: 'Alumni', data: indData, backgroundColor: '#6FB3B3' }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                plugins: { datalabels: { display: false }, legend: { display: false } }
            }
        });
    }

    if (document.getElementById('yearChart')) {
        new Chart(document.getElementById('yearChart'), {
            type: 'line',
            data: {
                labels: <?= $j($year_labels) ?>,
                datasets: [{ label: 'Employment Rate (%)', data: <?= $j($year_data) ?>, borderColor: '#388087', backgroundColor: 'rgba(56,128,135,0.15)', fill: true, tension: 0.1 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, max: 100 } },
                plugins: { datalabels: { display: false } }
            }
        });
    }

    const progData = <?= $j($prog_data) ?>;
    if (document.getElementById('progChart')) {
        new Chart(document.getElementById('progChart'), {
            type: 'doughnut',
            data: { labels: <?= $j($prog_labels) ?>, datasets: [{ data: progData, backgroundColor: colors(progData.length) }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { datalabels: pctLabel } }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>