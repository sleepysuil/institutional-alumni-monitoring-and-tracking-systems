<?php
require_once '../includes/admin_header.php';
$id = $_GET['id'] ?? 0;
$alumni = $pdo->prepare("SELECT * FROM alumni WHERE id = ?");
$alumni->execute([$id]);
$alumni = $alumni->fetch();
if (!$alumni) redirect('alumni_directory.php');

$employment = $pdo->prepare("SELECT * FROM employment WHERE alumni_id = ?");
$employment->execute([$id]);
$employment = $employment->fetch();

$history = $pdo->prepare("SELECT * FROM employment_history WHERE alumni_id = ? ORDER BY start_date DESC");
$history->execute([$id]);
$history = $history->fetchAll();
?>
<div class="page-header">
    <h1>Alumni Details: <?= htmlspecialchars($alumni['first_name'] . ' ' . $alumni['last_name']) ?></h1>
    <a href="alumni_directory.php" class="btn btn-outline"><i class="fas fa-arrow-left me-2"></i>Back</a>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if ($alumni['profile_pic'] && file_exists('../' . $alumni['profile_pic'])): ?>
                    <img src="<?= SITE_URL ?>/<?= $alumni['profile_pic'] ?>" alt="Profile" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-user-circle fa-5x mb-3" style="color: var(--primary);"></i>
                <?php endif; ?>
                <h5><?= htmlspecialchars($alumni['first_name'] . ' ' . $alumni['last_name']) ?></h5>
                <p class="text-muted"><?= $alumni['student_id'] ?></p>
                <hr>
                <p><strong>Program:</strong> <?= htmlspecialchars($alumni['program']) ?></p>
                <p><strong>Graduated:</strong> <?= $alumni['graduation_year'] ?></p>
                <p><strong>Email:</strong> <?= $alumni['email'] ?></p>
                <p><strong>Phone:</strong> <?= $alumni['phone'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">Current Employment</div>
            <div class="card-body">
                <?php if ($employment): ?>
                <div class="row">
                    <div class="col-sm-6"><strong>Status:</strong> <span class="badge <?= strtolower(str_replace(' ', '-', $employment['status'])) ?>"><?= $employment['status'] ?></span></div>
                    <div class="col-sm-6"><strong>Company:</strong> <?= htmlspecialchars($employment['company'] ?? 'N/A') ?></div>
                    <div class="col-sm-6 mt-2"><strong>Position:</strong> <?= htmlspecialchars($employment['position'] ?? 'N/A') ?></div>
                    <div class="col-sm-6 mt-2"><strong>Industry:</strong> <?= htmlspecialchars($employment['industry'] ?? 'N/A') ?></div>
                    <div class="col-sm-6 mt-2"><strong>Salary:</strong> <?= htmlspecialchars($employment['salary_range'] ?? 'N/A') ?></div>
                    <div class="col-sm-6 mt-2"><strong>Relevance:</strong> <?= $employment['relevance'] ?? 'N/A' ?></div>
                    <div class="col-sm-6 mt-2"><strong>Start Date:</strong> <?= $employment['start_date'] ?? 'N/A' ?></div>
                </div>
                <?php else: ?>
                <p class="text-muted">No employment information.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($history) > 0): ?>
        <div class="card">
            <div class="card-header">Employment History</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Company</th><th>Position</th><th>Start</th><th>End</th></tr></thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?= htmlspecialchars($h['company']) ?></td>
                                <td><?= htmlspecialchars($h['position']) ?></td>
                                <td><?= $h['start_date'] ?></td>
                                <td><?= $h['end_date'] ?? 'Present' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>