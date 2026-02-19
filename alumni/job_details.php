<?php
require_once '../includes/alumni_header.php';
$job_id = $_GET['id'] ?? 0;
$job = $pdo->prepare("SELECT * FROM job_postings WHERE id = ? AND status='active'");
$job->execute([$job_id]);
$job = $job->fetch();
if (!$job) { echo "<div class='alert alert-warning'>Job not found.</div>"; include '../includes/footer.php'; exit; }
?>
<div class="page-header">
    <h1><?= htmlspecialchars($job['title']) ?></h1>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title"><?= htmlspecialchars($job['company']) ?></h5>
        <h6 class="card-subtitle mb-3 text-muted"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($job['location']) ?></h6>
        <hr>
        <h6>Job Description</h6>
        <p><?= nl2br(htmlspecialchars($job['description'])) ?></p>
        <h6>Requirements</h6>
        <p><?= nl2br(htmlspecialchars($job['requirements'])) ?></p>
        <hr>
        <p><strong>Posted:</strong> <?= $job['posted_date'] ?></p>
        <p><strong>Deadline:</strong> <?= $job['deadline'] ?></p>
        <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn btn-success"><i class="fas fa-paper-plane me-2"></i>Apply Now</a>
        <a href="job_opportunities.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>