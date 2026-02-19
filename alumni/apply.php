<?php
require_once '../includes/alumni_header.php';
$alumni_id = $_SESSION['alumni_id'];
$job_id = $_GET['job_id'] ?? 0;
$check = $pdo->prepare("SELECT * FROM applications WHERE alumni_id = ? AND job_id = ?");
$check->execute([$alumni_id, $job_id]);
if ($check->fetch()) {
    $error = "You have already applied for this job.";
} else {
    $pdo->prepare("INSERT INTO applications (alumni_id, job_id, applied_date, status) VALUES (?, ?, NOW(), 'pending')")->execute([$alumni_id, $job_id]);
    $success = "Application submitted successfully!";
}
?>
<div class="page-header">
    <h1>Apply for Job</h1>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
    <a href="job_opportunities.php" class="btn btn-primary">Back to Jobs</a>
<?php elseif (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
    <a href="job_opportunities.php" class="btn btn-primary">Back to Jobs</a>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>