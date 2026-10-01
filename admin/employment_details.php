<?php
require_once '../includes/admin_header.php';

$search  = trim($_GET['search'] ?? '');
$program = trim($_GET['program'] ?? '');
$year    = (int)($_GET['year'] ?? 0);
$status_options = ['Employed', 'Self-Employed', 'Unemployed', 'Pursuing Higher Education', 'No Record'];
$status  = in_array($_GET['status'] ?? '', $status_options, true) ? $_GET['status'] : '';

$sql = "SELECT a.*, e.status, e.company, e.position, e.industry, e.salary_range, e.relevance, e.start_date
        FROM alumni a
        LEFT JOIN employment e ON a.id = e.alumni_id
        WHERE 1";
$params = [];
if ($search !== '') {
    $sql .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.student_id LIKE ? OR e.company LIKE ? OR e.position LIKE ?)";
    for ($i = 0; $i < 5; $i++) $params[] = "%$search%";
}
if ($program !== '') { $sql .= " AND a.program = ?"; $params[] = $program; }
if ($year)           { $sql .= " AND a.graduation_year = ?"; $params[] = $year; }
if ($status === 'No Record') {
    $sql .= " AND e.status IS NULL";
} elseif ($status !== '') {
    $sql .= " AND e.status = ?"; $params[] = $status;
}
$sql .= " ORDER BY a.last_name, a.first_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alumni = $stmt->fetchAll();

// Summary of the rows currently shown
$summary = ['Employed' => 0, 'Self-Employed' => 0, 'Unemployed' => 0, 'Pursuing Higher Education' => 0, 'No Record' => 0];
foreach ($alumni as $a) $summary[$a['status'] ?? 'No Record']++;

$programs = $pdo->query("SELECT DISTINCT program FROM alumni ORDER BY program")->fetchAll(PDO::FETCH_COLUMN);
$years = $pdo->query("SELECT DISTINCT graduation_year FROM alumni ORDER BY graduation_year DESC")->fetchAll(PDO::FETCH_COLUMN);

$e = fn($v) => htmlspecialchars((string)($v ?? ''));
$export_qs = http_build_query(array_filter(['search' => $search, 'program' => $program, 'year' => $year ?: null, 'status' => $status]));
$has_filters = ($search !== '' || $program !== '' || $year || $status !== '');
?>
<div class="page-header">
    <h1>Employment Details</h1>
    <div class="btn-group">
        <a class="btn btn-outline-primary btn-sm" href="export.php?format=excel&<?= $export_qs ?>"><i class="fas fa-file-excel me-1"></i>Excel</a>
        <a class="btn btn-outline-primary btn-sm" href="export.php?format=csv&<?= $export_qs ?>"><i class="fas fa-file-csv me-1"></i>CSV</a>
        <a class="btn btn-outline-primary btn-sm" href="export.php?format=pdf&<?= $export_qs ?>"><i class="fas fa-file-pdf me-1"></i>PDF</a>
    </div>
</div>

<ul class="nav-tabs">
    <li><a class="nav-link" href="tracer_module.php">Overview</a></li>
    <li><a class="nav-link active" href="employment_details.php">Employment Details</a></li>
    <li><a class="nav-link" href="job_relevance.php">Job Relevance</a></li>
</ul>

<div class="row g-3 my-3">
    <?php $colors = ['Employed' => 'success', 'Self-Employed' => 'info', 'Unemployed' => 'danger', 'Pursuing Higher Education' => 'primary', 'No Record' => 'secondary']; ?>
    <?php foreach ($summary as $label => $n): ?>
    <div class="col-6 col-md">
        <div class="card text-center"><div class="card-body py-2">
            <div class="fs-4 fw-bold text-<?= $colors[$label] ?>"><?= $n ?></div>
            <div class="small text-muted"><?= $e($label) ?></div>
        </div></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="Name, ID, company, position..." value="<?= $e($search) ?>"></div>
            <div class="col-md-3">
                <select name="program" class="form-select">
                    <option value="">All Programs</option>
                    <?php foreach ($programs as $p): ?><option value="<?= $e($p) ?>" <?= $program === $p ? 'selected' : '' ?>><?= $e($p) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="year" class="form-select">
                    <option value="">All Years</option>
                    <?php foreach ($years as $y): ?><option value="<?= (int)$y ?>" <?= $year == $y ? 'selected' : '' ?>><?= (int)$y ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <?php foreach ($status_options as $s): ?><option value="<?= $e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= $e($s) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit"><i class="fas fa-filter"></i></button>
                <?php if ($has_filters): ?><a href="employment_details.php" class="btn btn-outline-secondary" title="Clear"><i class="fas fa-times"></i></a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-muted small"><?= count($alumni) ?> record<?= count($alumni) == 1 ? '' : 's' ?></p>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th><th>Program</th><th>Year</th><th>Status</th><th>Company</th><th>Position</th><th>Industry</th><th>Salary</th><th>Relevance</th><th>Start</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alumni)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-4">No records match your filters.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($alumni as $a): ?>
                    <tr>
                        <td><a href="view_alumni.php?id=<?= (int)$a['id'] ?>" class="text-decoration-none"><?= $e($a['first_name'] . ' ' . $a['last_name']) ?></a></td>
                        <td><?= $e($a['program']) ?></td>
                        <td><?= (int)$a['graduation_year'] ?></td>
                        <td>
                            <?php if ($a['status']): ?>
                                <span class="badge <?= strtolower(str_replace(' ', '-', $a['status'])) ?>"><?= $e($a['status']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">No Record</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $e($a['company'] ?? 'N/A') ?></td>
                        <td><?= $e($a['position'] ?? 'N/A') ?></td>
                        <td><?= $e($a['industry'] ?? 'N/A') ?></td>
                        <td><?= $e($a['salary_range'] ?? 'N/A') ?></td>
                        <td><?= $e($a['relevance'] ?? 'N/A') ?></td>
                        <td><?= $a['start_date'] ? date('Y-m-d', strtotime($a['start_date'])) : 'N/A' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>