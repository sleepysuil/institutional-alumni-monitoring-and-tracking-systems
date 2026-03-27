<?php
require_once '../includes/admin_header.php';

$job_id = $_GET['job_id'] ?? 0;

$jobs = $pdo->query("SELECT id, title FROM job_postings ORDER BY title")->fetchAll();

if ($job_id) {
    $stmt = $pdo->prepare("SELECT a.*, j.title as job_title, j.company, al.first_name, al.last_name, al.email 
                           FROM applications a 
                           JOIN job_postings j ON a.job_id = j.id 
                           JOIN alumni al ON a.alumni_id = al.id 
                           WHERE a.job_id = ?
                           ORDER BY a.applied_date DESC");
    $stmt->execute([$job_id]);
} else {
    $stmt = $pdo->query("SELECT a.*, j.title as job_title, j.company, al.first_name, al.last_name, al.email 
                         FROM applications a 
                         JOIN job_postings j ON a.job_id = j.id 
                         JOIN alumni al ON a.alumni_id = al.id 
                         ORDER BY a.applied_date DESC");
}
$apps = $stmt->fetchAll();
?>
<div class="page-header">
    <h1>Job Applications</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Filter by Job</label>
                <select name="job_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Jobs</option>
                    <?php foreach ($jobs as $j): ?>
                    <option value="<?= $j['id'] ?>" <?= $job_id == $j['id'] ? 'selected' : '' ?>><?= htmlspecialchars($j['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Applied Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apps as $app): ?>
                    <tr>
                        <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?><br><small><?= $app['email'] ?></small></td>
                        <td><?= htmlspecialchars($app['job_title']) ?></td>
                        <td><?= htmlspecialchars($app['company']) ?></td>
                        <td><?= date('M j, Y', strtotime($app['applied_date'])) ?></td>
                        <td>
                            <?php
                            $status_class = match($app['status']) {
                                'pending' => 'warning',
                                'reviewed' => 'info',
                                'accepted' => 'success',
                                'rejected' => 'danger',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $status_class ?>"><?= ucfirst($app['status']) ?></span>
                        </td>
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