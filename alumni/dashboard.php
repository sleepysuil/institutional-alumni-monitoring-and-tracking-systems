<?php
require_once '../includes/alumni_header.php';
$alumni_id = $_SESSION['alumni_id'];

$alumni = $pdo->prepare("SELECT * FROM alumni WHERE id = ?");
$alumni->execute([$alumni_id]);
$alumni = $alumni->fetch();

$employment = $pdo->prepare("SELECT * FROM employment WHERE alumni_id = ?");
$employment->execute([$alumni_id]);
$employment = $employment->fetch();

$active_jobs = $pdo->query("SELECT COUNT(*) FROM job_postings WHERE status='active'")->fetchColumn();
?>
<div class="page-header">
    <h1>Welcome back, <?= htmlspecialchars($alumni['first_name']) ?>!</h1>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if ($alumni['profile_pic'] && file_exists('../' . $alumni['profile_pic'])): ?>
                    <img src="<?= SITE_URL ?>/<?= $alumni['profile_pic'] ?>" alt="Profile" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-user-circle fa-5x mb-3" style="color: var(--primary);"></i>
                <?php endif; ?>
                <h5><?= htmlspecialchars($alumni['first_name'] . ' ' . $alumni['last_name']) ?></h5>
                <p class="text-muted"><?= $alumni['student_id'] ?></p>
                <hr>
                <p><strong>Program:</strong> <?= htmlspecialchars($alumni['program']) ?></p>
                <p><strong>Graduated:</strong> <?= $alumni['graduation_year'] ?></p>
                <p><strong>Status:</strong> 
                    <?php if ($employment): ?>
                        <span class="badge <?= strtolower(str_replace(' ', '-', $employment['status'])) ?>"><?= $employment['status'] ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Not updated</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">Current Employment</div>
            <div class="card-body">
                <?php if ($employment): ?>
                    <div class="row">
                        <div class="col-sm-6"><strong>Company:</strong> <?= htmlspecialchars($employment['company'] ?? 'N/A') ?></div>
                        <div class="col-sm-6"><strong>Industry:</strong> <?= htmlspecialchars($employment['industry'] ?? 'N/A') ?></div>
                        <div class="col-sm-6 mt-2"><strong>Position:</strong> <?= htmlspecialchars($employment['position'] ?? 'N/A') ?></div>
                        <div class="col-sm-6 mt-2"><strong>Relevance:</strong> <?= $employment['relevance'] ?? 'N/A' ?></div>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No employment information yet. Please update your profile.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Quick Actions</div>
            <div class="card-body d-flex gap-3 flex-wrap">
                <a href="profile.php" class="btn btn-primary"><i class="fas fa-user-edit me-2"></i>Update Profile</a>
                <a href="job_opportunities.php" class="btn btn-success"><i class="fas fa-briefcase me-2"></i>Browse Jobs (<?= $active_jobs ?>)</a>
                <a href="tracer_survey.php" class="btn btn-info"><i class="fas fa-poll me-2"></i>Tracer Survey</a>
                <a href="newsfeed.php" class="btn btn-outline"><i class="fas fa-newspaper me-2"></i>Newsfeed</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>