<?php
require_once '../includes/admin_header.php';
$apps = $pdo->query("SELECT a.*, j.title as job_title, al.first_name, al.last_name, al.email 
                     FROM applications a 
                     JOIN job_postings j ON a.job_id = j.id 
                     JOIN alumni al ON a.alumni_id = al.id 
                     ORDER BY a.applied_date DESC")->fetchAll();
?>
<div class="page-header">
    <h1>Job Applications</h1>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>Applicant</th><th>Job</th><th>Applied</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($apps as $app): ?>
                    <tr>
                        <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?><br><small><?= $app['email'] ?></small></td>
                        <td><?= htmlspecialchars($app['job_title']) ?></td>
                        <td><?= date('M j, Y', strtotime($app['applied_date'])) ?></td>
                        <td><span class="badge bg-<?= $app['status']=='pending'?'warning':($app['status']=='accepted'?'success':'secondary') ?>"><?= $app['status'] ?></span></td>
                        <td>
                            <a href="update_application.php?id=<?= $app['id'] ?>&status=reviewed" class="btn btn-sm btn-info">Reviewed</a>
                            <a href="update_application.php?id=<?= $app['id'] ?>&status=accepted" class="btn btn-sm btn-success">Accept</a>
                            <a href="update_application.php?id=<?= $app['id'] ?>&status=rejected" class="btn btn-sm btn-danger">Reject</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>