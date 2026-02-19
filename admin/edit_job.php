<?php
require_once '../includes/admin_header.php';
$id = $_GET['id'] ?? 0;
$job = $pdo->prepare("SELECT * FROM job_postings WHERE id = ?");
$job->execute([$id]);
$job = $job->fetch();
if (!$job) redirect('job_postings.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = cleanInput($_POST['title']);
    $company = cleanInput($_POST['company']);
    $location = cleanInput($_POST['location']);
    $description = cleanInput($_POST['description']);
    $requirements = cleanInput($_POST['requirements']);
    $deadline = $_POST['deadline'];
    $status = $_POST['status'];
    $pdo->prepare("UPDATE job_postings SET title=?, company=?, location=?, description=?, requirements=?, deadline=?, status=? WHERE id=?")
        ->execute([$title, $company, $location, $description, $requirements, $deadline, $status, $id]);
    $_SESSION['message'] = "Job updated.";
    redirect('job_postings.php');
}
?>
<div class="page-header">
    <h1>Edit Job</h1>
</div>

<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Job Title</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($job['title']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Company</label>
                    <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($job['company']) ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($job['location']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Deadline</label>
                    <input type="date" name="deadline" class="form-control" value="<?= $job['deadline'] ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($job['description']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Requirements</label>
                <textarea name="requirements" class="form-control" rows="3"><?= htmlspecialchars($job['requirements']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $job['status']=='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $job['status']=='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="job_postings.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>