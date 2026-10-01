<?php
require_once '../includes/admin_header.php';

$search = trim($_GET['search'] ?? '');
$program = trim($_GET['program'] ?? '');
$status_filter = $_GET['status'] ?? '';
$valid_status = ['Employed', 'Unemployed', 'Self-Employed', 'No Survey'];
if (!in_array($status_filter, $valid_status, true)) $status_filter = '';
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));

// Latest tracer-survey answer for an alumnus (1 = employed, 0 = unemployed, 2 = self-employed)
$latest = "(SELECT t.is_employed FROM tracer_responses t WHERE t.alumni_id = a.id ORDER BY t.response_date DESC, t.id DESC LIMIT 1)";

$where_conditions = [];
$params = [];

if ($search !== '') {
    $where_conditions[] = "(a.first_name LIKE ? OR a.last_name LIKE ? OR a.student_id LIKE ? OR a.email LIKE ?)";
    for ($i = 0; $i < 4; $i++) $params[] = "%$search%";
}
if ($program !== '') {
    $where_conditions[] = "a.program = ?";
    $params[] = $program;
}
// The status filter now actually works (it was ignored before)
if ($status_filter === 'Employed')      $where_conditions[] = "$latest = 1";
elseif ($status_filter === 'Unemployed')    $where_conditions[] = "$latest = 0";
elseif ($status_filter === 'Self-Employed') $where_conditions[] = "$latest = 2";
elseif ($status_filter === 'No Survey')     $where_conditions[] = "$latest IS NULL";

$where_sql = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM alumni a $where_sql");
$stmt->execute($params);
$total_records = (int)$stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_records / $limit));
$page = min($page, $total_pages);
$offset = ($page - 1) * $limit;

// Page of results
$sql = "SELECT a.*, $latest AS is_employed
        FROM alumni a
        $where_sql
        ORDER BY a.last_name, a.first_name
        LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$i = 1;
foreach ($params as $val) $stmt->bindValue($i++, $val, PDO::PARAM_STR);
$stmt->bindValue($i++, $limit, PDO::PARAM_INT);
$stmt->bindValue($i++, $offset, PDO::PARAM_INT);
$stmt->execute();
$alumni = $stmt->fetchAll();

function statusFromFlag($flag) {
    if ($flag === null) return 'No Survey';
    return match((int)$flag) { 1 => 'Employed', 2 => 'Self-Employed', default => 'Unemployed' };
}
$badge_class = ['Employed' => 'bg-success', 'Unemployed' => 'bg-danger', 'Self-Employed' => 'bg-info', 'No Survey' => 'bg-secondary'];

$programs = $pdo->query("SELECT DISTINCT program FROM alumni ORDER BY program")->fetchAll(PDO::FETCH_COLUMN);
$e = fn($v) => htmlspecialchars((string)($v ?? ''));
$has_filters = ($search !== '' || $program !== '' || $status_filter !== '');
?>
<div class="page-header">
    <h1>Alumni Directory</h1>
    <span class="text-muted"><?= $total_records ?> alumni<?= $has_filters ? ' found' : '' ?></span>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by name, ID, or email..." value="<?= $e($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="program" class="form-select">
                    <option value="">All Programs</option>
                    <?php foreach ($programs as $p): ?>
                        <option value="<?= $e($p) ?>" <?= $program === $p ? 'selected' : '' ?>><?= $e($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <?php foreach ($valid_status as $s): ?>
                        <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-search"></i></button>
                <?php if ($has_filters): ?><a href="alumni_directory.php" class="btn btn-outline-secondary" title="Clear filters"><i class="fas fa-times"></i></a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (empty($alumni)): ?>
    <div class="alert alert-info">No alumni match your search.</div>
<?php endif; ?>

<div class="row g-4">
    <?php foreach ($alumni as $a): $st = statusFromFlag($a['is_employed']); ?>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                    <?php if (!empty($a['profile_pic']) && file_exists('../' . $a['profile_pic'])): ?>
                        <img src="<?= SITE_URL ?>/<?= $e($a['profile_pic']) ?>" alt="Profile" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user-circle fa-3x" style="color: var(--primary);"></i>
                    <?php endif; ?>
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-1">
                            <a href="view_alumni.php?id=<?= (int)$a['id'] ?>" class="text-decoration-none"><?= $e($a['first_name'] . ' ' . $a['last_name']) ?></a>
                        </h5>
                        <p class="text-muted small mb-2"><?= $e($a['student_id']) ?></p>
                        <p class="mb-2"><i class="fas fa-envelope me-2"></i><?= $e($a['email']) ?><br><i class="fas fa-phone me-2"></i><?= $e($a['phone']) ?></p>
                        <p class="mb-2"><strong><?= $e($a['program']) ?></strong> • Class of <?= (int)$a['graduation_year'] ?></p>
                    </div>
                    <span class="badge <?= $badge_class[$st] ?>"><?= $st ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($total_pages > 1):
    $link = fn($p) => '?' . http_build_query(array_merge($_GET, ['page' => $p]));
    $start = max(1, $page - 2);
    $end = min($total_pages, $page + 2);
?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $link($page - 1) ?>">&laquo;</a></li>
        <?php if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= $link(1) ?>">1</a></li>
            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <?php endif; ?>
        <?php for ($i = $start; $i <= $end; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="<?= $link($i) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
        <?php if ($end < $total_pages): ?>
            <?php if ($end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="<?= $link($total_pages) ?>"><?= $total_pages ?></a></li>
        <?php endif; ?>
        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= $link($page + 1) ?>">&raquo;</a></li>
    </ul>
</nav>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>