<?php
require_once '../includes/admin_header.php';

$report_type = in_array($_GET['report_type'] ?? 'list', ['list', 'employment', 'industry'], true) ? $_GET['report_type'] : 'list';
$year = (int)($_GET['year'] ?? 0);
$program = trim($_GET['program'] ?? '');
$status_options = ['Employed', 'Self-Employed', 'Unemployed', 'Pursuing Higher Education', 'No Record'];
$status = in_array($_GET['status'] ?? '', $status_options, true) ? $_GET['status'] : '';
$start_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['start_date'] ?? '') ? $_GET['start_date'] : '';
$end_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['end_date'] ?? '') ? $_GET['end_date'] : '';
$date_error = ($start_date && $end_date && $end_date < $start_date) ? "The end date is earlier than the start date." : '';

$sql = "SELECT a.*, e.status, e.company, e.position, e.industry
        FROM alumni a
        LEFT JOIN employment e ON a.id = e.alumni_id
        WHERE 1";
$params = [];
if ($year)    { $sql .= " AND a.graduation_year = ?"; $params[] = $year; }
if ($program !== '') { $sql .= " AND a.program = ?"; $params[] = $program; }
if ($status === 'No Record') {
    $sql .= " AND e.status IS NULL";
} elseif ($status !== '') {
    $sql .= " AND e.status = ?"; $params[] = $status;
}
if ($start_date) { $sql .= " AND a.created_at >= ?"; $params[] = $start_date . ' 00:00:00'; }
if ($end_date)   { $sql .= " AND a.created_at <= ?"; $params[] = $end_date . ' 23:59:59'; }
$sql .= " ORDER BY a.graduation_year DESC, a.last_name, a.first_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alumni = $stmt->fetchAll();
$total = count($alumni);

// Summaries for the two aggregate report types
$by_status = $by_industry = $matrix = [];
foreach ($alumni as $a) {
    $s = $a['status'] ?? 'No Record';
    $by_status[$s] = ($by_status[$s] ?? 0) + 1;
    $matrix[$a['program']][$s] = ($matrix[$a['program']][$s] ?? 0) + 1;
    $ind = trim((string)($a['industry'] ?? '')) !== '' ? $a['industry'] : 'Unspecified';
    $by_industry[$ind] = ($by_industry[$ind] ?? 0) + 1;
}
arsort($by_status);
arsort($by_industry);
ksort($matrix);

$years = $pdo->query("SELECT DISTINCT graduation_year FROM alumni ORDER BY graduation_year DESC")->fetchAll(PDO::FETCH_COLUMN);
$programs = $pdo->query("SELECT DISTINCT program FROM alumni ORDER BY program")->fetchAll(PDO::FETCH_COLUMN);

$e = fn($v) => htmlspecialchars((string)($v ?? ''));
$export_qs = http_build_query(array_filter([
    'report_type' => $report_type, 'year' => $year ?: null, 'program' => $program,
    'status' => $status, 'start_date' => $start_date, 'end_date' => $end_date,
], fn($v) => $v !== null && $v !== ''));
$type_titles = ['list' => 'List of All Alumni', 'employment' => 'Employment Status Report', 'industry' => 'Industry Distribution'];
$preview_limit = 200;
$pct = fn($n) => $total ? round($n / $total * 100, 1) : 0;
?>
<div class="page-header">
    <h1>Reports</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Report Type</label>
                <select name="report_type" class="form-select">
                    <?php foreach ($type_titles as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $report_type == $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Graduation Year</label>
                <select name="year" class="form-select">
                    <option value="">All Years</option>
                    <?php foreach ($years as $y): ?><option value="<?= (int)$y ?>" <?= $year == $y ? 'selected' : '' ?>><?= (int)$y ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Program</label>
                <select name="program" class="form-select">
                    <option value="">All Programs</option>
                    <?php foreach ($programs as $p): ?><option value="<?= $e($p) ?>" <?= $program === $p ? 'selected' : '' ?>><?= $e($p) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Employment Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($status_options as $s): ?><option value="<?= $e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= $e($s) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Registered From</label>
                <input type="date" name="start_date" class="form-control" value="<?= $e($start_date) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Registered To</label>
                <input type="date" name="end_date" class="form-control" value="<?= $e($end_date) ?>">
            </div>
            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn btn-primary w-100">Generate</button>
            </div>
            <div class="col-md-3 align-self-end">
                <a href="reports.php" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if ($date_error): ?><div class="alert alert-warning"><?= $e($date_error) ?></div><?php endif; ?>

<div class="mb-3">
    <a href="export.php?format=pdf&<?= $export_qs ?>" class="btn btn-danger" target="_blank"><i class="fas fa-file-pdf me-2"></i>Export PDF</a>
    <a href="export.php?format=excel&<?= $export_qs ?>" class="btn btn-success" target="_blank"><i class="fas fa-file-excel me-2"></i>Export Excel</a>
    <a href="export.php?format=csv&<?= $export_qs ?>" class="btn btn-info" target="_blank"><i class="fas fa-file-csv me-2"></i>Export CSV</a>
</div>

<div class="card">
    <div class="card-header"><?= $e($type_titles[$report_type]) ?> <small class="text-muted">· <?= $total ?> record<?= $total == 1 ? '' : 's' ?></small></div>
    <div class="card-body">
    <?php if ($total === 0): ?>
        <p class="text-muted mb-0">No records match the selected filters.</p>

    <?php elseif ($report_type === 'employment'): ?>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-bordered text-center">
                <thead class="table-light"><tr><th>Status</th><th>Count</th><th>Percentage</th></tr></thead>
                <tbody>
                <?php foreach ($by_status as $s => $n): ?>
                    <tr><td><?= $e($s) ?></td><td><?= $n ?></td><td><?= $pct($n) ?>%</td></tr>
                <?php endforeach; ?>
                <tr class="fw-bold table-light"><td>Total</td><td><?= $total ?></td><td>100%</td></tr>
                </tbody>
            </table>
        </div>
        <h6>By program</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered text-center">
                <thead class="table-light"><tr><th class="text-start">Program</th><?php foreach ($status_options as $s): ?><th><?= $e($s) ?></th><?php endforeach; ?><th>Total</th></tr></thead>
                <tbody>
                <?php foreach ($matrix as $prog => $row): ?>
                    <tr>
                        <td class="text-start"><?= $e($prog) ?></td>
                        <?php foreach ($status_options as $s): ?><td><?= $row[$s] ?? 0 ?></td><?php endforeach; ?>
                        <td class="fw-bold"><?= array_sum($row) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($report_type === 'industry'): ?>
        <div class="table-responsive">
            <table class="table table-sm table-bordered text-center">
                <thead class="table-light"><tr><th class="text-start">Industry</th><th>Count</th><th>Percentage</th></tr></thead>
                <tbody>
                <?php foreach ($by_industry as $ind => $n): ?>
                    <tr><td class="text-start"><?= $e($ind) ?></td><td><?= $n ?></td><td><?= $pct($n) ?>%</td></tr>
                <?php endforeach; ?>
                <tr class="fw-bold table-light"><td class="text-start">Total</td><td><?= $total ?></td><td>100%</td></tr>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>Student ID</th><th>Name</th><th>Program</th><th>Year</th><th>Status</th><th>Company</th><th>Position</th></tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($alumni, 0, $preview_limit) as $a): ?>
                    <tr>
                        <td><?= $e($a['student_id']) ?></td>
                        <td><a href="view_alumni.php?id=<?= (int)$a['id'] ?>" class="text-decoration-none"><?= $e($a['first_name'] . ' ' . $a['last_name']) ?></a></td>
                        <td><?= $e($a['program']) ?></td>
                        <td><?= (int)$a['graduation_year'] ?></td>
                        <td><?= $e($a['status'] ?? 'N/A') ?></td>
                        <td><?= $e($a['company'] ?? 'N/A') ?></td>
                        <td><?= $e($a['position'] ?? 'N/A') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total > $preview_limit): ?>
            <p class="small text-muted mb-0">Showing the first <?= $preview_limit ?> of <?= $total ?> records. Exports contain all <?= $total ?>.</p>
        <?php endif; ?>
    <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>