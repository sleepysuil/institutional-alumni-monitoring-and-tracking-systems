<?php
require_once '../includes/admin_header.php';

// Get program-industry distribution
$program_industries = $pdo->query("
    SELECT a.program, e.industry, COUNT(*) as cnt
    FROM alumni a
    JOIN employment e ON a.id = e.alumni_id
    WHERE e.industry IS NOT NULL
    GROUP BY a.program, e.industry
")->fetchAll();

$skills = [];
$required_skills = [
    'Information Technology' => ['Cloud Computing', 'AI/ML', 'Cybersecurity', 'DevOps', 'Mobile Development'],
    'Accounting' => ['Data Analytics', 'ERP Systems', 'Financial Modeling', 'Compliance'],
    'Marketing' => ['Social Media', 'Analytics', 'Content Creation', 'SEO'],
    'Finance' => ['Financial Analysis', 'Risk Management', 'Excel', 'Bloomberg'],
    'Education' => ['Curriculum Design', 'Teaching', 'Assessment', 'EdTech'],
];

$recommendations = [
    'Information Technology' => ['Offer cloud certification', 'Introduce AI/ML courses', 'Partner with tech companies'],
    'Accounting' => ['Include data analytics in curriculum', 'Provide hands-on ERP training'],
    'Marketing' => ['Update marketing curriculum with digital focus', 'Offer Google Analytics certification'],
];

foreach ($program_industries as $row) {
    $program = $row['program'];
    $industry = $row['industry'];
    $cnt = $row['cnt'];
    
    // Get total alumni in program
    $total_program = $pdo->prepare("SELECT COUNT(*) FROM alumni WHERE program = ?");
    $total_program->execute([$program]);
    $total = $total_program->fetchColumn();
    
    $match = $total ? round(($cnt / $total) * 100, 1) : 0;
    $gap = 100 - $match;
    
    $skills[] = [
        'program' => $program,
        'industry' => $industry,
        'match' => $match,
        'gap' => $gap,
        'required' => $required_skills[$industry] ?? ['General skills'],
        'rec' => $recommendations[$industry] ?? ['Review curriculum']
    ];
}
?>
<div class="page-header">
    <h1>Skills Gap Analysis</h1>
    <p class="text-muted">Program vs Industry match based on employment data</p>
</div>

<?php if (empty($skills)): ?>
    <div class="alert alert-warning">No data available. Please ensure employment records have industry information.</div>
<?php endif; ?>

<?php foreach ($skills as $s): ?>
<div class="card mb-4">
    <div class="card-header"><?= htmlspecialchars($s['program']) ?> → <?= htmlspecialchars($s['industry']) ?></div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p><strong>Current Match:</strong> <?= $s['match'] ?>% <span class="text-danger">Gap: <?= $s['gap'] ?>%</span></p>
                <div class="progress mb-3" style="height: 30px;">
                    <div class="progress-bar bg-success" style="width: <?= $s['match'] ?>%;">Match <?= $s['match'] ?>%</div>
                    <div class="progress-bar bg-danger" style="width: <?= $s['gap'] ?>%;">Gap <?= $s['gap'] ?>%</div>
                </div>
            </div>
            <div class="col-md-6">
                <h6>Required Skills for <?= htmlspecialchars($s['industry']) ?>:</h6>
                <ul>
                    <?php foreach ($s['required'] as $skill): ?><li><?= $skill ?></li><?php endforeach; ?>
                </ul>
                <h6>Recommendations:</h6>
                <ul>
                    <?php foreach ($s['rec'] as $r): ?><li><?= $r ?></li><?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include '../includes/footer.php'; ?>