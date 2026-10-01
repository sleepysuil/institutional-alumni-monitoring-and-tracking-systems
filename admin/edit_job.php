<?php
require_once '../includes/admin_header.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM job_postings WHERE id = ?");
$stmt->execute([$id]);
$job = $stmt->fetch();
if (!$job) redirect('job_postings.php');

// Number of applicants for this job
$appCount = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ?");
$appCount->execute([$id]);
$applicants = (int)$appCount->fetchColumn();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        $title = cleanInput($_POST['title'] ?? '');
        $company = cleanInput($_POST['company'] ?? '');
        $location = cleanInput($_POST['location'] ?? '');
        $description = cleanInput($_POST['description'] ?? '');
        $requirements = cleanInput($_POST['requirements'] ?? '');
        $deadline = trim($_POST['deadline'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'inactive';

        if ($title === '' || $company === '') $errors[] = "Job title and company are required.";
        $d = DateTime::createFromFormat('Y-m-d', $deadline);
        if (!$d || $d->format('Y-m-d') !== $deadline) {
            $errors[] = "Please enter a valid deadline.";
        } elseif ($deadline < $job['posted_date']) {
            $errors[] = "Deadline cannot be earlier than the posted date (" . date('M j, Y', strtotime($job['posted_date'])) . ").";
        }

        if (!$errors) {
            $pdo->prepare("UPDATE job_postings SET title=?, company=?, location=?, description=?, requirements=?, deadline=?, status=? WHERE id=?")
                ->execute([$title, $company, $location, $description, $requirements, $deadline, $status, $id]);
            $_SESSION['message'] = "Job updated.";
            redirect('job_postings.php');
        }
        // keep what the admin typed so nothing is lost on error
        $job = array_merge($job, compact('title', 'company', 'location', 'description', 'requirements', 'deadline', 'status'));
    }
}
$e = fn($v) => htmlspecialchars((string)($v ?? ''));
$expired = !empty($job['deadline']) && $job['deadline'] < date('Y-m-d');
?>
<div class="page-header">
    <h1>Edit Job</h1>
    <?php if ($applicants): ?>
        <a href="applications.php?job_id=<?= $id ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-users me-1"></i><?= $applicants ?> applicant<?= $applicants > 1 ? 's' : '' ?></a>
    <?php else: ?>
        <span class="text-muted small">No applicants yet</span>
    <?php endif; ?>
</div>

<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= $e($err) ?></div><?php endforeach; ?>
<?php if ($expired && $job['status'] === 'active'): ?>
    <div class="alert alert-warning">The deadline has passed. Alumni can no longer apply, even though the job is still marked Active.</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Job Title</label>
                    <input type="text" name="title" class="form-control" maxlength="100" value="<?= $e($job['title']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Company</label>
                    <input type="text" name="company" class="form-control" maxlength="100" value="<?= $e($job['company']) ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" maxlength="100" value="<?= $e($job['location']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Deadline</label>
                    <input type="date" name="deadline" class="form-control" value="<?= $e($job['deadline']) ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= $e($job['description']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Requirements</label>
                <textarea name="requirements" class="form-control" rows="3"><?= $e($job['requirements']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $job['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $job['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="job_postings.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>