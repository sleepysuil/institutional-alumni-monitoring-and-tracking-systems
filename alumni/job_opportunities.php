<?php
require_once '../includes/alumni_header.php';
$jobs = $pdo->query("SELECT * FROM job_postings WHERE status='active' ORDER BY posted_date DESC")->fetchAll();
?>
<div class="page-header">
    <h1>Job Opportunities</h1>
</div>

<?php if (empty($jobs)): ?>
    <div class="alert alert-info">No job opportunities available at the moment.</div>
<?php else: ?>
<div class="row g-4">
    <?php foreach ($jobs as $job): ?>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($job['title']) ?></h5>
                <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($job['company']) ?> • <?= htmlspecialchars($job['location']) ?></h6>
                <p class="card-text small"><?= htmlspecialchars(substr($job['description'],0,120)) ?>...</p>
                <p class="small mb-2"><i class="far fa-calendar-alt me-1"></i> Deadline: <?= $job['deadline'] ?></p>
                <a href="job_details.php?id=<?= $job['id'] ?>" class="btn btn-primary btn-sm">View Details</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>