<?php
require_once '../includes/admin_header.php';
$alumni = $pdo->query("SELECT a.*, e.status, e.company, e.position, e.industry, e.salary_range, e.relevance, e.start_date 
                        FROM alumni a 
                        LEFT JOIN employment e ON a.id = e.alumni_id 
                        ORDER BY a.last_name")->fetchAll();
?>
<div class="page-header">
    <h1>Employment Details</h1>
</div>

<ul class="nav-tabs">
    <li><a class="nav-link" href="tracer_module.php">Overview</a></li>
    <li><a class="nav-link active" href="employment_details.php">Employment Details</a></li>
    <li><a class="nav-link" href="job_relevance.php">Job Relevance</a></li>
</ul>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th><th>Program</th><th>Year</th><th>Status</th><th>Company</th><th>Position</th><th>Industry</th><th>Salary</th><th>Start</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alumni as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></td>
                        <td><?= htmlspecialchars($a['program']) ?></td>
                        <td><?= $a['graduation_year'] ?></td>
                        <td><span class="badge <?= strtolower(str_replace(' ', '-', $a['status'] ?? 'unemployed')) ?>"><?= $a['status'] ?? 'N/A' ?></span></td>
                        <td><?= htmlspecialchars($a['company'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($a['position'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($a['industry'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($a['salary_range'] ?? 'N/A') ?></td>
                        <td><?= $a['start_date'] ? date('Y-m-d', strtotime($a['start_date'])) : 'N/A' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>