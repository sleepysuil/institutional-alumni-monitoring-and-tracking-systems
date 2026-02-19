<?php
require_once '../includes/admin_header.php';

// Get counts
$total_alumni = $pdo->query("SELECT COUNT(*) FROM alumni")->fetchColumn();
$employed = $pdo->query("SELECT COUNT(*) FROM employment WHERE status IN ('Employed','Self-Employed')")->fetchColumn();
$employment_rate = $total_alumni ? round(($employed / $total_alumni) * 100, 1) : 0;
$active_jobs = $pdo->query("SELECT COUNT(*) FROM job_postings WHERE status='active'")->fetchColumn();

// Top industry
$industry = $pdo->query("SELECT industry, COUNT(*) as cnt FROM employment WHERE industry IS NOT NULL GROUP BY industry ORDER BY cnt DESC LIMIT 1")->fetch();
$top_industry = $industry ? $industry['industry'] : 'N/A';

// Recent registrations with profile pictures
$recent = $pdo->query("SELECT * FROM alumni ORDER BY id DESC LIMIT 5")->fetchAll();

// Employment distribution for pie chart
$dist = $pdo->query("SELECT status, COUNT(*) as count FROM employment GROUP BY status")->fetchAll();
$status_counts = [];
foreach ($dist as $row) {
    $status_counts[$row['status']] = $row['count'];
}
$categories = ['Employed', 'Self-Employed', 'Unemployed', 'Pursuing Higher Education'];
$chart_data = [];
foreach ($categories as $cat) {
    $chart_data[$cat] = $status_counts[$cat] ?? 0;
}
?>
<div class="page-header">
    <h1>Dashboard</h1>
</div>

<!-- Stats Cards -->
<div class="row g-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-title">Total Alumni</div>
            <div class="stat-value"><?= $total_alumni ?></div>
            <div class="stat-label">Registered in system</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card teal">
            <div class="stat-title">Employment Rate</div>
            <div class="stat-value"><?= $employment_rate ?>%</div>
            <div class="stat-label"><?= $employed ?> of <?= $total_alumni ?> employed</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card purple">
            <div class="stat-title">Active Jobs</div>
            <div class="stat-value"><?= $active_jobs ?></div>
            <div class="stat-label">Job postings available</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card emerald">
            <div class="stat-title">Top Industry</div>
            <div class="stat-value"><?= $top_industry ?></div>
            <div class="stat-label">Most common</div>
        </div>
    </div>
</div>

<!-- Recent Registrations and Pie Chart -->
<div class="row mt-4 g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Recent Alumni Registrations</div>
            <div class="card-body p-0">
                <?php foreach ($recent as $al): ?>
                <div class="alumni-item">
                    <?php if ($al['profile_pic'] && file_exists('../' . $al['profile_pic'])): ?>
                        <img src="<?= SITE_URL ?>/<?= $al['profile_pic'] ?>" alt="Profile" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover; margin-right: 1rem;">
                    <?php else: ?>
                        <i class="fas fa-user-circle fa-2x" style="color: var(--primary); margin-right: 1rem;"></i>
                    <?php endif; ?>
                    <div class="alumni-info">
                        <a href="view_alumni.php?id=<?= $al['id'] ?>" class="alumni-name"><?= htmlspecialchars($al['first_name'] . ' ' . $al['last_name']) ?></a>
                        <br><small><?= htmlspecialchars($al['program']) ?> • Class of <?= $al['graduation_year'] ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
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
                    <span class="badge" style="background:#2563EB;">Employed (<?= $chart_data['Employed'] ?>)</span>
                    <span class="badge" style="background:#EF4444;">Self-Employed (<?= $chart_data['Self-Employed'] ?>)</span>
                    <span class="badge" style="background:#F59E0B;">Unemployed (<?= $chart_data['Unemployed'] ?>)</span>
                    <span class="badge" style="background:#10B981;">Higher Ed (<?= $chart_data['Pursuing Higher Education'] ?>)</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('employmentChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Employed', 'Self-Employed', 'Unemployed', 'Higher Education'],
                datasets: [{
                    data: [<?= $chart_data['Employed'] ?>, <?= $chart_data['Self-Employed'] ?>, <?= $chart_data['Unemployed'] ?>, <?= $chart_data['Pursuing Higher Education'] ?>],
                    backgroundColor: ['#2563EB', '#EF4444', '#F59E0B', '#10B981'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12 }
                    }
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>