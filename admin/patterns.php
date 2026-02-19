<?php
require_once '../includes/admin_header.php';
$patterns = [
    ['from' => 'Intern', 'to' => 'Junior Developer', 'timeframe' => '6-12 months', 'programs' => 'BSIS, BSCS'],
    ['from' => 'Junior Developer', 'to' => 'Software Developer', 'timeframe' => '1-2 years', 'programs' => 'BSIS, BSCS'],
    ['from' => 'Software Developer', 'to' => 'Senior Developer', 'timeframe' => '2-3 years', 'programs' => 'BSIS, BSCS'],
];
?>
<div class="page-header">
    <h1>Career Path Patterns</h1>
</div>

<div class="row g-4">
    <?php foreach ($patterns as $p): ?>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><?= $p['from'] ?> → <?= $p['to'] ?></h5>
                <p><strong>Average Timeframe:</strong> <?= $p['timeframe'] ?></p>
                <p><strong>Common Programs:</strong> <?= $p['programs'] ?></p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include '../includes/footer.php'; ?>