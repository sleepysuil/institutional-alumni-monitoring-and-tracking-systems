<?php
require_once '../includes/admin_header.php';

$report_type = $_GET['report_type'] ?? 'list';
$year = $_GET['year'] ?? '';
$program = $_GET['program'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$sql = "SELECT a.*, e.status, e.company, e.position, e.industry 
        FROM alumni a 
        LEFT JOIN employment e ON a.id = e.alumni_id 
        WHERE 1";
$params = [];
if ($year && $year != 'All Years') {
    $sql .= " AND a.graduation_year = ?";
    $params[] = $year;
}
if ($program && $program != 'All Programs') {
    $sql .= " AND a.program = ?";
    $params[] = $program;
}
if ($start_date) {
    $sql .= " AND a.created_at >= ?";
    $params[] = $start_date . ' 00:00:00';
}
if ($end_date) {
    $sql .= " AND a.created_at <= ?";
    $params[] = $end_date . ' 23:59:59';
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alumni = $stmt->fetchAll();

$years = $pdo->query("SELECT DISTINCT graduation_year FROM alumni ORDER BY graduation_year DESC")->fetchAll(PDO::FETCH_COLUMN);
$programs = $pdo->query("SELECT DISTINCT program FROM alumni")->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="page-header">
    <h1>Reports</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Report Type</label>
                <select name="report_type" class="form-select">
                    <option value="list" <?= $report_type == 'list' ? 'selected' : '' ?>>List of All Alumni</option>
                    <option value="employment" <?= $report_type == 'employment' ? 'selected' : '' ?>>Employment Status Report</option>
                    <option value="industry" <?= $report_type == 'industry' ? 'selected' : '' ?>>Industry Distribution</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Graduation Year</label>
                <select name="year" class="form-select">
                    <option value="">All Years</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Program</label>
                <select name="program" class="form-select">
                    <option value="">All Programs</option>
                    <?php foreach ($programs as $p): ?>
                        <option value="<?= $p ?>" <?= $program == $p ? 'selected' : '' ?>><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary w-100">Generate</button>
            </div>
        </form>
    </div>
</div>

<div class="mb-3">
    <a href="export.php?format=pdf&<?= http_build_query($_GET) ?>" class="btn btn-danger" target="_blank"><i class="fas fa-file-pdf me-2"></i>Export PDF</a>
    <a href="export.php?format=excel&<?= http_build_query($_GET) ?>" class="btn btn-success" target="_blank"><i class="fas fa-file-excel me-2"></i>Export Excel</a>
    <a href="export.php?format=csv&<?= http_build_query($_GET) ?>" class="btn btn-info" target="_blank"><i class="fas fa-file-csv me-2"></i>Export CSV</a>
</div>

<div class="card">
    <div class="card-header">Report Preview (<?= count($alumni) ?> records)</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>Student ID</th><th>Name</th><th>Program</th><th>Year</th><th>Status</th><th>Company</th><th>Position</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($alumni as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['student_id']) ?></td>
                        <td><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></td>
                        <td><?= htmlspecialchars($a['program']) ?></td>
                        <td><?= $a['graduation_year'] ?></td>
                        <td><?= $a['status'] ?? 'N/A' ?></td>
                        <td><?= htmlspecialchars($a['company'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($a['position'] ?? 'N/A') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>