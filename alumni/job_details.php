<?php
require_once '../includes/alumni_header.php';
$alumni_id = $_SESSION['alumni_id'];
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$job_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM job_postings WHERE id = ? AND status='active'");
$stmt->execute([$job_id]);
$job = $stmt->fetch();
if (!$job) {
    echo "<div class='alert alert-warning'>Job not found.</div>";
    include '../includes/footer.php';
    exit;
}

// Has this alumnus already applied?
$appStmt = $pdo->prepare("SELECT status, applied_date FROM applications WHERE alumni_id = ? AND job_id = ?");
$appStmt->execute([$alumni_id, $job_id]);
$application = $appStmt->fetch();

$is_expired = !empty($job['deadline']) && strtotime($job['deadline']) < strtotime('today');
$days_left = !empty($job['deadline']) ? (int)floor((strtotime($job['deadline']) - strtotime('today')) / 86400) : null;
?>
<div class="page-header">
    <h1><?= htmlspecialchars($job['title']) ?></h1>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title"><?= htmlspecialchars($job['company']) ?></h5>
        <h6 class="card-subtitle mb-3 text-muted"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($job['location'] ?? '') ?></h6>
        <hr>
        <h6>Job Description</h6>
        <p><?= nl2br(htmlspecialchars($job['description'] ?? '')) ?></p>
        <h6>Requirements</h6>
        <p><?= nl2br(htmlspecialchars($job['requirements'] ?? '')) ?></p>
        <hr>
        <p><strong>Posted:</strong> <?= $job['posted_date'] ? date('M j, Y', strtotime($job['posted_date'])) : 'N/A' ?></p>
        <p>
            <strong>Deadline:</strong> <?= $job['deadline'] ? date('M j, Y', strtotime($job['deadline'])) : 'No deadline' ?>
            <?php if ($is_expired): ?>
                <span class="badge bg-danger ms-2">Closed</span>
            <?php elseif ($days_left !== null && $days_left <= 7): ?>
                <span class="badge bg-warning text-dark ms-2"><?= $days_left === 0 ? 'Closes today' : "Closes in $days_left day" . ($days_left > 1 ? 's' : '') ?></span>
            <?php endif; ?>
        </p>

        <?php if ($application): ?>
            <div class="alert alert-info d-inline-block mb-3">
                <i class="fas fa-check-circle me-1"></i>
                You applied on <?= date('M j, Y', strtotime($application['applied_date'])) ?>
                (status: <strong><?= ucfirst($application['status']) ?></strong>).
            </div>
            <br>
            <a href="application_status.php" class="btn btn-success">View My Applications</a>
        <?php elseif ($is_expired): ?>
            <button class="btn btn-secondary" disabled>Applications Closed</button>
        <?php else: ?>
            <form method="post" action="apply.php" class="d-inline"
                  onsubmit="return confirm('Submit your application for this job?')">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-2"></i>Apply Now</button>
            </form>
        <?php endif; ?>
        <a href="job_opportunities.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>