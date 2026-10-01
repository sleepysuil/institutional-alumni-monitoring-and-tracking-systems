<?php
require_once '../includes/admin_header.php';

$total_alumni = (int)$pdo->query("SELECT COUNT(*) FROM alumni")->fetchColumn();
$employed = (int)$pdo->query("SELECT COUNT(*) FROM employment WHERE status IN ('Employed','Self-Employed')")->fetchColumn();
$employment_rate = $total_alumni ? round(($employed / $total_alumni) * 100, 1) : 0;
// Only jobs that are active AND not past their deadline
$active_jobs = (int)$pdo->query("SELECT COUNT(*) FROM job_postings WHERE status='active' AND (deadline IS NULL OR deadline >= CURDATE())")->fetchColumn();

$industry = $pdo->query("SELECT industry, COUNT(*) as cnt FROM employment WHERE industry IS NOT NULL AND industry <> '' GROUP BY industry ORDER BY cnt DESC LIMIT 1")->fetch();
$top_industry = $industry ? $industry['industry'] : 'N/A';

$recent = $pdo->query("SELECT * FROM alumni ORDER BY id DESC LIMIT 5")->fetchAll();

$dist = $pdo->query("SELECT status, COUNT(*) as count FROM employment GROUP BY status")->fetchAll();
$status_counts = [];
foreach ($dist as $row) $status_counts[$row['status']] = (int)$row['count'];
$categories = ['Employed', 'Self-Employed', 'Unemployed', 'Pursuing Higher Education'];
$chart_data = [];
foreach ($categories as $cat) $chart_data[$cat] = $status_counts[$cat] ?? 0;

// ---- Things that need the admin's attention ----
$pending_approvals = (int)$pdo->query("SELECT COUNT(*) FROM alumni WHERE is_approved = 0 AND approval_status = 'pending'")->fetchColumn();
$pending_apps = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
$no_survey = (int)$pdo->query("SELECT COUNT(*) FROM alumni a WHERE NOT EXISTS (SELECT 1 FROM tracer_responses t WHERE t.alumni_id = a.id)")->fetchColumn();
$expiring = (int)$pdo->query("SELECT COUNT(*) FROM job_postings WHERE status='active' AND deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

// ---- Recent activity ----
$recent_apps = $pdo->query("SELECT a.applied_date, a.status, j.title, al.first_name, al.last_name
                            FROM applications a
                            JOIN job_postings j ON a.job_id = j.id
                            JOIN alumni al ON a.alumni_id = al.id
                            ORDER BY a.applied_date DESC LIMIT 5")->fetchAll();

$e = fn($v) => htmlspecialchars((string)($v ?? ''));
?>
<div class="page-header">
    <h1>Dashboard</h1>
</div>

<?php if ($pending_approvals || $pending_apps || $expiring): ?>
<div class="row g-3 mb-4">
    <?php if ($pending_approvals): ?>
    <div class="col-md-4"><a href="settings.php" class="text-decoration-none">
        <div class="alert alert-warning mb-0"><i class="fas fa-user-clock me-2"></i><strong><?= $pending_approvals ?></strong> alumni registration<?= $pending_approvals > 1 ? 's' : '' ?> awaiting approval</div>
    </a></div>
    <?php endif; ?>
    <?php if ($pending_apps): ?>
    <div class="col-md-4"><a href="applications.php?status=pending" class="text-decoration-none">
        <div class="alert alert-info mb-0"><i class="fas fa-inbox me-2"></i><strong><?= $pending_apps ?></strong> job application<?= $pending_apps > 1 ? 's' : '' ?> to review</div>
    </a></div>
    <?php endif; ?>
    <?php if ($expiring): ?>
    <div class="col-md-4"><a href="job_postings.php" class="text-decoration-none">
        <div class="alert alert-secondary mb-0"><i class="fas fa-hourglass-half me-2"></i><strong><?= $expiring ?></strong> job posting<?= $expiring > 1 ? 's' : '' ?> closing within 7 days</div>
    </a></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-6 col-md-3">
        <div class="stat-card primary">
            <div class="stat-title">Total Alumni</div>
            <div class="stat-value"><?= $total_alumni ?></div>
            <div class="stat-label"><?= $no_survey ?> have not taken the survey</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card teal">
            <div class="stat-title">Employment Rate</div>
            <div class="stat-value"><?= $employment_rate ?>%</div>
            <div class="stat-label"><?= $employed ?> of <?= $total_alumni ?> employed</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card purple">
            <div class="stat-title">Open Jobs</div>
            <div class="stat-value"><?= $active_jobs ?></div>
            <div class="stat-label">Active and not expired</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card emerald">
            <div class="stat-title">Top Industry</div>
            <div class="stat-value" style="font-size: 1.4rem;"><?= $e($top_industry) ?></div>
            <div class="stat-label">Most common</div>
        </div>
    </div>
</div>

<div class="row mt-4 g-4">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between"><span>Recent Alumni Registrations</span><a href="alumni_directory.php" class="small">View all</a></div>
            <div class="card-body p-0">
                <?php if (!$recent): ?><p class="text-muted p-3 mb-0">No alumni yet.</p><?php endif; ?>
                <?php foreach ($recent as $al): ?>
                <div class="alumni-item">
                    <?php if (!empty($al['profile_pic']) && file_exists('../' . $al['profile_pic'])): ?>
                        <img src="<?= SITE_URL ?>/<?= $e($al['profile_pic']) ?>" alt="Profile" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover; margin-right: 1rem;">
                    <?php else: ?>
                        <i class="fas fa-user-circle fa-2x" style="color: var(--primary); margin-right: 1rem;"></i>
                    <?php endif; ?>
                    <div class="alumni-info">
                        <a href="view_alumni.php?id=<?= (int)$al['id'] ?>" class="alumni-name"><?= $e($al['first_name'] . ' ' . $al['last_name']) ?></a>
                        <br><small><?= $e($al['program']) ?> • Class of <?= (int)$al['graduation_year'] ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between"><span>Latest Job Applications</span><a href="applications.php" class="small">View all</a></div>
            <ul class="list-group list-group-flush">
                <?php if (!$recent_apps): ?><li class="list-group-item text-muted">No applications yet.</li><?php endif; ?>
                <?php foreach ($recent_apps as $ra):
                    $b = ['pending' => 'warning', 'reviewed' => 'info', 'accepted' => 'success', 'rejected' => 'danger'][$ra['status']] ?? 'secondary'; ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= $e($ra['first_name'] . ' ' . $ra['last_name']) ?> <small class="text-muted">applied for <?= $e($ra['title']) ?> · <?= date('M j', strtotime($ra['applied_date'])) ?></small></span>
                    <span class="badge bg-<?= $b ?>"><?= ucfirst($ra['status']) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Employment Status Distribution</div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="employmentChart"></canvas>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <span class="badge" style="background:#388087;">Employed (<?= $chart_data['Employed'] ?>)</span>
                    <span class="badge" style="background:#6FB3B3;">Self-Employed (<?= $chart_data['Self-Employed'] ?>)</span>
                    <span class="badge" style="background:#BADFE7; color:#1f4f4f;">Unemployed (<?= $chart_data['Unemployed'] ?>)</span>
                    <span class="badge" style="background:#C2EDCE; color:#1f4f4f;">Higher Ed (<?= $chart_data['Pursuing Higher Education'] ?>)</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('employmentChart');
    if (!ctx) return;
    try {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Employed', 'Self-Employed', 'Unemployed', 'Higher Education'],
                datasets: [{
                    data: [<?= $chart_data['Employed'] ?>, <?= $chart_data['Self-Employed'] ?>, <?= $chart_data['Unemployed'] ?>, <?= $chart_data['Pursuing Higher Education'] ?>],
                    backgroundColor: ['#388087', '#6FB3B3', '#BADFE7', '#C2EDCE'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } },
                    datalabels: {
                        display: c => c.dataset.data[c.dataIndex] > 0,   // no "0.0%" bubbles on empty slices
                        color: '#fff',
                        backgroundColor: 'rgba(0,0,0,0.6)',
                        borderRadius: 3,
                        padding: { top: 2, bottom: 2, left: 4, right: 4 },
                        font: { weight: 'bold', size: 11 },
                        formatter: (value, context) => {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            return total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '';
                        }
                    }
                }
            }
        });
    } catch (e) {
        ctx.insertAdjacentHTML('afterend', '<div class="alert alert-warning">Chart could not be loaded. Please check that Chart.js is included.</div>');
    }
});
</script>

<?php include '../includes/footer.php'; ?>