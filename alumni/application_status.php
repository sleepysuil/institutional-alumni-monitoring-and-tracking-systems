<?php
require_once '../includes/alumni_header.php';
$alumni_id = $_SESSION['alumni_id'];
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Withdraw a pending application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $del = $pdo->prepare("DELETE FROM applications WHERE id = ? AND alumni_id = ? AND status = 'pending'");
        $del->execute([(int)$_POST['application_id'], $alumni_id]);
        $_SESSION['message'] = $del->rowCount() ? "Application withdrawn." : "Only pending applications can be withdrawn.";
    }
    redirect('application_status.php');
}
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$allowed = ['pending', 'reviewed', 'accepted', 'rejected'];
$filter = in_array($_GET['status'] ?? '', $allowed, true) ? $_GET['status'] : 'all';

// Fetch all applications
$stmt = $pdo->prepare("
    SELECT a.*, j.title, j.company, j.location, j.status AS job_status
    FROM applications a
    JOIN job_postings j ON a.job_id = j.id
    WHERE a.alumni_id = ?
    ORDER BY a.applied_date DESC
");
$stmt->execute([$alumni_id]);
$all_apps = $stmt->fetchAll();

// Counts per status
$counts = array_fill_keys($allowed, 0);
foreach ($all_apps as $a) {
    if (isset($counts[$a['status']])) $counts[$a['status']]++;
}
$apps = $filter === 'all' ? $all_apps : array_filter($all_apps, fn($a) => $a['status'] === $filter);

$badge = ['pending' => 'warning', 'reviewed' => 'info', 'accepted' => 'success', 'rejected' => 'danger'];
?>
<div class="page-header">
    <h1>My Job Applications</h1>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<?php if (count($all_apps) == 0): ?>
    <div class="alert alert-info">You haven't applied to any jobs yet. <a href="job_opportunities.php">Browse jobs</a></div>
<?php else: ?>

<div class="row g-3 mb-4">
    <?php foreach ($allowed as $s): ?>
    <div class="col-6 col-md-3">
        <a href="?status=<?= $s ?>" class="text-decoration-none">
            <div class="card text-center <?= $filter === $s ? 'border-primary' : '' ?>">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-<?= $badge[$s] ?>"><?= $counts[$s] ?></div>
                    <div class="small text-muted"><?= ucfirst($s) ?></div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<div class="mb-3">
    <a href="?status=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">All (<?= count($all_apps) ?>)</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($apps)): ?>
            <p class="text-muted mb-0">No <?= htmlspecialchars($filter) ?> applications.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Location</th>
                        <th>Applied Date</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apps as $a): ?>
                    <tr>
                        <td>
                            <?php if ($a['job_status'] === 'active'): ?>
                                <a href="job_details.php?id=<?= $a['job_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($a['title']) ?></a>
                            <?php else: ?>
                                <?= htmlspecialchars($a['title']) ?> <span class="badge bg-secondary">Closed</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($a['company']) ?></td>
                        <td><?= htmlspecialchars($a['location'] ?? '') ?></td>
                        <td><?= date('M j, Y', strtotime($a['applied_date'])) ?></td>
                        <td><span class="badge bg-<?= $badge[$a['status']] ?? 'secondary' ?>"><?= ucfirst($a['status']) ?></span></td>
                        <td class="text-end">
                            <?php if ($a['status'] === 'pending'): ?>
                            <form method="post" class="d-inline" onsubmit="return confirm('Withdraw this application?')">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="application_id" value="<?= $a['id'] ?>">
                                <button type="submit" name="withdraw" class="btn btn-sm btn-outline-danger">Withdraw</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>