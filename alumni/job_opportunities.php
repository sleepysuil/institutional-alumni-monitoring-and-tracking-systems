<?php
require_once '../includes/alumni_header.php';
$alumni_id = $_SESSION['alumni_id'];

$q = trim($_GET['q'] ?? '');
$location = trim($_GET['location'] ?? '');

// Only active jobs that haven't passed their deadline
$sql = "SELECT * FROM job_postings WHERE status='active' AND (deadline IS NULL OR deadline >= CURDATE())";
$params = [];
if ($q !== '') {
    $sql .= " AND (title LIKE ? OR company LIKE ? OR description LIKE ? OR requirements LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($location !== '') {
    $sql .= " AND location = ?";
    $params[] = $location;
}
$sql .= " ORDER BY posted_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Locations for the filter dropdown
$locations = $pdo->query("SELECT DISTINCT location FROM job_postings
                          WHERE status='active' AND location IS NOT NULL AND location <> ''
                          ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);

// Jobs this alumnus already applied to
$appliedStmt = $pdo->prepare("SELECT job_id FROM applications WHERE alumni_id = ?");
$appliedStmt->execute([$alumni_id]);
$applied_ids = array_flip($appliedStmt->fetchAll(PDO::FETCH_COLUMN));
?>
<div class="page-header">
    <h1>Job Opportunities</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="q" class="form-control" placeholder="Search title, company or keywords..." value="<?= htmlspecialchars($q) ?>">
            </div>
            <div class="col-md-3">
                <select name="location" class="form-select">
                    <option value="">All locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= htmlspecialchars($loc) ?>" <?= $location === $loc ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-search me-1"></i>Search</button>
                <?php if ($q !== '' || $location !== ''): ?>
                    <a href="job_opportunities.php" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (empty($jobs)): ?>
    <div class="alert alert-info">No job opportunities match your search at the moment.</div>
<?php else: ?>
<p class="text-muted small"><?= count($jobs) ?> job<?= count($jobs) > 1 ? 's' : '' ?> found</p>
<div class="row g-4">
    <?php foreach ($jobs as $job):
        $days_left = !empty($job['deadline']) ? (int)floor((strtotime($job['deadline']) - strtotime('today')) / 86400) : null;
        $has_applied = isset($applied_ids[$job['id']]);
        $desc = $job['description'] ?? '';
    ?>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="card-title"><?= htmlspecialchars($job['title']) ?></h5>
                    <?php if ($has_applied): ?>
                        <span class="badge bg-success">Applied</span>
                    <?php elseif ($days_left !== null && $days_left <= 7): ?>
                        <span class="badge bg-warning text-dark">Closing soon</span>
                    <?php endif; ?>
                </div>
                <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($job['company']) ?> • <?= htmlspecialchars($job['location'] ?? '') ?></h6>
                <p class="card-text small"><?= htmlspecialchars(mb_substr($desc, 0, 120)) ?><?= mb_strlen($desc) > 120 ? '...' : '' ?></p>
                <p class="small mb-2"><i class="far fa-calendar-alt me-1"></i> Deadline: <?= $job['deadline'] ? date('M j, Y', strtotime($job['deadline'])) : 'None' ?></p>
                <a href="job_details.php?id=<?= $job['id'] ?>" class="btn btn-primary btn-sm">View Details</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>