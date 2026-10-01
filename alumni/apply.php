<?php
require_once '../includes/alumni_header.php';
$alumni_id = $_SESSION['alumni_id'];

// Applying changes data, so only accept POST requests with a valid CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('job_opportunities.php');
}
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    $error = "Invalid request. Please go back and try again.";
} else {
    $job_id = (int)($_POST['job_id'] ?? 0);

    // Job must exist, be active and not past its deadline
    $jobStmt = $pdo->prepare("SELECT id, title, company FROM job_postings
                              WHERE id = ? AND status = 'active' AND (deadline IS NULL OR deadline >= CURDATE())");
    $jobStmt->execute([$job_id]);
    $job = $jobStmt->fetch();

    if (!$job) {
        $error = "This job is no longer available or the deadline has passed.";
    } else {
        $check = $pdo->prepare("SELECT id FROM applications WHERE alumni_id = ? AND job_id = ?");
        $check->execute([$alumni_id, $job_id]);
        if ($check->fetch()) {
            $error = "You have already applied for this job.";
        } else {
            $pdo->prepare("INSERT INTO applications (alumni_id, job_id, applied_date, status) VALUES (?, ?, NOW(), 'pending')")
                ->execute([$alumni_id, $job_id]);
            $success = "Your application for " . htmlspecialchars($job['title']) . " at " . htmlspecialchars($job['company']) . " was submitted successfully!";
        }
    }
}
?>
<div class="page-header">
    <h1>Apply for Job</h1>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
    <a href="application_status.php" class="btn btn-success"><i class="fas fa-list me-2"></i>View My Applications</a>
    <a href="job_opportunities.php" class="btn btn-primary">Back to Jobs</a>
<?php elseif (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <a href="job_opportunities.php" class="btn btn-primary">Back to Jobs</a>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>