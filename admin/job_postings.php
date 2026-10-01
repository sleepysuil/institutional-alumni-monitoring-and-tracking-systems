<?php
require_once '../includes/admin_header.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['message'] = "Invalid request. Please try again.";
        $_SESSION['message_type'] = 'danger';
        redirect('job_postings.php');
    }

    // Add job
    if (isset($_POST['add_job'])) {
        $title = cleanInput($_POST['title'] ?? '');
        $company = cleanInput($_POST['company'] ?? '');
        $location = cleanInput($_POST['location'] ?? '');
        $description = cleanInput($_POST['description'] ?? '');
        $requirements = cleanInput($_POST['requirements'] ?? '');
        $deadline = trim($_POST['deadline'] ?? '');

        $d = DateTime::createFromFormat('Y-m-d', $deadline);
        if ($title === '' || $company === '') $errors[] = "Job title and company are required.";
        if (!$d || $d->format('Y-m-d') !== $deadline) $errors[] = "Please enter a valid deadline.";
        elseif ($deadline < date('Y-m-d')) $errors[] = "The deadline cannot be in the past.";

        if (!$errors) {
            $pdo->prepare("INSERT INTO job_postings (title, company, location, description, requirements, posted_date, deadline, status, created_by) VALUES (?,?,?,?,?, CURDATE(), ?, 'active', ?)")
                ->execute([$title, $company, $location, $description, $requirements, $deadline, $_SESSION['user_id']]);
            $_SESSION['message'] = "Job posted successfully.";
            redirect('job_postings.php');
        }
        $old = compact('title', 'company', 'location', 'description', 'requirements', 'deadline');
    }

    // Activate / deactivate
    if (isset($_POST['set_status'])) {
        $new = $_POST['set_status'] === 'active' ? 'active' : 'inactive';
        $pdo->prepare("UPDATE job_postings SET status = ? WHERE id = ?")->execute([$new, (int)($_POST['job_id'] ?? 0)]);
        $_SESSION['message'] = $new === 'active' ? "Job activated." : "Job deactivated.";
        redirect('job_postings.php' . (!empty($_POST['return_status']) ? '?status=' . urlencode($_POST['return_status']) : ''));
    }
}

// Filters
$status_filter = in_array($_GET['status'] ?? 'all', ['all', 'active', 'inactive', 'expired'], true) ? ($_GET['status'] ?? 'all') : 'all';
$search = trim($_GET['q'] ?? '');

// LEFT JOIN so jobs are still listed if the poster's account was removed
$sql = "SELECT j.*,
               (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as applications,
               u.email as poster_email
        FROM job_postings j
        LEFT JOIN users u ON j.created_by = u.id
        WHERE 1";
$params = [];
if ($status_filter === 'active' || $status_filter === 'inactive') { $sql .= " AND j.status = ?"; $params[] = $status_filter; }
if ($status_filter === 'expired') { $sql .= " AND j.deadline < CURDATE()"; }
if ($search !== '') {
    $sql .= " AND (j.title LIKE ? OR j.company LIKE ? OR j.location LIKE ?)";
    array_push($params, "%$search%", "%$search%", "%$search%");
}
$sql .= " ORDER BY j.posted_date DESC, j.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? 'success';
unset($_SESSION['message'], $_SESSION['message_type']);
$csrf = $_SESSION['csrf_token'];
$e = fn($v) => htmlspecialchars((string)($v ?? ''));
$old = $old ?? [];
?>
<div class="page-header">
    <h1>Job Postings</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJobModal"><i class="fas fa-plus me-2"></i>Post New Job</button>
</div>

<?php if ($message): ?><div class="alert alert-<?= $e($message_type) ?>"><?= $e($message) ?></div><?php endif; ?>
<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= $e($err) ?></div><?php endforeach; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All</option>
                    <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="expired" <?= $status_filter == 'expired' ? 'selected' : '' ?>>Past deadline</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Title, company or location" value="<?= $e($search) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit"><i class="fas fa-search"></i></button>
                <?php if ($search !== '' || $status_filter !== 'all'): ?><a href="job_postings.php" class="btn btn-outline-secondary">Clear</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($jobs)): ?>
            <p class="text-muted mb-0">No job postings found.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Title</th><th>Company</th><th>Location</th><th>Posted By</th><th>Posted</th><th>Deadline</th><th>Status</th><th>Applications</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job):
                        $expired = !empty($job['deadline']) && $job['deadline'] < date('Y-m-d'); ?>
                    <tr>
                        <td><?= $e($job['title']) ?></td>
                        <td><?= $e($job['company']) ?></td>
                        <td><?= $e($job['location']) ?></td>
                        <td><?= $e($job['poster_email'] ?? 'Unknown') ?></td>
                        <td><?= $job['posted_date'] ? date('M j, Y', strtotime($job['posted_date'])) : '' ?></td>
                        <td>
                            <?= $job['deadline'] ? date('M j, Y', strtotime($job['deadline'])) : 'None' ?>
                            <?php if ($expired): ?><span class="badge bg-danger">Passed</span><?php endif; ?>
                        </td>
                        <td><span class="badge bg-<?= $job['status'] == 'active' ? 'success' : 'secondary' ?>"><?= $e($job['status']) ?></span></td>
                        <td><a href="applications.php?job_id=<?= (int)$job['id'] ?>" class="badge bg-info"><?= (int)$job['applications'] ?></a></td>
                        <td>
                            <a href="edit_job.php?id=<?= (int)$job['id'] ?>" class="btn btn-sm btn-info" title="Edit"><i class="fas fa-edit"></i></a>
                            <form method="post" class="d-inline" onsubmit="return confirm('<?= $job['status'] == 'active' ? 'Deactivate' : 'Activate' ?> this job?')">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
                                <input type="hidden" name="return_status" value="<?= $e($status_filter !== 'all' ? $status_filter : '') ?>">
                                <?php if ($job['status'] == 'active'): ?>
                                    <button name="set_status" value="inactive" class="btn btn-sm btn-warning" title="Deactivate"><i class="fas fa-ban"></i></button>
                                <?php else: ?>
                                    <button name="set_status" value="active" class="btn btn-sm btn-success" title="Activate"><i class="fas fa-check"></i></button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Job Modal -->
<div class="modal fade" id="addJobModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Post New Job</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Job Title</label>
                            <input type="text" name="title" class="form-control" maxlength="100" value="<?= $e($old['title'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Company</label>
                            <input type="text" name="company" class="form-control" maxlength="100" value="<?= $e($old['company'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" maxlength="100" value="<?= $e($old['location'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Deadline</label>
                            <input type="date" name="deadline" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= $e($old['deadline'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= $e($old['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Requirements</label>
                        <textarea name="requirements" class="form-control" rows="3"><?= $e($old['requirements'] ?? '') ?></textarea>
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

<?php if ($errors): ?>
<script>document.addEventListener('DOMContentLoaded', function () { new bootstrap.Modal(document.getElementById('addJobModal')).show(); });</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>  