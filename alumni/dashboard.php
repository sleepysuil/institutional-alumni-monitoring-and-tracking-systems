<?php
require_once '../includes/alumni_header.php';
$alumni_id = $_SESSION['alumni_id'];

$alumni = $pdo->prepare("SELECT * FROM alumni WHERE id = ?");
$alumni->execute([$alumni_id]);
$alumni = $alumni->fetch();

$employment = $pdo->prepare("SELECT * FROM employment WHERE alumni_id = ?");
$employment->execute([$alumni_id]);
$employment = $employment->fetch();

// Open jobs (active and not past deadline)
$active_jobs = $pdo->query("SELECT COUNT(*) FROM job_postings WHERE status='active' AND (deadline IS NULL OR deadline >= CURDATE())")->fetchColumn();

// My applications summary
$appStmt = $pdo->prepare("SELECT status, COUNT(*) FROM applications WHERE alumni_id = ? GROUP BY status");
$appStmt->execute([$alumni_id]);
$app_counts = $appStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$total_apps = array_sum($app_counts);
$pending_apps = $app_counts['pending'] ?? 0;
$accepted_apps = $app_counts['accepted'] ?? 0;

// Latest tracer survey response
$surveyStmt = $pdo->prepare("SELECT MAX(response_date) FROM tracer_responses WHERE alumni_id = ?");
$surveyStmt->execute([$alumni_id]);
$last_survey = $surveyStmt->fetchColumn();
$survey_due = !$last_survey || strtotime($last_survey) < strtotime('-1 year');

// Latest announcements
$announcements = $pdo->query("SELECT title, posted_date FROM announcements ORDER BY posted_date DESC LIMIT 3")->fetchAll();

// Profile completeness
$checks = [
    'Phone number'    => !empty($alumni['phone']),
    'Age'             => !empty($alumni['age']),
    'Gender'          => !empty($alumni['gender']),
    'Profile picture' => !empty($alumni['profile_pic']),
    'Employment info' => (bool)$employment,
];
$completeness = (int)round(count(array_filter($checks)) / count($checks) * 100);
$missing = array_keys(array_filter($checks, fn($v) => !$v));
?>
<div class="page-header">
    <h1>Welcome back, <?= htmlspecialchars($alumni['first_name']) ?>!</h1>
</div>

<?php if ($survey_due): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center">
    <span><i class="fas fa-poll me-2"></i>
        <?= $last_survey ? 'It has been over a year since your last tracer survey.' : 'You have not answered the tracer survey yet.' ?>
    </span>
    <a href="tracer_survey.php" class="btn btn-sm btn-warning">Take Survey</a>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if (!empty($alumni['profile_pic']) && file_exists('../' . $alumni['profile_pic'])): ?>
                    <img src="<?= SITE_URL ?>/<?= htmlspecialchars($alumni['profile_pic']) ?>" alt="Profile" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-user-circle fa-5x mb-3" style="color: var(--primary);"></i>
                <?php endif; ?>
                <h5><?= htmlspecialchars($alumni['first_name'] . ' ' . $alumni['last_name']) ?></h5>
                <p class="text-muted"><?= htmlspecialchars($alumni['student_id']) ?></p>
                <hr>
                <p><strong>Program:</strong> <?= htmlspecialchars($alumni['program']) ?></p>
                <p><strong>Graduated:</strong> <?= (int)$alumni['graduation_year'] ?></p>
                <p><strong>Status:</strong>
                    <?php if ($employment): ?>
                        <span class="badge <?= strtolower(str_replace(' ', '-', $employment['status'])) ?>"><?= htmlspecialchars($employment['status']) ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Not updated</span>
                    <?php endif; ?>
                </p>
                <hr>
                <div class="text-start">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Profile completeness</span><strong><?= $completeness ?>%</strong>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar <?= $completeness == 100 ? 'bg-success' : '' ?>" style="width: <?= $completeness ?>%"></div>
                    </div>
                    <?php if ($missing): ?>
                        <small class="text-muted d-block mt-2">Missing: <?= htmlspecialchars(implode(', ', $missing)) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row g-3 mb-4">
            <div class="col-4">
                <a href="job_opportunities.php" class="text-decoration-none">
                    <div class="card text-center"><div class="card-body py-3">
                        <div class="fs-3 fw-bold text-primary"><?= (int)$active_jobs ?></div>
                        <div class="small text-muted">Open Jobs</div>
                    </div></div>
                </a>
            </div>
            <div class="col-4">
                <a href="application_status.php" class="text-decoration-none">
                    <div class="card text-center"><div class="card-body py-3">
                        <div class="fs-3 fw-bold text-warning"><?= $pending_apps ?></div>
                        <div class="small text-muted">Pending Applications</div>
                    </div></div>
                </a>
            </div>
            <div class="col-4">
                <a href="application_status.php?status=accepted" class="text-decoration-none">
                    <div class="card text-center"><div class="card-body py-3">
                        <div class="fs-3 fw-bold text-success"><?= $accepted_apps ?></div>
                        <div class="small text-muted">Accepted (of <?= $total_apps ?>)</div>
                    </div></div>
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Current Employment</div>
            <div class="card-body">
                <?php if ($employment): ?>
                    <div class="row">
                        <div class="col-sm-6"><strong>Company:</strong> <?= htmlspecialchars($employment['company'] ?? 'N/A') ?></div>
                        <div class="col-sm-6"><strong>Industry:</strong> <?= htmlspecialchars($employment['industry'] ?? 'N/A') ?></div>
                        <div class="col-sm-6 mt-2"><strong>Position:</strong> <?= htmlspecialchars($employment['position'] ?? 'N/A') ?></div>
                        <div class="col-sm-6 mt-2"><strong>Relevance:</strong> <?= htmlspecialchars($employment['relevance'] ?? 'N/A') ?></div>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No employment information yet. Please update your profile.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($announcements): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <span>Latest Announcements</span>
                <a href="newsfeed.php?filter=announcements" class="small">View all</a>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($announcements as $an): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><i class="fas fa-bullhorn text-warning me-2"></i><?= htmlspecialchars($an['title']) ?></span>
                    <small class="text-muted"><?= date('M j, Y', strtotime($an['posted_date'])) ?></small>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">Quick Actions</div>
            <div class="card-body d-flex gap-3 flex-wrap">
                <a href="profile.php" class="btn btn-primary"><i class="fas fa-user-edit me-2"></i>Update Profile</a>
                <a href="job_opportunities.php" class="btn btn-success"><i class="fas fa-briefcase me-2"></i>Browse Jobs (<?= (int)$active_jobs ?>)</a>
                <a href="application_status.php" class="btn btn-secondary"><i class="fas fa-list me-2"></i>My Applications</a>
                <a href="tracer_survey.php" class="btn btn-info"><i class="fas fa-poll me-2"></i>Tracer Survey</a>
                <a href="newsfeed.php" class="btn btn-outline"><i class="fas fa-newspaper me-2"></i>Newsfeed</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>