<?php
require_once '../includes/alumni_header.php';
$alumni_id = $_SESSION['alumni_id'];

// Fetch all applications including rejected
$apps = $pdo->prepare("
    SELECT a.*, j.title, j.company, j.location 
    FROM applications a 
    JOIN job_postings j ON a.job_id = j.id 
    WHERE a.alumni_id = ? 
    ORDER BY a.applied_date DESC
");
$apps->execute([$alumni_id]);
$apps = $apps->fetchAll();
?>
<div class="page-header">
    <h1>My Job Applications</h1>
</div>

<?php if (count($apps) == 0): ?>
    <div class="alert alert-info">You haven't applied to any jobs yet.</div>
<?php else: ?>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Location</th>
                        <th>Applied Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apps as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['title']) ?></td>
                        <td><?= htmlspecialchars($a['company']) ?></td>
                        <td><?= htmlspecialchars($a['location']) ?></td>
                        <td><?= date('M j, Y', strtotime($a['applied_date'])) ?></td>
                        <td>
                            <?php
                            $status_class = match($a['status']) {
                                'pending' => 'warning',
                                'reviewed' => 'info',
                                'accepted' => 'success',
                                'rejected' => 'danger',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $status_class ?>"><?= ucfirst($a['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>