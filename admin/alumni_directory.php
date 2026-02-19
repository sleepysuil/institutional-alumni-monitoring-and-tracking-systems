<?php
require_once '../includes/admin_header.php';
$search = $_GET['search'] ?? '';
$program = $_GET['program'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT a.*, e.status, e.company, e.position, e.industry, e.relevance 
        FROM alumni a 
        LEFT JOIN employment e ON a.id = e.alumni_id 
        WHERE 1";
$params = [];
if ($search) {
    $sql .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.student_id LIKE ? OR a.email LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}
if ($program && $program != 'All Programs') {
    $sql .= " AND a.program = ?";
    $params[] = $program;
}
if ($status && $status != 'All Status') {
    $sql .= " AND e.status = ?";
    $params[] = $status;
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alumni = $stmt->fetchAll();

$programs = $pdo->query("SELECT DISTINCT program FROM alumni")->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="page-header">
    <h1>Alumni Directory</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Search by name, ID, or email..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="program" class="form-select">
                    <option value="">All Programs</option>
                    <?php foreach ($programs as $p): ?>
                        <option value="<?= $p ?>" <?= $program == $p ? 'selected' : '' ?>><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="Employed" <?= $status == 'Employed' ? 'selected' : '' ?>>Employed</option>
                    <option value="Self-Employed" <?= $status == 'Self-Employed' ? 'selected' : '' ?>>Self-Employed</option>
                    <option value="Unemployed" <?= $status == 'Unemployed' ? 'selected' : '' ?>>Unemployed</option>
                    <option value="Pursuing Higher Education" <?= $status == 'Pursuing Higher Education' ? 'selected' : '' ?>>Pursuing Higher Education</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($alumni as $a): ?>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                    <?php if ($a['profile_pic'] && file_exists('../' . $a['profile_pic'])): ?>
                        <img src="<?= SITE_URL ?>/<?= $a['profile_pic'] ?>" alt="Profile" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user-circle fa-3x" style="color: var(--primary);"></i>
                    <?php endif; ?>
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-1">
                            <a href="view_alumni.php?id=<?= $a['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></a>
                        </h5>
                        <p class="text-muted small mb-2"><?= $a['student_id'] ?></p>
                        <p class="mb-2"><i class="fas fa-envelope me-2"></i><?= $a['email'] ?><br><i class="fas fa-phone me-2"></i><?= $a['phone'] ?></p>
                        <p class="mb-2"><strong><?= $a['program'] ?></strong> • Class of <?= $a['graduation_year'] ?></p>
                        <?php if (!empty($a['company'])): ?>
                        <div class="mt-2 pt-2 border-top">
                            <p class="mb-1"><strong><?= $a['position'] ?></strong> at <?= $a['company'] ?></p>
                            <p class="small mb-2"><?= $a['industry'] ?> • <span class="badge bg-secondary"><?= $a['relevance'] ?></span></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <span class="badge <?= strtolower(str_replace(' ', '-', $a['status'] ?? 'unemployed')) ?>"><?= $a['status'] ?? 'No status' ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include '../includes/footer.php'; ?>