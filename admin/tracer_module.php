<?php
require_once '../includes/admin_header.php';
$total_alumni = $pdo->query("SELECT COUNT(*) FROM alumni")->fetchColumn();
$employed = $pdo->query("SELECT COUNT(*) FROM employment WHERE status IN ('Employed','Self-Employed')")->fetchColumn();
$employment_rate = $total_alumni ? round(($employed/$total_alumni)*100,1) : 0;
$unemployed = $pdo->query("SELECT COUNT(*) FROM employment WHERE status='Unemployed'")->fetchColumn();
$job_relevance = $pdo->query("SELECT COUNT(*) FROM employment WHERE relevance='Highly Relevant'")->fetchColumn();
$relevance_rate = $employed ? round(($job_relevance/$employed)*100,1) : 0;
$years = $pdo->query("SELECT graduation_year, COUNT(*) as cnt FROM alumni GROUP BY graduation_year ORDER BY graduation_year DESC")->fetchAll();
?>
<div class="page-header">
    <h1>Tracer Module</h1>
</div>

<ul class="nav-tabs">
    <li><a class="nav-link active" href="tracer_module.php">Overview</a></li>
    <li><a class="nav-link" href="employment_details.php">Employment Details</a></li>
    <li><a class="nav-link" href="job_relevance.php">Job Relevance</a></li>
</ul>

<div class="row g-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-title">Total Alumni</div>
            <div class="stat-value"><?= $total_alumni ?></div>
            <div class="stat-label">All graduates</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card teal">
            <div class="stat-title">Employment Rate</div>
            <div class="stat-value"><?= $employment_rate ?>%</div>
            <div class="stat-label"><?= $employed ?> employed</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card purple">
            <div class="stat-title">Job Relevance</div>
            <div class="stat-value"><?= $relevance_rate ?>%</div>
            <div class="stat-label">Jobs aligned with degree</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card emerald">
            <div class="stat-title">Unemployment</div>
            <div class="stat-value"><?= $unemployed ?></div>
            <div class="stat-label">Seeking employment</div>
        </div>
    </div>
</div>

<div class="row mt-4 g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Employment Status Breakdown</div>
            <div class="card-body">
                <?php
                $statuses = [
                    'Employed' => $pdo->query("SELECT COUNT(*) FROM employment WHERE status='Employed'")->fetchColumn(),
                    'Self-Employed' => $pdo->query("SELECT COUNT(*) FROM employment WHERE status='Self-Employed'")->fetchColumn(),
                    'Unemployed' => $unemployed,
                    'Pursuing Higher Education' => $pdo->query("SELECT COUNT(*) FROM employment WHERE status='Pursuing Higher Education'")->fetchColumn(),
                ];
                ?>
                <div class="list-group">
                    <?php foreach ($statuses as $label => $count): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <?= $label ?>
                        <span class="badge bg-primary rounded-pill"><?= $count ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Response Rate by Year</div>
            <div class="card-body">
                <div class="list-group">
                    <?php foreach ($years as $y): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        Class of <?= $y['graduation_year'] ?>
                        <span class="badge bg-success"><?= $y['cnt'] ?> alumni (100%)</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <button class="btn btn-primary" onclick="sendTracerSurvey()"><i class="fas fa-paper-plane me-2"></i>Send Tracer Survey</button>
</div>

<script>
function sendTracerSurvey() {
    if(confirm('Send tracer survey to all alumni?')) {
        alert('Survey notifications sent!');
    }
}
</script>

<?php include '../includes/footer.php'; ?>