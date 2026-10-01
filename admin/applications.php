<?php
require_once '../includes/admin_header.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$statuses = ['pending', 'reviewed', 'accepted', 'rejected'];
$job_id = (int)($_GET['job_id'] ?? 0);
$status_filter = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$search = trim($_GET['q'] ?? '');

// Status change is handled here with POST + CSRF (replaces the old GET links to update_application.php)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_status'])) {
    $ok = hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '');
    $new = $_POST['new_status'];
    if ($ok && in_array($new, $statuses, true)) {
        $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([$new, (int)($_POST['application_id'] ?? 0)]);
        $_SESSION['message'] = "Application marked as " . $new . ".";
    } else {
        $_SESSION['message'] = "Invalid request. Please try again.";
    }
    $back = array_filter([
        'job_id' => (int)($_POST['job_id'] ?? 0) ?: null,
        'status' => in_array($_POST['filter_status'] ?? '', $statuses, true) ? $_POST['filter_status'] : null,
        'q'      => trim($_POST['q'] ?? '') ?: null,
    ]);
    redirect('applications.php' . ($back ? '?' . http_build_query($back) : ''));
}
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$jobs = $pdo->query("SELECT id, title FROM job_postings ORDER BY title")->fetchAll();

// Status counts (respecting the job filter only, so the cards stay useful)
$cSql = "SELECT status, COUNT(*) FROM applications" . ($job_id ? " WHERE job_id = ?" : "") . " GROUP BY status";
$cStmt = $pdo->prepare($cSql);
$cStmt->execute($job_id ? [$job_id] : []);
$counts = array_merge(array_fill_keys($statuses, 0), $cStmt->fetchAll(PDO::FETCH_KEY_PAIR));

// Applications list
$sql = "SELECT a.*, j.title AS job_title, j.company, al.first_name, al.last_name, al.email
        FROM applications a
        JOIN job_postings j ON a.job_id = j.id
        JOIN alumni al ON a.alumni_id = al.id
        WHERE 1";
$params = [];
if ($job_id)        { $sql .= " AND a.job_id = ?"; $params[] = $job_id; }
if ($status_filter) { $sql .= " AND a.status = ?"; $params[] = $status_filter; }
if ($search !== '') {
    $sql .= " AND (al.first_name LIKE ? OR al.last_name LIKE ? OR al.email LIKE ?)";
    array_push($params, "%$search%", "%$search%", "%$search%");
}
$sql .= " ORDER BY FIELD(a.status,'pending','reviewed','accepted','rejected'), a.applied_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$apps = $stmt->fetchAll();

$badge = ['pending' => 'warning', 'reviewed' => 'info', 'accepted' => 'success', 'rejected' => 'danger'];
$e = fn($v) => htmlspecialchars((string)($v ?? ''));
?>
<div class="page-header">
    <h1>Job Applications</h1>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= $e($message) ?></div><?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ($statuses as $s): ?>
    <div class="col-6 col-md-3">
        <a href="?<?= http_build_query(array_filter(['job_id' => $job_id ?: null, 'status' => $s])) ?>" class="text-decoration-none">
            <div class="card text-center <?= $status_filter === $s ? 'border-primary' : '' ?>">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-<?= $badge[$s] ?>"><?= (int)$counts[$s] ?></div>
                    <div class="small text-muted"><?= ucfirst($s) ?></div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Job</label>
                <select name="job_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Jobs</option>
                    <?php foreach ($jobs as $j): ?>
                    <option value="<?= (int)$j['id'] ?>" <?= $job_id == $j['id'] ? 'selected' : '' ?>><?= $e($j['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Applicant</label>
                <input type="text" name="q" class="form-control" placeholder="Name or email" value="<?= $e($search) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit"><i class="fas fa-search"></i></button>
                <?php if ($job_id || $status_filter || $search !== ''): ?><a href="applications.php" class="btn btn-outline-secondary" title="Clear"><i class="fas fa-times"></i></a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($apps)): ?>
            <p class="text-muted mb-0">No applications found.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Applicant</th><th>Job Title</th><th>Company</th><th>Applied Date</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($apps as $app): ?>
                    <tr>
                        <td>
                            <a href="view_alumni.php?id=<?= (int)$app['alumni_id'] ?>" class="text-decoration-none"><?= $e($app['first_name'] . ' ' . $app['last_name']) ?></a><br>
                            <small class="text-muted"><?= $e($app['email']) ?></small>
                        </td>
                        <td><?= $e($app['job_title']) ?></td>
                        <td><?= $e($app['company']) ?></td>
                        <td><?= date('M j, Y', strtotime($app['applied_date'])) ?></td>
                        <td><span class="badge bg-<?= $badge[$app['status']] ?? 'secondary' ?>"><?= ucfirst($app['status']) ?></span></td>
                        <td>
                            <form method="post" class="d-flex gap-1 flex-wrap">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                                <input type="hidden" name="job_id" value="<?= $job_id ?>">
                                <input type="hidden" name="filter_status" value="<?= $e($status_filter) ?>">
                                <input type="hidden" name="q" value="<?= $e($search) ?>">
                                <button name="new_status" value="reviewed" class="btn btn-sm btn-info" <?= $app['status'] === 'reviewed' ? 'disabled' : '' ?>>Reviewed</button>
                                <button name="new_status" value="accepted" class="btn btn-sm btn-success" <?= $app['status'] === 'accepted' ? 'disabled' : '' ?>>Accept</button>
                                <button name="new_status" value="rejected" class="btn btn-sm btn-danger" <?= $app['status'] === 'rejected' ? 'disabled' : '' ?>>Reject</button>
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

<?php include '../includes/footer.php'; ?>