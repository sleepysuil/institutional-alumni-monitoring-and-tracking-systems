<?php
require_once '../includes/admin_header.php';

$search = $_GET['search'] ?? '';
$program = $_GET['program'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Build WHERE conditions for alumni table (no status filter for now)
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(a.first_name LIKE ? OR a.last_name LIKE ? OR a.student_id LIKE ? OR a.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($program && $program != 'All Programs') {
    $where_conditions[] = "a.program = ?";
    $params[] = $program;
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Count query
$count_sql = "SELECT COUNT(*) FROM alumni a $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Main query
$sql = "SELECT a.*,
               (SELECT is_employed FROM tracer_responses WHERE alumni_id = a.id ORDER BY response_date DESC LIMIT 1) as is_employed,
               (SELECT survey_data FROM tracer_responses WHERE alumni_id = a.id ORDER BY response_date DESC LIMIT 1) as survey_data,
               CASE 
                   WHEN (SELECT is_employed FROM tracer_responses WHERE alumni_id = a.id ORDER BY response_date DESC LIMIT 1) = 1 THEN 'Employed'
                   WHEN (SELECT is_employed FROM tracer_responses WHERE alumni_id = a.id ORDER BY response_date DESC LIMIT 1) = 0 THEN 'Unemployed'
                   WHEN (SELECT is_employed FROM tracer_responses WHERE alumni_id = a.id ORDER BY response_date DESC LIMIT 1) = 2 THEN 'Self-Employed'
                   ELSE 'No Survey'
               END as employment_status
        FROM alumni a
        $where_sql
        ORDER BY a.last_name
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);

// Bind filter parameters (all strings)
$param_index = 1;
foreach ($params as $val) {
    $stmt->bindValue($param_index++, $val, PDO::PARAM_STR);
}
// Bind LIMIT and OFFSET as integers
$stmt->bindValue($param_index++, (int)$limit, PDO::PARAM_INT);
$stmt->bindValue($param_index++, (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$alumni = $stmt->fetchAll();

$programs = $pdo->query("SELECT DISTINCT program FROM alumni")->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="page-header">
    <h1>Alumni Directory</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
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
                    <option value="Employed" <?= $status_filter == 'Employed' ? 'selected' : '' ?>>Employed</option>
                    <option value="Unemployed" <?= $status_filter == 'Unemployed' ? 'selected' : '' ?>>Unemployed</option>
                    <option value="Self-Employed" <?= $status_filter == 'Self-Employed' ? 'selected' : '' ?>>Self-Employed</option>
                    <option value="No Survey" <?= $status_filter == 'No Survey' ? 'selected' : '' ?>>No Survey</option>
                </select>
            </div>
            <div class="col-md-2">
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
                    </div>
                    <span class="badge 
                        <?= $a['employment_status'] == 'Employed' ? 'bg-success' : ($a['employment_status'] == 'Unemployed' ? 'bg-danger' : ($a['employment_status'] == 'Self-Employed' ? 'bg-info' : 'bg-secondary')) ?>">
                        <?= $a['employment_status'] ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($total_pages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>