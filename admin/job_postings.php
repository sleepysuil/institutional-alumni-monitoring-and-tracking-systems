<?php
require_once '../includes/admin_header.php';

// Handle add job
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_job'])) {
    $title = cleanInput($_POST['title']);
    $company = cleanInput($_POST['company']);
    $location = cleanInput($_POST['location']);
    $description = cleanInput($_POST['description']);
    $requirements = cleanInput($_POST['requirements']);
    $deadline = $_POST['deadline'];
    $stmt = $pdo->prepare("INSERT INTO job_postings (title, company, location, description, requirements, posted_date, deadline, status, created_by) VALUES (?,?,?,?,?, CURDATE(), ?, 'active', ?)");
    $stmt->execute([$title, $company, $location, $description, $requirements, $deadline, $_SESSION['user_id']]);
    $_SESSION['message'] = "Job posted successfully.";
    redirect('job_postings.php');
}

// Handle deactivate/activate
if (isset($_GET['deactivate'])) {
    $id = $_GET['deactivate'];
    $pdo->prepare("UPDATE job_postings SET status='inactive' WHERE id=?")->execute([$id]);
    $_SESSION['message'] = "Job deactivated.";
    redirect('job_postings.php');
}
if (isset($_GET['activate'])) {
    $id = $_GET['activate'];
    $pdo->prepare("UPDATE job_postings SET status='active' WHERE id=?")->execute([$id]);
    $_SESSION['message'] = "Job activated.";
    redirect('job_postings.php');
}

// Filter by status
$status_filter = $_GET['status'] ?? 'all';
$sql = "SELECT j.*, 
               (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as applications,
               u.email as poster_email
        FROM job_postings j
        JOIN users u ON j.created_by = u.id";
$params = [];
if ($status_filter != 'all') {
    $sql .= " WHERE j.status = ?";
    $params[] = $status_filter;
}
$sql .= " ORDER BY j.posted_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<div class="page-header">
    <h1>Job Postings</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJobModal"><i class="fas fa-plus me-2"></i>Post New Job</button>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Filter by Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All</option>
                    <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
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
                    <tr><th>Title</th><th>Company</th><th>Location</th><th>Posted By</th><th>Posted</th><th>Deadline</th><th>Status</th><th>Applications</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                    <tr>
                        <td><?= htmlspecialchars($job['title']) ?></td>
                        <td><?= htmlspecialchars($job['company']) ?></td>
                        <td><?= htmlspecialchars($job['location']) ?></td>
                        <td><?= htmlspecialchars($job['poster_email']) ?></td>
                        <td><?= $job['posted_date'] ?></td>
                        <td><?= $job['deadline'] ?></td>
                        <td><span class="badge bg-<?= $job['status']=='active'?'success':'secondary' ?>"><?= $job['status'] ?></span></td>
                        <td><a href="applications.php?job_id=<?= $job['id'] ?>" class="badge bg-info"><?= $job['applications'] ?></a></td>
                        <td>
                            <a href="edit_job.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>
                            <?php if ($job['status'] == 'active'): ?>
                                <a href="?deactivate=<?= $job['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirm('Deactivate?')"><i class="fas fa-ban"></i></a>
                            <?php else: ?>
                                <a href="?activate=<?= $job['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Activate?')"><i class="fas fa-check"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Job Modal (unchanged, but include for completeness) -->
<div class="modal fade" id="addJobModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Post New Job</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Job Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Company</label>
                            <input type="text" name="company" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Deadline</label>
                            <input type="date" name="deadline" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Requirements</label>
                        <textarea name="requirements" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_job" class="btn btn-primary">Post Job</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>